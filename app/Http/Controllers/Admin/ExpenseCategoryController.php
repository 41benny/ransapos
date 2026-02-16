<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\CoaAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories
     */
    public function index()
    {
        $categories = ExpenseCategory::with(['parent', 'coaAccount'])
            ->orderBy('order')
            ->orderBy('name')
            ->get();

        // Group by parent
        $parentCategories = $categories->whereNull('parent_id');

        return view('admin.expense-categories.index', compact('categories', 'parentCategories'));
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $parentCategories = ExpenseCategory::whereNull('parent_id')
            ->active()
            ->orderBy('name')
            ->get();

        $coaAccounts = CoaAccount::where('type', 'expense')
            ->active()
            ->orderByRaw('CASE WHEN code = ? THEN 1 ELSE 0 END', ['6-135'])
            ->orderBy('code')
            ->get();

        return view('admin.expense-categories.create', compact('parentCategories', 'coaAccounts'));
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:expense_categories,code',
            'parent_id' => 'nullable|exists:expense_categories,id',
            'coa_account_id' => 'nullable|exists:coa_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        ExpenseCategory::create($validated);

        return redirect()->route('admin.expense-categories.index')
            ->with('success', 'Expense category created successfully.');
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        $parentCategories = ExpenseCategory::whereNull('parent_id')
            ->where('id', '!=', $expenseCategory->id)
            ->active()
            ->orderBy('name')
            ->get();

        $coaAccounts = CoaAccount::where('type', 'expense')
            ->active()
            ->orderByRaw('CASE WHEN code = ? THEN 1 ELSE 0 END', ['6-135'])
            ->orderBy('code')
            ->get();

        return view('admin.expense-categories.edit', compact('expenseCategory', 'parentCategories', 'coaAccounts'));
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:expense_categories,code,' . $expenseCategory->id,
            'parent_id' => 'nullable|exists:expense_categories,id',
            'coa_account_id' => 'nullable|exists:coa_accounts,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $expenseCategory->update($validated);

        return redirect()->route('admin.expense-categories.index')
            ->with('success', 'Expense category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Check if category has expenses
        if ($expenseCategory->expenses()->count() > 0) {
            return back()->with('error', 'Cannot delete category with existing expenses.');
        }

        // Check if category has children
        if ($expenseCategory->hasChildren()) {
            return back()->with('error', 'Cannot delete category with sub-categories.');
        }

        $expenseCategory->delete();

        return redirect()->route('admin.expense-categories.index')
            ->with('success', 'Expense category deleted successfully.');
    }
}
