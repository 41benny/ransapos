<?php

namespace App\Services;

use App\Models\CoaAccount;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseJournalExportService
{
    /**
     * @param array<int, int> $outletIds
     * @return array<int, array<string, mixed>>
     */
    public function buildMonthlyRows(string $month, array $outletIds = []): array
    {
        [$monthStart, $monthEnd] = $this->resolveMonthPeriod($month);
        $outletMappings = $this->outletMappingCollection();

        if ($outletMappings->isEmpty()) {
            throw new InvalidArgumentException('Mapping jurnal persediaan outlet belum dikonfigurasi.');
        }

        $summaryRows = $this->queryPurchaseSummary($monthStart, $monthEnd, $outletIds);
        if ($summaryRows->isEmpty()) {
            return [];
        }

        $resolvedRows = $summaryRows
            ->map(function ($row) use ($outletMappings) {
                $outletMapping = $this->findMappingForOutlet(
                    (string) $row->outlet_name,
                    (string) ($row->outlet_code ?? ''),
                    $outletMappings
                );

                if ($outletMapping === null) {
                    return null;
                }

                $apAccount = $this->resolveApAccount(
                    (string) ($row->supplier_name ?? ''),
                    (string) ($row->supplier_code ?? '')
                );

                return [
                    'outlet_id' => (int) $row->outlet_id,
                    'supplier_id' => (int) $row->supplier_id,
                    'supplier_name' => (string) $row->supplier_name,
                    'amount' => (float) $row->total_amount,
                    'alias_voucher' => (string) $outletMapping['alias_voucher'],
                    'inventory_coa_code' => (string) $outletMapping['inventory_coa_code'],
                    'inventory_coa_name' => (string) $outletMapping['inventory_coa_name'],
                    'ap_coa_code' => $apAccount['code'],
                    'ap_coa_name' => $apAccount['name'],
                ];
            })
            ->filter()
            ->values();

        if ($resolvedRows->isEmpty()) {
            return [];
        }

        $coaByCode = $this->loadCoaAccountsByCode($resolvedRows
            ->pluck('inventory_coa_code')
            ->merge($resolvedRows->pluck('ap_coa_code'))
            ->unique()
            ->values()
            ->all());

        $rows = [];
        foreach ($resolvedRows as $item) {
            $amount = (float) $item['amount'];
            if ($amount <= 0) {
                continue;
            }

            $inventoryCode = (string) $item['inventory_coa_code'];
            $apCode = (string) $item['ap_coa_code'];
            $alias = strtoupper((string) $item['alias_voucher']);
            $supplierName = (string) $item['supplier_name'];

            $inventoryAccount = [
                'code' => $inventoryCode,
                'name' => $coaByCode[$inventoryCode]->name ?? (string) $item['inventory_coa_name'],
            ];
            $apAccount = [
                'code' => $apCode,
                'name' => $coaByCode[$apCode]->name ?? (string) $item['ap_coa_name'],
            ];

            $voucher = 'PUR' . $alias . $monthEnd->format('my');
            $journalName = 'PEMBELIAN ' . $alias;
            $journalNote = sprintf(
                'Pembelian Persediaan %s %s sd %s',
                $supplierName,
                $monthStart->format('d M Y'),
                $monthEnd->format('d M Y')
            );

            $rows[] = $this->makeRow('PEMBELIAN', $inventoryAccount, $voucher, $monthEnd, $amount, 'D', $journalName, $journalNote);
            $rows[] = $this->makeRow('PEMBELIAN', $apAccount, $voucher, $monthEnd, $amount, 'K', $journalName, $journalNote);
        }

        return $rows;
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryPurchaseSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $query = DB::table('purchases')
            ->join('outlets', 'outlets.id', '=', 'purchases.outlet_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->where('purchases.status', 'received')
            ->whereBetween('purchases.purchase_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select(
                'purchases.outlet_id',
                'outlets.name as outlet_name',
                'outlets.code as outlet_code',
                'purchases.supplier_id',
                'suppliers.name as supplier_name',
                'suppliers.code as supplier_code'
            )
            ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_amount')
            ->groupBy(
                'purchases.outlet_id',
                'outlets.name',
                'outlets.code',
                'purchases.supplier_id',
                'suppliers.name',
                'suppliers.code'
            )
            ->havingRaw('COALESCE(SUM(purchases.total_amount), 0) > 0')
            ->orderBy('outlets.name')
            ->orderBy('suppliers.name');

        if (!empty($outletIds)) {
            $query->whereIn('purchases.outlet_id', $outletIds);
        }

        return $query->get();
    }

    /**
     * @return array{code: string, name: string}
     */
    private function resolveApAccount(string $supplierName, string $supplierCode): array
    {
        $normalizedName = $this->normalizeText($supplierName);
        $normalizedCode = $this->normalizeText($supplierCode);

        foreach ((array) config('sales_journal.purchase.supplier_ap_mappings', []) as $mapping) {
            $candidates = collect((array) ($mapping['match'] ?? []))
                ->map(fn ($value) => $this->normalizeText((string) $value))
                ->filter()
                ->values();

            foreach ($candidates as $candidate) {
                if ($candidate === $normalizedName || $candidate === $normalizedCode) {
                    return [
                        'code' => (string) $mapping['code'],
                        'name' => (string) $mapping['name'],
                    ];
                }

                if (strlen($candidate) >= 4 && (str_contains($normalizedName, $candidate) || str_contains($normalizedCode, $candidate))) {
                    return [
                        'code' => (string) $mapping['code'],
                        'name' => (string) $mapping['name'],
                    ];
                }
            }
        }

        $default = (array) config('sales_journal.purchase.default_ap_account', []);

        return [
            'code' => (string) ($default['code'] ?? '2102020'),
            'name' => (string) ($default['name'] ?? 'Hutang Usaha Lain-lain'),
        ];
    }

    /**
     * @param array{code: string, name: string} $account
     * @return array<string, mixed>
     */
    private function makeRow(string $status, array $account, string $voucher, Carbon $date, float $amount, string $mutation, string $journalName, string $journalNote): array
    {
        return [
            'STATUS' => $status,
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

        return CoaAccount::query()
            ->whereIn('code', $cleanCodes->all())
            ->get()
            ->keyBy('code')
            ->all();
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
    private function outletMappingCollection(): Collection
    {
        return collect((array) config('sales_journal.hpp.outlet_mappings', []));
    }
}
