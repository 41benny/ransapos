<?php

namespace App\Services;

use App\Models\CashSessionExpense;
use App\Models\CashTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosExpenseReportingService
{
    private function policy(string $key): array
    {
        // An existing config cache may predate this file. Keep the exclusions safe
        // until deployment clears/rebuilds that cache.
        $policy = config('pos_expense_reporting') ?? require config_path('pos_expense_reporting.php');

        return $policy[$key] ?? [];
    }

    /**
     * Only the canonical ledger row posted at closing contributes additional HPP.
     * Never add the shift subledger, stock purchases, AP settlements or cash-ins.
     */
    public function additionalCogsQuery(string $from, string $to, ?array $outletIds = null): Builder
    {
        $excludedCodes = $this->policy('operating_coa_codes');
        $excludedNames = $this->policy('operating_name_patterns');

        $query = $this->expenseQuery($from, $to, $outletIds)
            ->where('cash_transactions.reference_type', 'pos_drawer_expense')
            ->whereNotIn('coa_accounts.code', $excludedCodes)
            ->whereExists(function ($source) {
                $source->selectRaw('1')->from('cash_session_expenses as pos_source')
                    ->whereColumn('pos_source.id', 'cash_transactions.reference_id')
                    ->whereColumn('pos_source.cash_transaction_id', 'cash_transactions.id')
                    ->whereColumn('pos_source.coa_account_id', 'cash_transactions.coa_account_id')
                    ->whereColumn('pos_source.cash_session_id', 'cash_transactions.cash_session_id')
                    ->where('pos_source.status', CashSessionExpense::STATUS_POSTED)
                    ->where('pos_source.direction', CashSessionExpense::DIRECTION_OUT);
            });

        foreach ($excludedNames as $pattern) {
            $query->whereRaw('LOWER(coa_accounts.name) NOT LIKE ?', [strtolower($pattern)]);
        }

        return $query;
    }

    public function additionalCogsTotal(string $from, string $to, ?array $outletIds = null): float
    {
        return (float) $this->additionalCogsQuery($from, $to, $outletIds)->sum('cash_transactions.amount');
    }

    public function additionalCogsByOutlet(string $from, string $to, ?array $outletIds = null): Collection
    {
        return $this->additionalCogsQuery($from, $to, $outletIds)
            ->groupBy('cash_accounts.outlet_id')
            ->selectRaw('cash_accounts.outlet_id, SUM(cash_transactions.amount) as additional_cogs')
            ->pluck('additional_cogs', 'outlet_id');
    }

    public function operatingExpensesQuery(string $from, string $to, ?array $outletIds = null): Builder
    {
        return $this->expenseQuery($from, $to, $outletIds)
            ->whereNotIn('cash_transactions.id', $this->additionalCogsQuery($from, $to, $outletIds)
                ->select('cash_transactions.id'));
    }

    private function expenseQuery(string $from, string $to, ?array $outletIds): Builder
    {
        $query = CashTransaction::query()
            ->join('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
            ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
            ->where('cash_transactions.type', 'out')
            ->where('coa_accounts.type', 'expense')
            // These accounts already belong to stock/product HPP, not POS overhead.
            ->where('coa_accounts.group', '!=', 'HPP')
            ->where(function ($scope) {
                $scope->whereNull('cash_transactions.reference_type')
                    ->orWhere('cash_transactions.reference_type', '!=', 'pos_drawer_expense')
                    ->orWhere(function ($pos) {
                        $pos->whereNotIn('coa_accounts.code', array_merge(
                            $this->policy('stock_coa_codes'),
                            $this->policy('included_in_product_cost_coa_codes'),
                        ));
                        foreach ($this->policy('stock_name_patterns') as $pattern) {
                            $pos->whereRaw('LOWER(coa_accounts.name) NOT LIKE ?', [strtolower($pattern)]);
                        }
                    });
            })
            ->whereBetween('cash_transactions.transaction_date', [$from, $to.' 23:59:59'])
            ->when(! empty($outletIds), fn ($q) => $q->whereIn('cash_accounts.outlet_id', $outletIds));

        return $query;
    }
}
