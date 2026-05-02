<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryMutationJournalExportService
{
    /**
     * @param array<int, int> $outletIds
     * @return array<int, array<string, mixed>>
     */
    public function buildMonthlyRows(string $month, array $outletIds = []): array
    {
        [$monthStart, $monthEnd] = $this->resolveMonthPeriod($month);
        $mappings = $this->mappingCollection();

        if ($mappings->isEmpty()) {
            throw new InvalidArgumentException('Mapping jurnal persediaan belum dikonfigurasi.');
        }

        $mutationRows = $this->queryTransferMutationSummary($monthStart, $monthEnd, $outletIds);
        if ($mutationRows->isEmpty()) {
            return [];
        }

        $rows = [];

        foreach ($mutationRows as $mutationRow) {
            $fromMapping = $this->findMappingForOutlet(
                (string) $mutationRow->from_outlet_name,
                (string) ($mutationRow->from_outlet_code ?? ''),
                $mappings
            );
            $toMapping = $this->findMappingForOutlet(
                (string) $mutationRow->to_outlet_name,
                (string) ($mutationRow->to_outlet_code ?? ''),
                $mappings
            );

            if ($fromMapping === null || $toMapping === null) {
                continue;
            }

            $amount = abs((float) $mutationRow->total_cost);
            if ($amount <= 0) {
                continue;
            }

            $fromInventoryAccount = [
                'code' => (string) $fromMapping['inventory_coa_code'],
                'name' => (string) $fromMapping['inventory_coa_name'],
                'alias' => (string) $fromMapping['alias_voucher'],
            ];
            $toInventoryAccount = [
                'code' => (string) $toMapping['inventory_coa_code'],
                'name' => (string) $toMapping['inventory_coa_name'],
                'alias' => (string) $toMapping['alias_voucher'],
            ];

            [$debitAccount, $creditAccount, $label] = $this->resolveMutationAccounts(
                (string) $mutationRow->mutation_type,
                (float) $mutationRow->quantity,
                $fromInventoryAccount,
                $toInventoryAccount
            );

            $voucherAlias = $fromInventoryAccount['alias'] . $toInventoryAccount['alias'];
            $voucher = 'MUT' . strtoupper($voucherAlias) . $monthEnd->format('my');
            $journalName = 'MUTASI PERSEDIAAN ' . strtoupper($voucherAlias);
            $journalNote = sprintf(
                '%s %s sd %s',
                $label,
                $monthStart->format('d M Y'),
                $monthEnd->format('d M Y')
            );

            $rows[] = $this->makeRow($debitAccount, $voucher, $monthEnd, $amount, 'D', $journalName, $journalNote);
            $rows[] = $this->makeRow($creditAccount, $voucher, $monthEnd, $amount, 'K', $journalName, $journalNote);
        }

        return $rows;
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryTransferMutationSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $query = DB::table('stock_mutations')
            ->join('stock_transfers', 'stock_transfers.id', '=', 'stock_mutations.reference_id')
            ->join('outlets as from_outlets', 'from_outlets.id', '=', 'stock_transfers.from_outlet_id')
            ->join('outlets as to_outlets', 'to_outlets.id', '=', 'stock_transfers.to_outlet_id')
            ->where('stock_mutations.reference_type', 'stock_transfer')
            ->whereIn('stock_mutations.mutation_type', ['transfer_out', 'adjustment'])
            ->whereDate('stock_mutations.mutation_date', '>=', $monthStart->toDateString())
            ->whereDate('stock_mutations.mutation_date', '<=', $monthEnd->toDateString())
            ->select(
                'stock_transfers.from_outlet_id',
                'from_outlets.name as from_outlet_name',
                'from_outlets.code as from_outlet_code',
                'stock_transfers.to_outlet_id',
                'to_outlets.name as to_outlet_name',
                'to_outlets.code as to_outlet_code',
                'stock_mutations.mutation_type'
            )
            ->selectRaw('CASE WHEN stock_mutations.quantity >= 0 THEN 1 ELSE -1 END as quantity_sign')
            ->selectRaw('COALESCE(SUM(stock_mutations.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(ABS(stock_mutations.total_cost)), 0) as total_cost')
            ->groupBy(
                'stock_transfers.from_outlet_id',
                'from_outlets.name',
                'from_outlets.code',
                'stock_transfers.to_outlet_id',
                'to_outlets.name',
                'to_outlets.code',
                'stock_mutations.mutation_type',
                DB::raw('CASE WHEN stock_mutations.quantity >= 0 THEN 1 ELSE -1 END')
            )
            ->havingRaw('COALESCE(SUM(ABS(stock_mutations.total_cost)), 0) > 0')
            ->orderBy('from_outlets.name')
            ->orderBy('to_outlets.name')
            ->orderBy('stock_mutations.mutation_type');

        if (!empty($outletIds)) {
            $query->where(function ($inner) use ($outletIds) {
                $inner->whereIn('stock_transfers.from_outlet_id', $outletIds)
                    ->orWhereIn('stock_transfers.to_outlet_id', $outletIds);
            });
        }

        return $query->get();
    }

    /**
     * @param array{code: string, name: string, alias: string} $fromInventoryAccount
     * @param array{code: string, name: string, alias: string} $toInventoryAccount
     * @return array{0: array{code: string, name: string}, 1: array{code: string, name: string}, 2: string}
     */
    private function resolveMutationAccounts(string $mutationType, float $quantity, array $fromInventoryAccount, array $toInventoryAccount): array
    {
        $fromInventory = [
            'code' => $fromInventoryAccount['code'],
            'name' => $fromInventoryAccount['name'],
        ];
        $toInventory = [
            'code' => $toInventoryAccount['code'],
            'name' => $toInventoryAccount['name'],
        ];

        if ($mutationType === 'transfer_out') {
            return [$toInventory, $fromInventory, 'Transfer Persediaan'];
        }

        if ($quantity >= 0) {
            return [$fromInventory, $toInventory, 'Adjustment Transfer Persediaan'];
        }

        return [$toInventory, $fromInventory, 'Adjustment Transfer Persediaan'];
    }

    /**
     * @param array{code: string, name: string} $account
     * @return array<string, mixed>
     */
    private function makeRow(array $account, string $voucher, Carbon $date, float $amount, string $mutation, string $journalName, string $journalNote): array
    {
        return [
            'STATUS' => 'MUTASI_PERSEDIAAN',
            'NO_AKUN' => (int) $account['code'],
            '_VOUCHER' => $voucher,
            'J_TANGGAL' => $date->format('d/m/Y'),
            'J_JUMLAH' => $amount,
            'D' => $mutation === 'D' ? $amount : null,
            'K' => $mutation === 'K' ? $amount : null,
            'J_MUTASI' => $mutation,
            'J_NAMA' => $journalName,
            'J_KET1' => $journalNote,
            'KET 2' => $account['name'],
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $mappings
     * @return array<string, mixed>|null
     */
    private function findMappingForOutlet(string $outletName, string $outletCode, Collection $mappings): ?array
    {
        $normalizedName = $this->normalizeText($outletName);
        $normalizedCode = $this->normalizeText($outletCode);

        foreach ($mappings as $mapping) {
            $candidates = collect((array) ($mapping['match'] ?? []))
                ->map(fn ($value) => $this->normalizeText((string) $value))
                ->filter()
                ->values();

            foreach ($candidates as $candidate) {
                if ($candidate === $normalizedName || $candidate === $normalizedCode) {
                    return $mapping;
                }

                if (strlen($candidate) >= 4 && (str_contains($normalizedName, $candidate) || str_contains($normalizedCode, $candidate))) {
                    return $mapping;
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveMonthPeriod(string $month): array
    {
        $month = trim($month);
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new InvalidArgumentException('Parameter month harus berformat YYYY-MM.');
        }

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return [$start, (clone $start)->endOfMonth()];
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower(trim($value));

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function mappingCollection(): Collection
    {
        return collect((array) config('sales_journal.hpp.outlet_mappings', []));
    }
}
