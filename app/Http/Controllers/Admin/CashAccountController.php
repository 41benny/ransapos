<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashAccountRequest;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Requests\UpdateCashAccountRequest;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Services\CashAccountService;
use Illuminate\Http\Request;

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
        return view('admin.cash-accounts.edit', compact('cashAccount'));
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
        $filters = $request->only(['cash_account_id', 'type', 'date_from', 'date_to', 'reference_type']);
        
        $transactions = $this->cashAccountService->getTransactions($filters);
        $accounts = CashAccount::active()->orderBy('name')->get();

        return view('admin.cash-accounts.transactions', compact('transactions', 'accounts', 'filters'));
    }

    /**
     * Show form to create new transaction
     */
    public function createTransaction()
    {
        $accounts = CashAccount::active()->orderBy('name')->get();
        return view('admin.cash-accounts.create-transaction', compact('accounts'));
    }

    /**
     * Store a new transaction
     */
    public function storeTransaction(StoreCashTransactionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['created_by'] = auth()->id() ?? 1; // TODO: Replace with actual auth

            $transaction = $this->cashAccountService->recordTransaction($data);

            return redirect()
                ->route('admin.cash-accounts.transactions')
                ->with('success', 'Transaksi berhasil dicatat! Nomor: ' . $transaction->transaction_number);
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
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
