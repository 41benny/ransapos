<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Outlet;
use App\Models\CashAccount;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $query = Expense::with(['outlet', 'category', 'creator', 'approver']);

        // Filter by outlet
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get filter options
        $outlets = Outlet::active()->orderBy('name')->get();
        $categories = ExpenseCategory::active()->orderBy('name')->get();

        // Get statistics
        $statistics = $this->expenseService->getStatistics(
            $request->outlet_id,
            $request->start_date,
            $request->end_date
        );

        return view('admin.expenses.index', compact('expenses', 'outlets', 'categories', 'statistics'));
    }

    /**
     * Show the form for creating a new expense
     */
    public function create()
    {
        $outlets = Outlet::active()->orderBy('name')->get();
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $cashAccounts = CashAccount::active()->orderBy('name')->get();

        return view('admin.expenses.create', compact('outlets', 'categories', 'cashAccounts'));
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'status' => 'required|in:pending,approved,paid',
        ]);

        try {
            $expense = $this->expenseService->createExpense($validated);

            return redirect()->route('admin.expenses.show', $expense)
                ->with('success', 'Expense created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create expense: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified expense
     */
    public function show(Expense $expense)
    {
        $expense->load(['outlet', 'category', 'cashAccount', 'creator', 'approver']);

        return view('admin.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(Expense $expense)
    {
        // Only pending expenses can be edited
        if (!$expense->isPending()) {
            return back()->with('error', 'Only pending expenses can be edited.');
        }

        $outlets = Outlet::active()->orderBy('name')->get();
        $categories = ExpenseCategory::active()->orderBy('name')->get();
        $cashAccounts = CashAccount::active()->orderBy('name')->get();

        return view('admin.expenses.edit', compact('expense', 'outlets', 'categories', 'cashAccounts'));
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        // Only pending expenses can be updated
        if (!$expense->isPending()) {
            return back()->with('error', 'Only pending expenses can be edited.');
        }

        $validated = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'cash_account_id' => 'nullable|exists:cash_accounts,id',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        try {
            $expense = $this->expenseService->updateExpense($expense, $validated);

            return redirect()->route('admin.expenses.show', $expense)
                ->with('success', 'Expense updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to update expense: ' . $e->getMessage());
        }
    }

    /**
     * Approve expense
     */
    public function approve(Request $request, Expense $expense)
    {
        $request->validate([
            'approval_notes' => 'nullable|string',
        ]);

        try {
            $this->expenseService->approveExpense($expense, $request->approval_notes);

            return back()->with('success', 'Expense approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve expense: ' . $e->getMessage());
        }
    }

    /**
     * Reject expense
     */
    public function reject(Request $request, Expense $expense)
    {
        $request->validate([
            'approval_notes' => 'required|string',
        ]);

        try {
            $this->expenseService->rejectExpense($expense, $request->approval_notes);

            return back()->with('success', 'Expense rejected successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject expense: ' . $e->getMessage());
        }
    }

    /**
     * Pay expense
     */
    public function pay(Request $request, Expense $expense)
    {
        $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
        ]);

        try {
            $this->expenseService->payExpense($expense, $request->cash_account_id);

            return back()->with('success', 'Expense marked as paid successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to mark expense as paid: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        try {
            $this->expenseService->deleteExpense($expense);

            return redirect()->route('admin.expenses.index')
                ->with('success', 'Expense deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Show expense reports
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $outletId = $request->input('outlet_id');

        // Get statistics
        $statistics = $this->expenseService->getStatistics($outletId, $startDate, $endDate);

        // Get expense by category
        $expenseByCategory = $this->expenseService->getExpenseByCategory($outletId, $startDate, $endDate);

        // Get expense trend
        $expenseTrend = $this->expenseService->getExpenseTrend($outletId, 12);

        $outlets = Outlet::active()->orderBy('name')->get();

        return view('admin.expenses.reports', compact(
            'statistics',
            'expenseByCategory',
            'expenseTrend',
            'outlets',
            'startDate',
            'endDate',
            'outletId'
        ));
    }
}
