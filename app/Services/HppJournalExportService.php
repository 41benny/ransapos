<?php

namespace App\Services;

use App\Models\CoaAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HppJournalExportService
{
    /**
     * @param Collection<int, \App\Models\Outlet> $outlets
     * @return Collection<int, \App\Models\Outlet>
     */
    public function filterMappedOutlets(Collection $outlets): Collection
    {
        $mappings = $this->mappingCollection();

        return $outlets
            ->filter(function ($outlet) use ($mappings) {
                $mapping = $this->findMappingForOutlet(
                    (string) ($outlet->name ?? ''),
                    (string) ($outlet->code ?? ''),
                    $mappings
                );

                return $mapping !== null;
            })
            ->values();
    }

    /**
     * @param Collection<int, \App\Models\Outlet> $outlets
     * @return array{mapped: Collection<int, \App\Models\Outlet>, unmapped: Collection<int, \App\Models\Outlet>}
     */
    public function partitionOutletsByMapping(Collection $outlets): array
    {
        $mappings = $this->mappingCollection();

        $partitioned = $outlets->partition(function ($outlet) use ($mappings) {
            return $this->findMappingForOutlet(
                (string) ($outlet->name ?? ''),
                (string) ($outlet->code ?? ''),
                $mappings
            ) !== null;
        });

        return [
            'mapped' => $partitioned[0]->values(),
            'unmapped' => $partitioned[1]->values(),
        ];
    }

    /**
     * @param array<int, int> $outletIds
     * @return array<int, array<string, mixed>>
     */
    public function buildMonthlyRows(string $month, array $outletIds = []): array
    {
        [$monthStart, $monthEnd] = $this->resolveMonthPeriod($month);
        $mappings = $this->mappingCollection();

        if ($mappings->isEmpty()) {
            throw new InvalidArgumentException('Mapping jurnal HPP belum dikonfigurasi.');
        }

        $summaryRows = $this->queryOutletCogsSummary($monthStart, $monthEnd, $outletIds);
        if ($summaryRows->isEmpty()) {
            return [];
        }

        $resolved = $this->resolveOutletMappings($summaryRows, $mappings);
        if ($resolved->isEmpty()) {
            return [];
        }

        $coaByCode = $this->loadCoaAccountsByCode($resolved->pluck('hpp_coa_code')
            ->merge($resolved->pluck('inventory_coa_code'))
            ->unique()
            ->values()
            ->all());

        $rows = [];
        foreach ($resolved as $item) {
            $amount = (float) $item['total_cogs'];
            if ($amount <= 0) {
                continue;
            }

            $hppCode = (string) $item['hpp_coa_code'];
            $inventoryCode = (string) $item['inventory_coa_code'];
            $alias = (string) $item['alias_voucher'];

            $hppAccountName = $coaByCode[$hppCode]->name ?? (string) $item['hpp_coa_name'];
            $inventoryAccountName = $coaByCode[$inventoryCode]->name ?? (string) $item['inventory_coa_name'];

            $voucher = 'HPP' . strtoupper($alias) . $monthEnd->format('my');
            $journalName = 'HPP ' . strtoupper($alias);
            $journalNote = sprintf(
                'HPP %s %s sd %s',
                strtoupper($alias),
                $monthStart->format('d M Y'),
                $monthEnd->format('d M Y')
            );

            $rows[] = [
                'STATUS' => 'HPP',
                'NO_AKUN' => (int) $hppCode,
                '_VOUCHER' => $voucher,
                'J_TANGGAL' => $monthEnd->format('d/m/Y'),
                'J_JUMLAH' => $amount,
                'D' => $amount,
                'K' => null,
                'J_MUTASI' => 'D',
                'J_NAMA' => $journalName,
                'J_KET1' => $journalNote,
                'KET 2' => $hppAccountName,
            ];

            $rows[] = [
                'STATUS' => 'HPP',
                'NO_AKUN' => (int) $inventoryCode,
                '_VOUCHER' => $voucher,
                'J_TANGGAL' => $monthEnd->format('d/m/Y'),
                'J_JUMLAH' => $amount,
                'D' => null,
                'K' => $amount,
                'J_MUTASI' => 'K',
                'J_NAMA' => $journalName,
                'J_KET1' => $journalNote,
                'KET 2' => $inventoryAccountName,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryOutletCogsSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $query = DB::table('sales')
            ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
            ->join('outlets', 'outlets.id', '=', 'sales.outlet_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select(
                'sales.outlet_id',
                'outlets.name as outlet_name',
                'outlets.code as outlet_code'
            )
            ->selectRaw('COALESCE(SUM(sale_items.cogs), 0) as total_cogs')
            ->groupBy('sales.outlet_id', 'outlets.name', 'outlets.code')
            ->orderBy('outlets.name');

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        return $query->get();
    }

    /**
     * @param Collection<int, object> $summaryRows
     * @param Collection<int, array<string, mixed>> $mappings
     * @return Collection<int, array<string, mixed>>
     */
    private function resolveOutletMappings(Collection $summaryRows, Collection $mappings): Collection
    {
        return $summaryRows->map(function ($row) use ($mappings) {
            $mapping = $this->findMappingForOutlet(
                (string) ($row->outlet_name ?? ''),
                (string) ($row->outlet_code ?? ''),
                $mappings
            );

            if ($mapping === null) {
                return null;
            }

            return [
                'outlet_id' => (int) $row->outlet_id,
                'outlet_name' => (string) $row->outlet_name,
                'outlet_code' => (string) ($row->outlet_code ?? ''),
                'total_cogs' => (float) $row->total_cogs,
                'alias_voucher' => (string) ($mapping['alias_voucher'] ?? ''),
                'hpp_coa_code' => (string) ($mapping['hpp_coa_code'] ?? ''),
                'hpp_coa_name' => (string) ($mapping['hpp_coa_name'] ?? ''),
                'inventory_coa_code' => (string) ($mapping['inventory_coa_code'] ?? ''),
                'inventory_coa_name' => (string) ($mapping['inventory_coa_name'] ?? ''),
            ];
        })->filter()->values();
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
     * @param array<int, string> $codes
     * @return array<string, CoaAccount>
     */
    private function loadCoaAccountsByCode(array $codes): array
    {
        $cleanCodes = collect($codes)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        $coaByCode = CoaAccount::query()
            ->whereIn('code', $cleanCodes->all())
            ->get()
            ->keyBy('code')
            ->all();

        return $coaByCode;
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
