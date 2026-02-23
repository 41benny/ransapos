<?php

namespace App\Services;

use App\Models\CoaAccount;
use Illuminate\Support\Facades\DB;

class BalanceSheetReportService
{
    /**
     * Generate Neraca (Balance Sheet) menggunakan data COA + transaksi kas.
     *
     * @return array<string, mixed>
     */
    public function generate(string $dateFrom, string $dateTo, ?int $outletId = null): array
    {
        $coaAccounts = CoaAccount::query()
            ->where('is_active', true)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'group']);

        $movementQuery = DB::table('cash_transactions')
            ->join('coa_accounts', 'cash_transactions.coa_account_id', '=', 'coa_accounts.id')
            ->leftJoin('cash_accounts', 'cash_transactions.cash_account_id', '=', 'cash_accounts.id')
            ->whereIn('coa_accounts.type', ['asset', 'liability', 'equity'])
            ->whereDate('cash_transactions.transaction_date', '<=', $dateTo);

        if (!empty($outletId)) {
            $movementQuery->where('cash_accounts.outlet_id', $outletId);
        }

        // Untuk Lawan Kas (Counterpart COA):
        // Jika COA = Asset (misal Persediaan, Piutang, Aset Tetap): Uang Keluar (out) -> Aset Bertambah, Uang Masuk (in) -> Aset Berkurang
        // Jika COA = Liability/Equity: Uang Masuk (in) -> Liability Bertambah, Uang Keluar (out) -> Liability Berkurang
        $movements = $movementQuery
            ->select(
                'cash_transactions.coa_account_id',
                DB::raw("SUM(
                    CASE 
                        WHEN coa_accounts.type = 'asset' THEN (CASE WHEN cash_transactions.type = 'out' THEN cash_transactions.amount ELSE -cash_transactions.amount END)
                        ELSE (CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE -cash_transactions.amount END)
                    END
                ) as balance_to_date"),
                DB::raw("SUM(
                    CASE WHEN cash_transactions.transaction_date >= '{$dateFrom}' THEN 
                        CASE 
                            WHEN coa_accounts.type = 'asset' THEN (CASE WHEN cash_transactions.type = 'out' THEN cash_transactions.amount ELSE -cash_transactions.amount END)
                            ELSE (CASE WHEN cash_transactions.type = 'in' THEN cash_transactions.amount ELSE -cash_transactions.amount END)
                        END
                    ELSE 0 END
                ) as movement_in_period")
            )
            ->groupBy('cash_transactions.coa_account_id', 'coa_accounts.type')
            ->get()
            ->keyBy('coa_account_id');

        // MENGHITUNG SALDO REAL KAS/BANK (Penting! Karena Kas/Bank tidak pernah jadi counterpart dirinya sendiri)
        $cashAccountsStatsQuery = DB::table('cash_accounts')
            ->leftJoinSub(
                DB::table('cash_transactions')
                    ->select(
                        'cash_account_id',
                        DB::raw("SUM(CASE WHEN type = 'in' THEN amount ELSE -amount END) as balance_to_date"),
                        DB::raw("SUM(CASE WHEN transaction_date >= '" . addslashes($dateFrom) . "' THEN (CASE WHEN type = 'in' THEN amount ELSE -amount END) ELSE 0 END) as movement_in_period")
                    )
                    ->whereDate('transaction_date', '<=', $dateTo)
                    ->groupBy('cash_account_id'),
                'mov',
                'cash_accounts.id',
                '=',
                'mov.cash_account_id'
            )
            ->where('cash_accounts.is_active', true);

        if (!empty($outletId)) {
            $cashAccountsStatsQuery->where('cash_accounts.outlet_id', $outletId);
        }

        $cashAccountsStats = $cashAccountsStatsQuery
            ->select(
                'cash_accounts.type',
                DB::raw('SUM(cash_accounts.opening_balance + COALESCE(mov.balance_to_date, 0)) as total_balance'),
                DB::raw('SUM(COALESCE(mov.movement_in_period, 0)) as total_movement')
            )
            ->groupBy('cash_accounts.type')
            ->get()
            ->keyBy('type');

        // Inject ke ID COA 1-100 (Kas) dan 1-110 (Bank)
        $kasCoa = $coaAccounts->where('code', '1-100')->first();
        if ($kasCoa) {
            $stats = $cashAccountsStats->get('cash');
            $movements->put($kasCoa->id, (object)[
                'balance_to_date' => $stats->total_balance ?? 0,
                'movement_in_period' => $stats->total_movement ?? 0,
            ]);
        }
        $bankCoa = $coaAccounts->where('code', '1-110')->first();
        if ($bankCoa) {
            $stats = $cashAccountsStats->get('bank');
            $movements->put($bankCoa->id, (object)[
                'balance_to_date' => $stats->total_balance ?? 0,
                'movement_in_period' => $stats->total_movement ?? 0,
            ]);
        }

        $sections = [
            'asset' => ['label' => 'Aset', 'rows' => [], 'total' => 0.0],
            'liability' => ['label' => 'Kewajiban', 'rows' => [], 'total' => 0.0],
            'equity' => ['label' => 'Ekuitas', 'rows' => [], 'total' => 0.0],
        ];

        foreach ($coaAccounts as $account) {
            $movement = $movements->get($account->id);
            $balance = (float) ($movement->balance_to_date ?? 0);
            $periodMutation = (float) ($movement->movement_in_period ?? 0);

            $sections[$account->type]['rows'][] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'group' => $account->group,
                'balance' => $balance,
                'movement_in_period' => $periodMutation,
            ];

            $sections[$account->type]['total'] += $balance;
        }

        $assetTotal = $sections['asset']['total'];
        $liabilityTotal = $sections['liability']['total'];
        $equityTotal = $sections['equity']['total'];
        $liabilityEquityTotal = $liabilityTotal + $equityTotal;
        $difference = $assetTotal - $liabilityEquityTotal;

        $cashBankControl = 0;
        foreach ($cashAccountsStats as $stat) {
            $cashBankControl += (float) ($stat->total_balance ?? 0);
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_id' => $outletId,
            'sections' => $sections,
            'totals' => [
                'asset' => $assetTotal,
                'liability' => $liabilityTotal,
                'equity' => $equityTotal,
                'liability_equity' => $liabilityEquityTotal,
                'difference' => $difference,
                'is_balanced' => abs($difference) < 0.01,
            ],
            'controls' => [
                'cash_bank_as_of' => $cashBankControl,
                'asset_vs_cash_bank_gap' => $assetTotal - $cashBankControl,
            ],
        ];
    }
}
