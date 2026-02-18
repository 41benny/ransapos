<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransfer;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Services\CashAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankTransferController extends Controller
{
    protected CashAccountService $cashAccountService;

    public function __construct(CashAccountService $cashAccountService)
    {
        $this->cashAccountService = $cashAccountService;
    }

    /**
     * Display a listing of bank transfers
     */
    public function index(Request $request)
    {
        $query = BankTransfer::with(['fromAccount.outlet', 'toAccount.outlet', 'creator'])
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('transfer_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transfer_date', '<=', $request->date_to);
        }

        // Filter by from/to account
        if ($request->filled('from_account_id')) {
            $query->where('from_cash_account_id', $request->from_account_id);
        }
        if ($request->filled('to_account_id')) {
            $query->where('to_cash_account_id', $request->to_account_id);
        }

        $transfers = $query->paginate(20);
        $accounts = CashAccount::with('outlet')->active()->orderBy('name')->get();

        return view('admin.bank-transfers.index', compact('transfers', 'accounts'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        // Alur transfer disatukan ke form Kas & Bank > Tambah Transaksi (kategori Pindah Buku).
        return redirect()
            ->route('admin.cash-transactions.create', ['transaction_category' => 'book_transfer']);
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'transfer_date' => 'required|date',
            'from_cash_account_id' => 'required|exists:cash_accounts,id',
            'to_cash_account_id' => 'required|exists:cash_accounts,id|different:from_cash_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string',
        ], [
            'to_cash_account_id.different' => 'Rekening tujuan harus berbeda dengan rekening sumber!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $fromAccount = CashAccount::findOrFail($request->from_cash_account_id);
            $toAccount = CashAccount::findOrFail($request->to_cash_account_id);
            $transferClearingCoaId = $this->cashAccountService->resolveTransferClearingCoaAccountId();

            // Validate balance
            if ($fromAccount->current_balance < $request->amount) {
                return back()
                    ->with('error', 'Saldo rekening sumber tidak cukup! Saldo tersedia: Rp ' . number_format($fromAccount->current_balance, 0, ',', '.'))
                    ->withInput();
            }

            // Generate transfer number
            $transferNumber = $this->generateTransferNumber();

            // Create bank transfer record
            $transfer = BankTransfer::create([
                'transfer_number' => $transferNumber,
                'from_cash_account_id' => $request->from_cash_account_id,
                'to_cash_account_id' => $request->to_cash_account_id,
                'transfer_date' => $request->transfer_date,
                'amount' => $request->amount,
                'description' => $request->description,
                'notes' => $request->notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Create OUT transaction for from account
            $fromTransactionNumber = $this->cashAccountService->generateTransactionNumber($fromAccount, 'out', $request->transfer_date);
            CashTransaction::create([
                'transaction_number' => $fromTransactionNumber,
                'voucher_number' => $fromTransactionNumber,
                'cash_account_id' => $fromAccount->id,
                'coa_account_id' => $transferClearingCoaId,
                'type' => 'out',
                'transaction_date' => $request->transfer_date,
                'amount' => $request->amount,
                'balance_before' => $fromAccount->current_balance,
                'balance_after' => $fromAccount->current_balance - $request->amount,
                'description' => 'Transfer ke ' . $toAccount->name . ' - ' . $request->description,
                'reference_type' => 'bank_transfer',
                'reference_id' => $transfer->id,
                'notes' => $request->notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Update from account balance
            $fromAccount->current_balance -= $request->amount;
            $fromAccount->save();

            // Create IN transaction for to account
            $toTransactionNumber = $this->cashAccountService->generateTransactionNumber($toAccount, 'in', $request->transfer_date);
            CashTransaction::create([
                'transaction_number' => $toTransactionNumber,
                'voucher_number' => $toTransactionNumber,
                'cash_account_id' => $toAccount->id,
                'coa_account_id' => $transferClearingCoaId,
                'type' => 'in',
                'transaction_date' => $request->transfer_date,
                'amount' => $request->amount,
                'balance_before' => $toAccount->current_balance,
                'balance_after' => $toAccount->current_balance + $request->amount,
                'description' => 'Transfer dari ' . $fromAccount->name . ' - ' . $request->description,
                'reference_type' => 'bank_transfer',
                'reference_id' => $transfer->id,
                'notes' => $request->notes,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Update to account balance
            $toAccount->current_balance += $request->amount;
            $toAccount->save();

            DB::commit();

            return redirect()
                ->route('admin.bank-transfers.show', $transfer)
                ->with('success', 'Transfer berhasil dibuat! Nomor: ' . $transferNumber);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat transfer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer
     */
    public function show(BankTransfer $bankTransfer)
    {
        $bankTransfer->load([
            'fromAccount.outlet',
            'toAccount.outlet',
            'creator',
            'transactions' => function ($query) {
                $query->orderBy('type', 'desc'); // out first, then in
            }
        ]);

        return view('admin.bank-transfers.show', compact('bankTransfer'));
    }

    /**
     * Generate unique transfer number
     */
    private function generateTransferNumber(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'TRF-' . $today . '-';

        $lastTransfer = BankTransfer::where('transfer_number', 'like', $prefix . '%')
            ->orderBy('transfer_number', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastNumber = (int) substr($lastTransfer->transfer_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
