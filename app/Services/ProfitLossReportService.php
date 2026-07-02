<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\Sale;
use App\Models\StockMutation;
use App\Models\CashTransaction;
use Illuminate\Support\Facades\DB;
use App\Support\MerchantCommission;

class ProfitLossReportService
{
    /**
     * Generate Profit & Loss Report
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @param array<int>|int|null $outletIds
     * @return array
     */
    public function generate(string $dateFrom, string $dateTo, array|int|null $outletIds = null): array
    {
        $outletIds = $this->normalizeOutletIds($outletIds);

        // 1. PENDAPATAN (Revenue from Sales)
        $revenueQuery = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo]);
        
        if (!empty($outletIds)) {
            $revenueQuery->whereIn('outlet_id', $outletIds);
        }
        
        $totalRevenue = (float) (clone $revenueQuery)->sum('total_amount');
        $merchantSales = (float) (clone $revenueQuery)
            ->whereIn('sales_type', MerchantCommission::SALES_TYPES)
            ->sum('total_amount');
        $merchantCommission = $merchantSales * MerchantCommission::RATE;

        // 2. HPP / COGS (Cost of Goods Sold from Stock Mutations)
        // out = HPP penjualan, in (sale_cancellation) = reversal HPP
        $cogsQuery = StockMutation::query()
            ->whereIn('reference_type', ['sale', 'sale_cancellation'])
            ->whereBetween('mutation_date', [$dateFrom, $dateTo]);
        
        if (!empty($outletIds)) {
            $cogsQuery->whereIn('outlet_id', $outletIds);
        }
        
        $totalCogs = (float) $cogsQuery
            ->selectRaw("SUM(CASE WHEN mutation_type = 'out' THEN total_cost ELSE -total_cost END) as total_cogs")
            ->value('total_cogs');

        // 3. LABA KOTOR (Gross Profit)
        $grossProfitBeforeMerchant = $totalRevenue - $totalCogs;
        $grossProfit = $grossProfitBeforeMerchant - $merchantCommission;

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

        if (!empty($outletIds)) {
            $expenseQuery->whereIn('cash_accounts.outlet_id', $outletIds);
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
                'id' => $expense->coa_account_id,
                'code' => $expense->code,
                'name' => $expense->name,
                'amount' => $amount,
            ];

            $expensesByGroup[$group]['total'] += $amount;
            $totalExpenses += $amount;
        }

        $groupOrderMap = [
            'BIAYA OPERASIONAL' => 10,
            'BIAYA ADMINISTRASI' => 20,
            'LAINNYA' => 90,
        ];

        uasort($expensesByGroup, function (array $left, array $right) use ($groupOrderMap) {
            $leftOrder = $groupOrderMap[$left['group_name']] ?? 50;
            $rightOrder = $groupOrderMap[$right['group_name']] ?? 50;

            if ($leftOrder === $rightOrder) {
                return strcmp($left['group_name'], $right['group_name']);
            }

            return $leftOrder <=> $rightOrder;
        });

        // 5. LABA BERSIH (Net Profit)
        $netProfit = $grossProfit - $totalExpenses;

        // 6. Revenue Trend (Last 5 Months)
        $trends = [];
        for ($i = 4; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $start = $monthDate->copy()->startOfMonth()->format('Y-m-d');
            $end = $monthDate->copy()->endOfMonth()->format('Y-m-d');
            
            $trendQuery = Sale::query()
                ->where('status', 'completed')
                ->whereBetween('sale_date', [$start, $end]);
            
            if (!empty($outletIds)) {
                $trendQuery->whereIn('outlet_id', $outletIds);
            }
            
            $trends[] = [
                'month' => $monthDate->format('M'),
                'amount' => (float) $trendQuery->sum('total_amount'),
            ];
        }

        $outletComparison = $this->buildOutletComparison($dateFrom, $dateTo, $outletIds, $totalRevenue, $totalCogs, $grossProfit);

        // Return report data
        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_ids' => $outletIds,
            'outlet_id' => count($outletIds) === 1 ? $outletIds[0] : null,
            
            // Revenue
            'total_revenue' => $totalRevenue,
            'merchant_sales' => $merchantSales,
            'merchant_commission_rate' => MerchantCommission::RATE,
            'merchant_commission' => $merchantCommission,
            
            // COGS
            'total_cogs' => $totalCogs,
            
            // Gross Profit
            'gross_profit' => $grossProfit,
            'gross_profit_before_merchant' => $grossProfitBeforeMerchant,
            'gross_profit_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0,
            
            // Operating Expenses
            'expenses_by_group' => array_values($expensesByGroup),
            'total_expenses' => $totalExpenses,
            
            // Net Profit
            'net_profit' => $netProfit,
            'net_profit_margin' => $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0,

            // Charts
            'revenue_trends' => $trends,
            'expense_chart' => collect($expensesByGroup)->map(fn($g) => [
                'label' => $g['group_name'],
                'value' => $g['total']
            ])->values()->toArray(),

            // Comparison
            'outlet_comparison' => $outletComparison,
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

    /**
     * @param array<int>|int|null $outletIds
     * @return array<int>
     */
    private function normalizeOutletIds(array|int|null $outletIds): array
    {
        if (is_int($outletIds)) {
            return $outletIds > 0 ? [$outletIds] : [];
        }

        if (!is_array($outletIds)) {
            return [];
        }

        return collect($outletIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => is_numeric($id) ? (int) $id : null)
            ->filter(fn ($id) => is_int($id) && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int> $outletIds
     * @return array<string, mixed>
     */
    private function buildOutletComparison(
        string $dateFrom,
        string $dateTo,
        array $outletIds,
        float|int $totalRevenue,
        float|int $totalCogs,
        float|int $grossProfit,
    ): array {
        if (empty($outletIds)) {
            return [
                'outlets' => [],
                'totals' => [
                    'revenue' => (float) $totalRevenue,
                    'cogs' => (float) $totalCogs,
                    'gross_profit' => (float) $grossProfit,
                    'gross_margin' => (float) ($totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0),
                ],
            ];
        }

        $outlets = Outlet::query()
            ->whereIn('id', $outletIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $revenueByOutlet = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->whereIn('outlet_id', $outletIds)
            ->select('outlet_id', DB::raw('SUM(total_amount) as total_revenue'))
            ->groupBy('outlet_id')
            ->pluck('total_revenue', 'outlet_id');

        $merchantSalesByOutlet = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->whereIn('outlet_id', $outletIds)
            ->whereIn('sales_type', MerchantCommission::SALES_TYPES)
            ->select('outlet_id', DB::raw('SUM(total_amount) as merchant_sales'))
            ->groupBy('outlet_id')
            ->pluck('merchant_sales', 'outlet_id');

        $cogsByOutlet = StockMutation::query()
            ->whereIn('reference_type', ['sale', 'sale_cancellation'])
            ->whereBetween('mutation_date', [$dateFrom, $dateTo])
            ->whereIn('outlet_id', $outletIds)
            ->select(
                'outlet_id',
                DB::raw("SUM(CASE WHEN mutation_type = 'out' THEN total_cost ELSE -total_cost END) as total_cogs")
            )
            ->groupBy('outlet_id')
            ->pluck('total_cogs', 'outlet_id');

        $comparisonRows = collect($outletIds)
            ->map(function (int $outletId) use ($outlets, $revenueByOutlet, $merchantSalesByOutlet, $cogsByOutlet) {
                $revenue = (float) ($revenueByOutlet[$outletId] ?? 0);
                $cogs = (float) ($cogsByOutlet[$outletId] ?? 0);
                $merchantSales = (float) ($merchantSalesByOutlet[$outletId] ?? 0);
                $merchantCommission = $merchantSales * MerchantCommission::RATE;
                $grossProfit = $revenue - $cogs - $merchantCommission;

                return [
                    'id' => $outletId,
                    'name' => $outlets[$outletId]->name ?? ('Outlet #' . $outletId),
                    'revenue' => $revenue,
                    'cogs' => $cogs,
                    'merchant_sales' => $merchantSales,
                    'merchant_commission' => $merchantCommission,
                    'gross_profit' => $grossProfit,
                    'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0,
                ];
            })
            ->values()
            ->all();

        return [
            'outlets' => $comparisonRows,
            'totals' => [
                'revenue' => (float) $totalRevenue,
                'cogs' => (float) $totalCogs,
                'gross_profit' => (float) $grossProfit,
                'gross_margin' => (float) ($totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0),
            ],
        ];
    }
}
