<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SalesJournalExportService
{
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
            throw new InvalidArgumentException('Mapping jurnal penjualan belum dikonfigurasi.');
        }

        $saleRows = $this->queryOutletSalesSummary($monthStart, $monthEnd, $outletIds);
        if ($saleRows->isEmpty()) {
            return [];
        }

        $discountRows = $this->queryOutletDiscountSummary($monthStart, $monthEnd, $outletIds);
        $paymentRows = $this->queryOutletPaymentSummary($monthStart, $monthEnd, $outletIds);

        $discountByOutlet = $discountRows->groupBy('outlet_id');
        $paymentByOutlet = $paymentRows->groupBy('outlet_id');

        $rows = [];
        foreach ($saleRows as $saleRow) {
            $mapping = $this->findMappingForOutlet(
                (string) $saleRow->outlet_name,
                (string) ($saleRow->outlet_code ?? ''),
                $mappings
            );

            if ($mapping === null) {
                continue;
            }

            $alias = (string) $mapping['alias_voucher'];
            $voucher = 'SAL' . strtoupper($alias) . $monthEnd->format('my');
            $journalName = 'PENJUALAN ' . strtoupper($alias);
            $journalNote = sprintf(
                'PENJUALAN %s %s sd %s',
                strtoupper($alias),
                $monthStart->format('d M Y'),
                $monthEnd->format('d M Y')
            );

            $grossSales = (float) $saleRow->gross_sales;
            $taxAmount = (float) $saleRow->tax_amount;
            $serviceChargeAmount = (float) $saleRow->service_charge_amount;
            $roundingAmount = (float) $saleRow->rounding_amount;

            $this->appendRow($rows, 'SALES', (string) $mapping['sales_coa_code'], $voucher, $monthEnd, $grossSales, 'K', $journalName, $journalNote, 'Penjualan Gross', (string) $mapping['sales_coa_name']);
            $this->appendRow($rows, 'SALES', (string) $mapping['sales_coa_code'], $voucher, $monthEnd, $taxAmount, 'K', $journalName, $journalNote, 'Pajak Penjualan', (string) $mapping['sales_coa_name']);
            $this->appendRow($rows, 'SALES', (string) $mapping['sales_coa_code'], $voucher, $monthEnd, $serviceChargeAmount, 'K', $journalName, $journalNote, 'Service Charge', (string) $mapping['sales_coa_name']);

            foreach ($discountByOutlet->get($saleRow->outlet_id, collect()) as $discountRow) {
                $isMeal = $this->isMealSalesType((string) ($discountRow->sales_type ?? ''));
                $accountCode = $isMeal ? (string) $mapping['meal_coa_code'] : (string) $mapping['discount_coa_code'];
                $accountName = $isMeal ? (string) $mapping['meal_coa_name'] : (string) $mapping['discount_coa_name'];
                $label = $isMeal ? 'Meal Karyawan' : 'Diskon Penjualan';

                $this->appendRow($rows, 'SALES', $accountCode, $voucher, $monthEnd, (float) $discountRow->discount_amount, 'D', $journalName, $journalNote, $label, $accountName);
            }

            if (abs($roundingAmount) > 0.00001) {
                $rounding = (array) config('sales_journal.sales.rounding_account', []);
                $this->appendRow(
                    $rows,
                    'SALES',
                    (string) ($rounding['code'] ?? '7000007'),
                    $voucher,
                    $monthEnd,
                    abs($roundingAmount),
                    $roundingAmount > 0 ? 'K' : 'D',
                    $journalName,
                    $journalNote,
                    'Pembulatan',
                    (string) ($rounding['name'] ?? 'Pembulatan')
                );
            }

            foreach ($paymentByOutlet->get($saleRow->outlet_id, collect()) as $paymentRow) {
                $paymentAccount = $this->resolvePaymentAccount((string) $paymentRow->payment_method_code, (string) $paymentRow->payment_method_name, $mapping);
                $this->appendRow(
                    $rows,
                    'SALES',
                    $paymentAccount['code'],
                    $voucher,
                    $monthEnd,
                    (float) $paymentRow->payment_amount,
                    'D',
                    $journalName,
                    $journalNote,
                    (string) $paymentRow->payment_method_name,
                    $paymentAccount['name']
                );
            }
        }

        return $rows;
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryOutletSalesSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $grossSubquery = DB::table('sale_items')
            ->select('sale_id')
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as gross_sales')
            ->groupBy('sale_id');

        $query = DB::table('sales')
            ->join('outlets', 'outlets.id', '=', 'sales.outlet_id')
            ->leftJoinSub($grossSubquery, 'gross_items', function ($join) {
                $join->on('gross_items.sale_id', '=', 'sales.id');
            })
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select('sales.outlet_id', 'outlets.name as outlet_name', 'outlets.code as outlet_code')
            ->selectRaw('COALESCE(SUM(gross_items.gross_sales), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(sales.tax_amount), 0) as tax_amount')
            ->selectRaw('COALESCE(SUM(sales.service_charge_amount), 0) as service_charge_amount')
            ->selectRaw('COALESCE(SUM(sales.rounding_amount), 0) as rounding_amount')
            ->groupBy('sales.outlet_id', 'outlets.name', 'outlets.code')
            ->orderBy('outlets.name');

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        return $query->get();
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryOutletDiscountSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $itemDiscountSubquery = DB::table('sale_items')
            ->select('sale_id')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as item_discount_amount')
            ->groupBy('sale_id');

        $query = DB::table('sales')
            ->leftJoinSub($itemDiscountSubquery, 'item_discounts', function ($join) {
                $join->on('item_discounts.sale_id', '=', 'sales.id');
            })
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select('sales.outlet_id', 'sales.sales_type')
            ->selectRaw('COALESCE(SUM(sales.discount_amount + COALESCE(item_discounts.item_discount_amount, 0)), 0) as discount_amount')
            ->groupBy('sales.outlet_id', 'sales.sales_type')
            ->havingRaw('COALESCE(SUM(sales.discount_amount + COALESCE(item_discounts.item_discount_amount, 0)), 0) > 0');

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        return $query->get();
    }

    /**
     * @param array<int, int> $outletIds
     */
    private function queryOutletPaymentSummary(Carbon $monthStart, Carbon $monthEnd, array $outletIds): Collection
    {
        $query = DB::table('payments')
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->select(
                'sales.outlet_id',
                'payment_methods.code as payment_method_code',
                'payment_methods.name as payment_method_name'
            )
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as payment_amount')
            ->groupBy('sales.outlet_id', 'payment_methods.code', 'payment_methods.name')
            ->havingRaw('COALESCE(SUM(payments.amount), 0) > 0');

        if (!empty($outletIds)) {
            $query->whereIn('sales.outlet_id', $outletIds);
        }

        return $query->get();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function appendRow(array &$rows, string $status, string $accountCode, string $voucher, Carbon $date, float $amount, string $mutation, string $journalName, string $journalNote, string $description, string $accountName): void
    {
        if ($amount <= 0) {
            return;
        }

        $rows[] = [
            'STATUS' => $status,
            'NO_AKUN' => (int) $accountCode,
            '_VOUCHER' => $voucher,
            'J_TANGGAL' => $date->format('d/m/Y'),
            'J_JUMLAH' => $amount,
            'D' => $mutation === 'D' ? $amount : null,
            'K' => $mutation === 'K' ? $amount : null,
            'J_MUTASI' => $mutation,
            'J_NAMA' => $journalName,
            'J_KET1' => $description,
            'KET 2' => $accountName,
        ];
    }

    /**
     * @param array<string, mixed> $mapping
     * @return array{code: string, name: string}
     */
    private function resolvePaymentAccount(string $methodCode, string $methodName, array $mapping): array
    {
        $normalizedMethod = $this->normalizeText($methodCode !== '' ? $methodCode : $methodName);

        foreach ((array) config('sales_journal.sales.payment_accounts', []) as $account) {
            $methods = collect((array) ($account['methods'] ?? []))
                ->map(fn ($value) => $this->normalizeText((string) $value))
                ->filter();

            if ($methods->contains($normalizedMethod)) {
                return [
                    'code' => (string) $account['code'],
                    'name' => (string) $account['name'],
                ];
            }
        }

        return [
            'code' => (string) $mapping['cashbank_coa_code'],
            'name' => (string) $mapping['cashbank_coa_name'],
        ];
    }

    private function isMealSalesType(string $salesType): bool
    {
        return str_contains($this->normalizeText($salesType), 'meal');
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
        return collect((array) config('sales_journal.sales.outlet_mappings', []));
    }
}
