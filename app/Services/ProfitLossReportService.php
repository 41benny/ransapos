<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\StockMutation;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
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
        $revenueQuery = Sale::whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        
        if ($outletId) {
            $revenueQuery->where('outlet_id', $outletId);
        }
        
        $totalRevenue = $revenueQuery->sum('grand_total');

        // 2. HPP / COGS (Cost of Goods Sold from Stock Mutations)
        $cogsQuery = StockMutation::where('mutation_type', 'out')
            ->whereBetween('mutation_date', [$dateFrom, $dateTo]);
        
        if ($outletId) {
            $cogsQuery->where('outlet_id', $outletId);
        }
        
        $totalCogs = $cogsQuery->sum('total_cost');

        // 3. LABA KOTOR (Gross Profit)
        $grossProfit = $totalRevenue - $totalCogs;

        // 4. BIAYA OPERASIONAL (Operating Expenses by COA)
        $expenseQuery = CashTransaction::where('type', 'out')
            ->whereNotNull('coa_account_id')
            ->whereBetween('transaction_date', [$dateFrom, $dateTo]);
        
        $expensesByAccount = $expenseQuery
            ->select('coa_account_id', DB::raw('SUM(amount) as total'))
            ->groupBy('coa_account_id')
            ->with('coaAccount')
            ->get();

        // Group by COA group
        $expensesByGroup = [];
        $totalExpenses = 0;

        foreach ($expensesByAccount as $expense) {
            if ($expense->coaAccount) {
                $group = $expense->coaAccount->group;
                
                if (!isset($expensesByGroup[$group])) {
                    $expensesByGroup[$group] = [
                        'group_name' => $group,
                        'accounts' => [],
                        'total' => 0,
                    ];
                }

                $expensesByGroup[$group]['accounts'][] = [
                    'code' => $expense->coaAccount->code,
                    'name' => $expense->coaAccount->name,
                    'amount' => $expense->total,
                ];

                $expensesByGroup[$group]['total'] += $expense->total;
                $totalExpenses += $expense->total;
            }
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

