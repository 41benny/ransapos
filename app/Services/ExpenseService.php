<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExpenseService
{
    protected CashAccountService $cashAccountService;

    public function __construct(CashAccountService $cashAccountService)
    {
        $this->cashAccountService = $cashAccountService;
    }

    /**
     * Generate expense number
     * Format: EXP-OUTLET-YYYYMMDD-XXX
     */
    public function generateExpenseNumber(int $outletId, string $date): string
    {
        $dateStr = Carbon::parse($date)->format('Ymd');
        $prefix = "EXP-" . str_pad($outletId, 3, '0', STR_PAD_LEFT) . "-{$dateStr}-";

        $lastExpense = Expense::where('expense_number', 'like', $prefix . '%')
            ->orderBy('expense_number', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = (int) substr($lastExpense->expense_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Create expense
     */
    public function createExpense(array $data): Expense
    {
        DB::beginTransaction();
        try {
            // Generate expense number
            $data['expense_number'] = $this->generateExpenseNumber(
                $data['outlet_id'],
                $data['expense_date']
            );

            // Set creator
            $data['created_by'] = Auth::id();

            // Handle file upload
            if (isset($data['attachment']) && $data['attachment']) {
                $file = $data['attachment'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('expenses', $filename, 'public');
                $data['attachment_path'] = $path;
            }

            // Create expense
            $expense = Expense::create($data);

            // If status is approved and paid, create cash transaction
            if ($data['status'] === 'paid') {
                if (empty($data['cash_account_id'])) {
                    throw new \Exception('Cash account harus dipilih untuk expense dengan status paid.');
                }

                // Paid implies approved (minimal audit fields)
                if (empty($expense->approved_at)) {
                    $expense->forceFill([
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                    ])->save();
                }

                $this->createCashTransaction($expense);
            }

            DB::commit();
            return $expense;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update expense
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        DB::beginTransaction();
        try {
            // Handle file upload
            if (isset($data['attachment']) && $data['attachment']) {
                // Delete old file
                if ($expense->attachment_path && Storage::disk('public')->exists($expense->attachment_path)) {
                    Storage::disk('public')->delete($expense->attachment_path);
                }

                $file = $data['attachment'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('expenses', $filename, 'public');
                $data['attachment_path'] = $path;
            }

            $expense->update($data);

            DB::commit();
            return $expense->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve expense
     */
    public function approveExpense(Expense $expense, string $notes = null): Expense
    {
        if (!$expense->canBeApproved()) {
            throw new \Exception('Expense cannot be approved. Current status: ' . $expense->status);
        }

        DB::beginTransaction();
        try {
            $expense->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            DB::commit();
            return $expense->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject expense
     */
    public function rejectExpense(Expense $expense, string $reason): Expense
    {
        if (!$expense->canBeRejected()) {
            throw new \Exception('Expense cannot be rejected. Current status: ' . $expense->status);
        }

        DB::beginTransaction();
        try {
            $expense->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'approval_notes' => $reason,
            ]);

            DB::commit();
            return $expense->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mark expense as paid and create cash transaction
     */
    public function payExpense(Expense $expense, int $cashAccountId): Expense
    {
        if (!$expense->canBePaid()) {
            throw new \Exception('Expense cannot be paid. Current status: ' . $expense->status);
        }

        DB::beginTransaction();
        try {
            // Update expense status
            $expense->update([
                'status' => 'paid',
                'cash_account_id' => $cashAccountId,
            ]);

            // Create cash transaction
            $this->createCashTransaction($expense);

            DB::commit();
            return $expense->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create cash transaction for paid expense
     */
    protected function createCashTransaction(Expense $expense): void
    {
        if (!$expense->cash_account_id) {
            throw new \Exception('Cash account belum di-set untuk expense ini.');
        }

        $existing = CashTransaction::whereIn('reference_type', ['expense', Expense::class])
            ->where('reference_id', $expense->id)
            ->first();

        if ($existing) {
            return;
        }

        $expense->loadMissing('category');

        $coaAccountId = $expense->category?->coa_account_id;
        if (!$coaAccountId) {
            throw new \Exception('Expense category belum terhubung ke COA account. Set COA di master Expense Category.');
        }

        $this->cashAccountService->recordTransaction([
            'cash_account_id' => $expense->cash_account_id,
            'coa_account_id' => $coaAccountId,
            'type' => 'out',
            'transaction_date' => $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'amount' => (float) $expense->amount,
            'description' => "Expense: {$expense->description} ({$expense->expense_number})",
            'reference_type' => 'expense',
            'reference_id' => $expense->id,
            'notes' => $expense->reference_no ? "Reference: {$expense->reference_no}" : null,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Delete expense
     */
    public function deleteExpense(Expense $expense): bool
    {
        DB::beginTransaction();
        try {
            // Delete attachment if exists
            if ($expense->attachment_path && Storage::disk('public')->exists($expense->attachment_path)) {
                Storage::disk('public')->delete($expense->attachment_path);
            }

            // If expense was paid, reverse cash transaction
            if ($expense->isPaid() && $expense->cash_account_id) {
                $this->reverseCashTransaction($expense);
            }

            $expense->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse cash transaction when deleting paid expense
     */
    protected function reverseCashTransaction(Expense $expense): void
    {
        // Find and delete cash transaction
        $transaction = CashTransaction::whereIn('reference_type', ['expense', Expense::class])
            ->where('reference_id', $expense->id)
            ->first();

        if ($transaction) {
            // Revert cash account balance (note: ini delete-based, bukan reversal transaction)
            $cashAccount = $transaction->cashAccount()->lockForUpdate()->first();
            if ($cashAccount) {
                $cashAccount->increment('current_balance', (float) $transaction->amount);
            }

            $transaction->delete();
        }
    }

    /**
     * Get expense statistics
     */
    public function getStatistics(int $outletId = null, string $startDate = null, string $endDate = null): array
    {
        $query = Expense::query();

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        return [
            'total_expenses' => $query->sum('amount'),
            'pending_count' => (clone $query)->where('status', 'pending')->count(),
            'approved_count' => (clone $query)->where('status', 'approved')->count(),
            'paid_count' => (clone $query)->where('status', 'paid')->count(),
            'rejected_count' => (clone $query)->where('status', 'rejected')->count(),
            'total_paid' => (clone $query)->where('status', 'paid')->sum('amount'),
            'total_pending' => (clone $query)->where('status', 'pending')->sum('amount'),
        ];
    }

    /**
     * Get expense by category report
     */
    public function getExpenseByCategory(int $outletId = null, string $startDate = null, string $endDate = null): array
    {
        $query = Expense::with('category')
            ->select('expense_category_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_count'))
            ->where('status', '!=', 'rejected')
            ->groupBy('expense_category_id');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        return $query->get()->map(function ($item) {
            return [
                'category' => $item->category ? $item->category->full_name : 'Uncategorized',
                'total_amount' => $item->total_amount,
                'total_count' => $item->total_count,
            ];
        })->toArray();
    }

    /**
     * Get expense trend by month
     */
    public function getExpenseTrend(int $outletId = null, int $months = 12): array
    {
        $query = Expense::select(
            DB::raw('DATE_FORMAT(expense_date, "%Y-%m") as month'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(*) as total_count')
        )
        ->where('status', '!=', 'rejected')
        ->where('expense_date', '>=', now()->subMonths($months))
        ->groupBy('month')
        ->orderBy('month');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        return $query->get()->toArray();
    }
}
