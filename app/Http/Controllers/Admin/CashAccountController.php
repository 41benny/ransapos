<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashAccountRequest;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Requests\UpdateCashAccountRequest;
use App\Models\BankTransfer;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Models\Purchase;
use App\Services\CashAccountService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CashAccountController extends Controller
{
    protected $cashAccountService;

    public function __construct(CashAccountService $cashAccountService)
    {
        $this->cashAccountService = $cashAccountService;
    }

    /**
     * Display a listing of cash accounts
     */
    public function index()
    {
        $summary = $this->cashAccountService->getAccountsSummary();

        return view('admin.cash-accounts.index', [
            'accounts' => $summary['accounts'],
            'totalCash' => $summary['total_cash'],
            'totalBank' => $summary['total_bank'],
            'totalBalance' => $summary['total_balance'],
        ]);
    }

    /**
     * Show the form for creating a new account
     */
    public function create()
    {
        return view('admin.cash-accounts.create');
    }

    /**
     * Store a newly created account
     */
    public function store(StoreCashAccountRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id() ?? 1; // TODO: Replace with actual auth

            $account = $this->cashAccountService->createAccount($data);

            return redirect()
                ->route('admin.cash-accounts.show', $account)
                ->with('success', 'Akun kas/bank berhasil dibuat!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified account
     */
    public function show(CashAccount $cashAccount)
    {
        $cashAccount->load(['creator', 'transactions' => function ($query) {
            $query->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->limit(10);
        }]);

        // Summary untuk akun ini
        $totalIn = $cashAccount->transactions()->where('type', 'in')->sum('amount');
        $totalOut = $cashAccount->transactions()->where('type', 'out')->sum('amount');

        return view('admin.cash-accounts.show', compact('cashAccount', 'totalIn', 'totalOut'));
    }

    /**
     * Show the form for editing the specified account
     */
    public function edit(CashAccount $cashAccount)
    {
        $outlets = \App\Models\Outlet::active()->orderBy('name')->get();

        return view('admin.cash-accounts.edit', compact('cashAccount', 'outlets'));
    }

    /**
     * Update the specified account
     */
    public function update(UpdateCashAccountRequest $request, CashAccount $cashAccount)
    {
        try {
            $data = $request->validated();

            $account = $this->cashAccountService->updateAccount($cashAccount, $data);

            return redirect()
                ->route('admin.cash-accounts.show', $account)
                ->with('success', 'Akun kas/bank berhasil diupdate!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified account from storage
     */
    public function destroy(CashAccount $cashAccount)
    {
        try {
            // Validasi: tidak bisa hapus jika ada transaksi
            if ($cashAccount->transactions()->count() > 0) {
                return back()->with('error', 'Tidak bisa hapus akun yang sudah memiliki transaksi!');
            }

            $cashAccount->delete();

            return redirect()
                ->route('admin.cash-accounts.index')
                ->with('success', 'Akun kas/bank berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display transactions list
     */
    public function transactions(Request $request)
    {
        $filters = $request->only([
            'transaction_number',
            'transaction_date',
            'cash_account_id',
            'outlet_id',
            'type',
            'description',
            'amount',
            'balance_after',
            'date_from',
            'date_to',
            'reference_type',
            'coa_account_id',
            'coa_type',
            'coa_group',
            'exclude_coa_group',
        ]);

        $transactions = $this->cashAccountService->getTransactions($filters);
        $totals = $this->cashAccountService->getTransactionTotals($filters);
        $accounts = CashAccount::active()->orderBy('name')->get();
        $outlets = \App\Models\Outlet::active()->orderBy('name')->get();
        $coaAccounts = CoaAccount::active()->orderBy('code')->get();
        $coaGroups = CoaAccount::query()->where('is_active', true)->distinct()->orderBy('group')->pluck('group');

        return view('admin.cash-accounts.transactions', compact('transactions', 'accounts', 'outlets', 'coaAccounts', 'coaGroups', 'filters', 'totals'));
    }

    /**
     * Show form to create new transaction
     */
    public function createTransaction()
    {
        $accounts = CashAccount::active()->orderBy('name')->get();
        $coaIncomeAccounts = \App\Models\CoaAccount::active()->income()->orderBy('code')->get();
        $coaExpenseAccounts = \App\Models\CoaAccount::active()
            ->expense()
            ->orderByRaw('CASE WHEN code = ? THEN 1 ELSE 0 END', ['6-135'])
            ->orderBy('code')
            ->get();

        $outstandingPurchases = Purchase::query()
            ->with(['supplier', 'outlet'])
            ->where('status', 'received')
            ->where(function ($query) {
                $query->whereIn('payment_status', ['unpaid', 'partial'])
                    ->orWhereNull('payment_status');
            })
            ->withSum('cashTransactions as total_paid', 'amount')
            ->orderByDesc('purchase_date')
            ->get()
            ->map(function (Purchase $purchase) {
                $totalPaid = (float) ($purchase->total_paid ?? 0);
                $purchase->remaining_amount = max(0, (float) $purchase->total_amount - $totalPaid);
                return $purchase;
            })
            ->filter(fn (Purchase $purchase) => $purchase->remaining_amount > 0)
            ->values();

        return view('admin.cash-accounts.create-transaction', compact(
            'accounts',
            'coaIncomeAccounts',
            'coaExpenseAccounts',
            'outstandingPurchases'
        ));
    }

    /**
     * Store a new transaction
     */
    public function storeTransaction(StoreCashTransactionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id() ?? 1; // TODO: Replace with actual auth

            $transactionCategory = $data['transaction_category'] ?? 'general';

            if ($transactionCategory === 'purchase_payment') {
                return $this->storePurchasePaymentFromCashTransaction($data);
            }

            if ($transactionCategory === 'book_transfer') {
                return $this->storeBookTransferFromCashTransaction($data);
            }

            $rows = $data['rows'] ?? [];
            unset($data['rows']);

            $transactions = $this->cashAccountService->recordTransactionsBulk($data, $rows);
            $count = count($transactions);
            $firstNumber = $transactions[0]->transaction_number ?? null;

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', $count === 1
                    ? 'Transaksi berhasil dicatat! Nomor: ' . $firstNumber
                    : 'Transaksi berhasil dicatat! Total: ' . $count . ' baris.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Catat pembayaran hutang purchase dari menu Transaksi Kas/Bank.
     */
    protected function storePurchasePaymentFromCashTransaction(array $data)
    {
        $purchase = Purchase::findOrFail((int) $data['purchase_id']);

        $transaction = $this->cashAccountService->recordPurchasePayment($purchase, [
            'cash_account_id' => (int) $data['cash_account_id'],
            'amount' => (float) $data['purchase_amount'],
            'transaction_date' => $data['transaction_date'],
            'notes' => $data['purchase_notes'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        return redirect()
            ->route('admin.cash-transactions.index')
            ->with('success', 'Pembayaran hutang purchase berhasil dicatat. Nomor: ' . $transaction->transaction_number);
    }

    /**
     * Catat pindah buku (transfer antar rekening) dari menu Transaksi Kas/Bank.
     */
    protected function storeBookTransferFromCashTransaction(array $data)
    {
        DB::beginTransaction();

        try {
            $fromAccount = CashAccount::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['cash_account_id']);

            $toAccount = CashAccount::query()
                ->lockForUpdate()
                ->findOrFail((int) $data['transfer_to_cash_account_id']);

            if ($fromAccount->id === $toAccount->id) {
                throw new \Exception('Rekening tujuan harus berbeda dengan rekening sumber.');
            }

            $amount = (float) $data['transfer_amount'];
            $transferClearingCoaId = $this->cashAccountService->resolveTransferClearingCoaAccountId();

            if ((float) $fromAccount->current_balance < $amount) {
                throw new \Exception(
                    'Saldo rekening sumber tidak cukup! Saldo tersedia: Rp ' .
                    number_format((float) $fromAccount->current_balance, 0, ',', '.')
                );
            }

            $transfer = BankTransfer::create([
                'transfer_number' => $this->generateTransferNumber($data['transaction_date']),
                'from_cash_account_id' => $fromAccount->id,
                'to_cash_account_id' => $toAccount->id,
                'transfer_date' => $data['transaction_date'],
                'amount' => $amount,
                'description' => $data['transfer_description'],
                'notes' => $data['transfer_notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            CashTransaction::create([
                'transaction_number' => $this->cashAccountService->generateTransactionNumber($fromAccount, 'out', $data['transaction_date']),
                'cash_account_id' => $fromAccount->id,
                'coa_account_id' => $transferClearingCoaId,
                'type' => 'out',
                'transaction_date' => $data['transaction_date'],
                'amount' => $amount,
                'balance_before' => $fromAccount->current_balance,
                'balance_after' => $fromAccount->current_balance - $amount,
                'description' => 'Transfer ke ' . $toAccount->name . ' - ' . $data['transfer_description'],
                'reference_type' => 'bank_transfer',
                'reference_id' => $transfer->id,
                'notes' => $data['transfer_notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            $fromAccount->current_balance -= $amount;
            $fromAccount->save();

            CashTransaction::create([
                'transaction_number' => $this->cashAccountService->generateTransactionNumber($toAccount, 'in', $data['transaction_date']),
                'cash_account_id' => $toAccount->id,
                'coa_account_id' => $transferClearingCoaId,
                'type' => 'in',
                'transaction_date' => $data['transaction_date'],
                'amount' => $amount,
                'balance_before' => $toAccount->current_balance,
                'balance_after' => $toAccount->current_balance + $amount,
                'description' => 'Transfer dari ' . $fromAccount->name . ' - ' . $data['transfer_description'],
                'reference_type' => 'bank_transfer',
                'reference_id' => $transfer->id,
                'notes' => $data['transfer_notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            $toAccount->current_balance += $amount;
            $toAccount->save();

            DB::commit();

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', 'Pindah buku berhasil dicatat! Nomor transfer: ' . $transfer->transfer_number);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function generateTransferNumber(?string $transferDate = null): string
    {
        $date = Carbon::parse($transferDate ?? now())->format('Ymd');
        $prefix = 'TRF-' . $date . '-';

        $lastTransfer = BankTransfer::where('transfer_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Show transaction details
     */
    public function showTransaction(CashTransaction $cashTransaction)
    {
        return view('admin.cash-accounts.show-transaction', compact('cashTransaction'));
    }

    /**
     * Show edit form for transaction
     */
    public function editTransaction(CashTransaction $cashTransaction)
    {
        $accounts = CashAccount::active()->orderBy('name')->get();
        // Determine if it's income or expense based on type
        if ($cashTransaction->type === 'in') {
            $coaAccounts = CoaAccount::active()->income()->orderBy('code')->get();
        } else {
            $coaAccounts = CoaAccount::active()
                ->expense()
                ->orderByRaw('CASE WHEN code = ? THEN 1 ELSE 0 END', ['6-135'])
                ->orderBy('code')
                ->get();
        }

        return view('admin.cash-accounts.edit-transaction', compact('cashTransaction', 'accounts', 'coaAccounts'));
    }

    /**
     * Update transaction
     */
    public function updateTransaction(Request $request, CashTransaction $cashTransaction)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string|max:255',
            'coa_account_id' => 'nullable|exists:coa_accounts,id',
        ]);

        try {
            $this->cashAccountService->updateTransaction($cashTransaction, $request->only([
                'transaction_date',
                'amount',
                'description',
                'coa_account_id',
                'notes'
            ]));

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', 'Transaksi berhasil diperbarui dan saldo telah dihitung ulang.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete transaction
     */
    public function destroyTransaction(CashTransaction $cashTransaction)
    {
        try {
            $this->cashAccountService->deleteTransaction($cashTransaction);

            return back()->with('success', 'Transaksi berhasil dihapus dan saldo telah dihitung ulang.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Print voucher
     */
    public function printVoucher(CashTransaction $cashTransaction)
    {
        return view('admin.cash-accounts.print-voucher', compact('cashTransaction'));
    }

    /**
     * Show mutation report for an account
     */
    public function mutationReport(Request $request, CashAccount $cashAccount)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->endOfMonth()->format('Y-m-d'));

        $report = $this->cashAccountService->getMutationReport($cashAccount->id, $dateFrom, $dateTo);

        return view('admin.cash-accounts.mutation-report', $report);
    }
}
