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
                ->orderBy('id', 'desc')
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
        $accountStats = $this->cashAccountService->getPerAccountTotals($filters);
        
        $accounts = CashAccount::active()->orderBy('name')->get();
        $totalBalance = $accounts->sum('current_balance');
        
        $outlets = \App\Models\Outlet::active()->orderBy('name')->get();
        $coaAccounts = CoaAccount::active()->orderBy('code')->get();
        $coaGroups = CoaAccount::query()->where('is_active', true)->distinct()->orderBy('group')->pluck('group');

        return view('admin.cash-accounts.transactions', compact(
            'transactions', 
            'accounts', 
            'accountStats',
            'totalBalance',
            'outlets', 
            'coaAccounts', 
            'coaGroups', 
            'filters', 
            'totals'
        ));
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
            $voucherNumber = $transactions[0]->voucher_number ?? $transactions[0]->transaction_number ?? null;

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', $count === 1
                    ? 'Transaksi berhasil dicatat! Nomor voucher: ' . $voucherNumber
                    : 'Transaksi berhasil dicatat! Nomor voucher: ' . $voucherNumber . ' (' . $count . ' baris).');
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
            'description' => $data['purchase_description'] ?? null,
            'notes' => $data['purchase_notes'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        return redirect()
            ->route('admin.cash-transactions.index')
            ->with('success', 'Pembayaran hutang purchase berhasil dicatat. Nomor voucher: ' . ($transaction->voucher_number ?? $transaction->transaction_number));
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

            $fromTransactionNumber = $this->cashAccountService->generateTransactionNumber($fromAccount, 'out', $data['transaction_date']);
            CashTransaction::create([
                'transaction_number' => $fromTransactionNumber,
                'voucher_number' => $fromTransactionNumber,
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

            $toTransactionNumber = $this->cashAccountService->generateTransactionNumber($toAccount, 'in', $data['transaction_date']);
            CashTransaction::create([
                'transaction_number' => $toTransactionNumber,
                'voucher_number' => $toTransactionNumber,
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

        $transactionCategory = 'general';
        if ($cashTransaction->reference_type === 'purchase') {
            $transactionCategory = 'purchase_payment';
        } elseif ($cashTransaction->reference_type === 'bank_transfer') {
            $transactionCategory = 'book_transfer';
        }

        $outstandingPurchases = collect();
        if ($transactionCategory === 'purchase_payment') {
            $outstandingPurchases = Purchase::query()
                ->with(['supplier', 'outlet'])
                ->where('status', 'received')
            ->withSum('cashTransactions as total_paid', 'amount')
            ->orderByDesc('purchase_date')
            ->get()
            ->map(function (Purchase $purchase) use ($cashTransaction) {
                $totalPaid = (float) ($purchase->total_paid ?? 0);
                
                // Jangan hitung jumlah dari transaksi ini saat mencari sisa hutang sebelumnya
                if ($cashTransaction->reference_id == $purchase->id) {
                    $totalPaid -= (float) $cashTransaction->amount;
                }
                
                $purchase->remaining_amount = max(0, (float) $purchase->total_amount - $totalPaid);
                return $purchase;
            })
            ->filter(fn (Purchase $purchase) => $purchase->remaining_amount > 0 || $cashTransaction->reference_id == $purchase->id)
            ->values();
        }

        // Ambil semua transaksi terkait menggunakan teknik yang sama dengan di printVoucher
        $voucherNumber = (string) ($cashTransaction->voucher_number ?: $cashTransaction->transaction_number);

        $relatedTransactions = CashTransaction::query()
            ->with(['coaAccount', 'cashAccount'])
            ->where('voucher_number', $voucherNumber)
            ->where('cash_account_id', $cashTransaction->cash_account_id)
            ->where('type', $cashTransaction->type)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($relatedTransactions->count() <= 1) {
            $legacyVoucherTransactions = $this->resolveLegacyVoucherTransactions($cashTransaction);
            if ($legacyVoucherTransactions->count() > $relatedTransactions->count()) {
                $relatedTransactions = $legacyVoucherTransactions;
            }
        }

        if ($relatedTransactions->isEmpty()) {
            $relatedTransactions = collect([$cashTransaction]);
        }

        return view('admin.cash-accounts.edit-transaction', compact('cashTransaction', 'accounts', 'coaAccounts', 'relatedTransactions', 'transactionCategory', 'outstandingPurchases'));
    }

    /**
     * Update transaction
     */
    public function updateTransaction(Request $request, CashTransaction $cashTransaction)
    {
        $transactionCategory = $request->input('transaction_category', 'general');

        if ($transactionCategory === 'purchase_payment') {
            return $this->updatePurchasePaymentFromCashTransaction($request, $cashTransaction);
        }

        $request->validate([
            'transaction_date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.amount' => 'required|numeric|min:0',
            'rows.*.description' => 'required|string|max:255',
            'rows.*.coa_account_id' => 'required|exists:coa_accounts,id',
            'deleted_row_ids' => 'nullable|array',
            'deleted_row_ids.*' => 'exists:cash_transactions,id',
        ]);
        
        try {
            DB::beginTransaction();

            $date = $request->input('transaction_date');
            $notes = $request->input('notes');
            $rows = $request->input('rows');
            $deletedIds = $request->input('deleted_row_ids', []);

            // 1. Hapus transaksi yang dihapus user dari tabel
            if (!empty($deletedIds)) {
                foreach ($deletedIds as $id) {
                    $txnToDelete = CashTransaction::find($id);
                    if ($txnToDelete && $txnToDelete->cash_account_id == $cashTransaction->cash_account_id) {
                        $this->cashAccountService->deleteTransaction($txnToDelete);
                    }
                }
            }

            // 2. Update atau Buat Transaksi Baru
            $voucherNumber = $cashTransaction->voucher_number ?: $cashTransaction->transaction_number;
            $batchReferenceId = count($rows) > 1 && $cashTransaction->reference_type === 'general_batch'
                ? $cashTransaction->reference_id
                : (count($rows) > 1 ? mt_rand(100000000, 999999999) : null);
            $referenceType = count($rows) > 1 ? 'general_batch' : null;

            $account = $cashTransaction->cashAccount;
            $type = $cashTransaction->type;
            
            // Kita cari transaksi yang paling awal untuk recalculate
            $recalcFromDate = $cashTransaction->transaction_date->format('Y-m-d') < $date
                ? $cashTransaction->transaction_date->format('Y-m-d')
                : $date;

            foreach ($rows as $index => $row) {
                $rowId = $row['id'] ?? null;
                $amount = (float) $row['amount'];
                
                if ($rowId) {
                    // Update existing
                    $txnToUpdate = CashTransaction::find($rowId);
                    if ($txnToUpdate) {
                        $txnToUpdate->update([
                            'transaction_date' => $date,
                            'amount' => $amount,
                            'description' => $row['description'],
                            'coa_account_id' => $row['coa_account_id'],
                            'notes' => $notes,
                            'reference_type' => $referenceType,
                            'reference_id' => $batchReferenceId,
                            'voucher_number' => $voucherNumber,
                        ]);
                    }
                } else {
                    // Create new row
                    $data = [
                        'cash_account_id' => $account->id,
                        'type' => $type,
                        'transaction_date' => $date,
                        'amount' => $amount,
                        'description' => $row['description'],
                        'coa_account_id' => $row['coa_account_id'],
                        'notes' => $notes,
                        'reference_type' => $referenceType,
                        'reference_id' => $batchReferenceId,
                        'voucher_number' => $voucherNumber,
                        'created_by' => auth()->id() ?? $cashTransaction->created_by,
                    ];
                    
                    // Bypass service record validation and rely on recalculation
                    // Similar to applyTransaction without recalculation yet
                    $data['transaction_number'] = $this->cashAccountService->generateTransactionNumber(
                        $account,
                        $type,
                        $date
                    );
                    $data['balance_before'] = 0; // will be recalculated
                    $data['balance_after'] = 0;  // will be recalculated
                    
                    CashTransaction::create($data);
                }
            }

            // 3. Recalculate Balances once for all changes
            $this->cashAccountService->recalculateBalances($account, $recalcFromDate);

            DB::commit();

            return redirect()
                ->route('admin.cash-transactions.index', $request->query())
                ->with('success', 'Transaksi berhasil diperbarui dan saldo telah dihitung ulang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update pembayaran hutang purchase dari menu Transaksi Kas/Bank.
     */
    protected function updatePurchasePaymentFromCashTransaction(Request $request, CashTransaction $cashTransaction)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'purchase_amount' => 'required|numeric|min:0',
            'purchase_id' => 'required|exists:purchases,id',
            'purchase_description' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $purchase = Purchase::findOrFail((int) $request->input('purchase_id'));
            $date = $request->input('transaction_date');
            $notes = $request->input('purchase_notes');
            $amount = (float) $request->input('purchase_amount');
            $description = $request->input('purchase_description');

            $oldPurchaseId = $cashTransaction->reference_id;
            
            // Recalculate balances
            $recalcFromDate = $cashTransaction->transaction_date->format('Y-m-d') < $date
                ? $cashTransaction->transaction_date->format('Y-m-d')
                : $date;

            // Update transaction
            $cashTransaction->update([
                'transaction_date' => $date,
                'amount' => $amount,
                'description' => $description,
                'notes' => $notes,
                'reference_id' => $purchase->id,
            ]);

            $this->cashAccountService->recalculateBalances($cashTransaction->cashAccount, $recalcFromDate);

            // Update new purchase payment status
            $this->cashAccountService->updatePurchasePaymentStatus($purchase);

            // If purchase changed, update old purchase payment status
            if ($oldPurchaseId && $oldPurchaseId != $purchase->id) {
                $oldPurchase = Purchase::find($oldPurchaseId);
                if ($oldPurchase) {
                    $this->cashAccountService->updatePurchasePaymentStatus($oldPurchase);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.cash-transactions.index', $request->query())
                ->with('success', 'Transaksi pelunasan hutang berhasil diperbarui dan saldo telah dihitung ulang.');
        } catch (\Exception $e) {
            DB::rollBack();
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
        $voucherNumber = (string) ($cashTransaction->voucher_number ?: $cashTransaction->transaction_number);

        $voucherTransactions = CashTransaction::query()
            ->with(['coaAccount', 'cashAccount'])
            ->where('voucher_number', $voucherNumber)
            ->where('cash_account_id', $cashTransaction->cash_account_id)
            ->where('type', $cashTransaction->type)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Fallback legacy: data lama sebelum voucher_number dibakukan
        // bisa punya nomor baris berbeda walau sebenarnya 1 submit multi-baris.
        if ($voucherTransactions->count() <= 1) {
            $legacyVoucherTransactions = $this->resolveLegacyVoucherTransactions($cashTransaction);
            if ($legacyVoucherTransactions->count() > $voucherTransactions->count()) {
                $voucherTransactions = $legacyVoucherTransactions;
                $voucherNumber = (string) (
                    $voucherTransactions->pluck('voucher_number')->filter()->sort()->first()
                    ?? $voucherTransactions->pluck('transaction_number')->filter()->sort()->first()
                    ?? $voucherNumber
                );
            }
        }

        if ($voucherTransactions->isEmpty()) {
            $voucherTransactions = collect([$cashTransaction->load(['coaAccount', 'cashAccount'])]);
        }

        $totalAmount = (float) $voucherTransactions->sum('amount');

        return view('admin.cash-accounts.print-voucher', compact(
            'cashTransaction',
            'voucherTransactions',
            'voucherNumber',
            'totalAmount'
        ));
    }

    protected function resolveLegacyVoucherTransactions(CashTransaction $cashTransaction)
    {
        if (! $cashTransaction->created_at || ! $cashTransaction->transaction_date) {
            return collect();
        }

        $createdAtStart = $cashTransaction->created_at->copy()->startOfSecond();
        $createdAtEnd = $cashTransaction->created_at->copy()->endOfSecond();
        $notes = trim((string) ($cashTransaction->notes ?? ''));

        return CashTransaction::query()
            ->with(['coaAccount', 'cashAccount'])
            ->where('cash_account_id', $cashTransaction->cash_account_id)
            ->where('type', $cashTransaction->type)
            ->whereDate('transaction_date', $cashTransaction->transaction_date->format('Y-m-d'))
            ->where('created_by', $cashTransaction->created_by)
            ->whereNull('reference_type')
            ->whereNull('reference_id')
            ->whereBetween('created_at', [$createdAtStart, $createdAtEnd])
            ->whereRaw('COALESCE(TRIM(notes), \'\') = ?', [$notes])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
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
