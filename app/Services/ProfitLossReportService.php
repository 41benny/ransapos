<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\StockMutation;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;

class ProfitLossReportService
{
    /**
     * Generate Profit & Loss Report
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $outletId
     * @return array
     */
    public function generate(string $dateFrom, string $dateTo, ?int $outletId = null): array
    {
        // 1. PENDAPATAN (Revenue from Sales)
        $revenueQuery = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);
        
        if ($outletId) {
            $revenueQuery->where('outlet_id', $outletId);
        }
        
        $totalRevenue = $revenueQuery->sum('total_amount');

        // 2. HPP / COGS (Cost of Goods Sold from Stock Mutations)
        // out = HPP penjualan, in (sale_cancellation) = reversal HPP
        $cogsQuery = StockMutation::query()
            ->whereIn('reference_type', ['sale', 'sale_cancellation'])
            ->whereBetween('mutation_date', [$dateFrom, $dateTo]);
        
        if ($outletId) {
            $cogsQuery->where('outlet_id', $outletId);
        }
        
        $totalCogs = (float) $cogsQuery
            ->selectRaw("SUM(CASE WHEN mutation_type = 'out' THEN total_cost ELSE -total_cost END) as total_cogs")
            ->value('total_cogs');

        // 3. LABA KOTOR (Gross Profit)
        $grossProfit = $totalRevenue - $totalCogs;

        // 4. BIAYA OPERASIONAL (Operating Expenses by COA)
        // Exclude HPP agar tidak double-count dengan totalCogs.
        $expenseQuery = CashTransaction::query()
            ->join('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
            ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
            ->where('cash_transactions.type', 'out')
            ->whereNotNull('cash_transactions.coa_account_id')
            ->where('coa_accounts.type', 'expense')
            ->where('coa_accounts.group', '!=', 'HPP')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo]);

        if ($outletId) {
            $expenseQuery->where('cash_accounts.outlet_id', $outletId);
        }
        
        $expensesByAccount = $expenseQuery
            ->select(
                'cash_transactions.coa_account_id',
                'coa_accounts.code',
                'coa_accounts.name',
                'coa_accounts.group',
                DB::raw('SUM(cash_transactions.amount) as total')
            )
            ->groupBy(
                'cash_transactions.coa_account_id',
                'coa_accounts.code',
                'coa_accounts.name',
                'coa_accounts.group'
            )
            ->orderBy('coa_accounts.group')
            ->orderBy('coa_accounts.code')
            ->get();

        // Group by COA group
        $expensesByGroup = [];
        $totalExpenses = 0;

        foreach ($expensesByAccount as $expense) {
            $group = $expense->group ?? 'LAINNYA';
            
            if (!isset($expensesByGroup[$group])) {
                $expensesByGroup[$group] = [
                    'group_name' => $group,
                    'accounts' => [],
                    'total' => 0,
                ];
            }

            $amount = (float) $expense->total;
            $expensesByGroup[$group]['accounts'][] = [
                'code' => $expense->code,
                'name' => $expense->name,
                'amount' => $amount,
            ];

            $expensesByGroup[$group]['total'] += $amount;
            $totalExpenses += $amount;
        }

        // 5. LABA BERSIH (Net Profit)
        $netProfit = $grossProfit - $totalExpenses;

        // Return report data
        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_id' => $outletId,
            
            // Revenue
            'total_revenue' => $totalRevenue,
            
            // COGS
            'total_cogs' => $totalCogs,
            
            // Gross Profit
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
            
            // Operating Expenses
            'expenses_by_group' => array_values($expensesByGroup),
            'total_expenses' => $totalExpenses,
            
            // Net Profit
            'net_profit' => $netProfit,
            'net_profit_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,
        ];
    }

    /**
     * Get summary statistics
     */
    public function getSummary(string $dateFrom, string $dateTo): array
    {
        $report = $this->generate($dateFrom, $dateTo);
        
        return [
            'total_revenue' => $report['total_revenue'],
            'gross_profit' => $report['gross_profit'],
            'net_profit' => $report['net_profit'],
            'profit_margin' => $report['net_profit_margin'],
        ];
    }
}

