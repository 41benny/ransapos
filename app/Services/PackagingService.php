<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\CashSessionPackagingClosing;
use App\Models\CashSessionPackagingOpening;
use App\Models\PackagingAdjustment;
use App\Models\PackagingItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PackagingService
{
    /**
     * Item packaging aktif, terurut.
     */
    public function activeItems(): Collection
    {
        return PackagingItem::active()->ordered()->get();
    }

    /**
     * Nilai default stok awal per item = stok akhir fisik dari closing terakhir
     * pada outlet yang sama.
     *
     * @return array<int, float> packaging_item_id => qty
     */
    public function openingDefaults(int $outletId): array
    {
        $lastClosedSession = CashSession::where('outlet_id', $outletId)
            ->where('status', 'closed')
            ->whereHas('packagingClosings')
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->first();

        if (! $lastClosedSession) {
            return [];
        }

        return CashSessionPackagingClosing::where('cash_session_id', $lastClosedSession->id)
            ->pluck('closing_physical_qty', 'packaging_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Simpan stok awal packaging saat open shift.
     *
     * @param array<int, float> $qtyByItemId packaging_item_id => qty
     */
    public function saveOpenings(CashSession $session, array $qtyByItemId, ?User $user = null): void
    {
        $defaults = $this->openingDefaults($session->outlet_id);
        $userId = $user?->id ?? auth()->id();

        DB::transaction(function () use ($session, $qtyByItemId, $defaults, $userId) {
            foreach ($qtyByItemId as $itemId => $qty) {
                $qty = (float) $qty;
                $source = array_key_exists($itemId, $defaults) ? (float) $defaults[$itemId] : null;
                $isCorrected = $source !== null && abs($source - $qty) > 0.001;

                CashSessionPackagingOpening::updateOrCreate(
                    ['cash_session_id' => $session->id, 'packaging_item_id' => $itemId],
                    [
                        'opening_qty' => $qty,
                        'source_last_closing_qty' => $source,
                        'is_manual_corrected' => $isCorrected,
                        'created_by' => $userId,
                    ]
                );
            }
        });
    }

    /**
     * Estimasi pemakaian packaging dari penjualan shift.
     * Rumus per item transaksi: CEIL(quantity * qty_per_product).
     *
     * @return array<int, float> packaging_item_id => estimated qty
     */
    public function estimateSalesUsage(CashSession $session): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('product_packaging_mappings', function ($join) {
                $join->on('product_packaging_mappings.product_id', '=', 'sale_items.product_id')
                    ->where('product_packaging_mappings.is_active', '=', true);
            })
            ->where('sales.cash_session_id', $session->id)
            ->where('sales.status', 'completed')
            ->select(
                'product_packaging_mappings.packaging_item_id',
                'sale_items.quantity',
                'product_packaging_mappings.qty_per_product'
            )
            ->get();

        $usage = [];
        foreach ($rows as $row) {
            $itemId = (int) $row->packaging_item_id;
            $estimated = (int) ceil(((float) $row->quantity) * ((float) $row->qty_per_product));
            $usage[$itemId] = ($usage[$itemId] ?? 0) + $estimated;
        }

        return $usage;
    }

    /**
     * Produk terjual pada shift yang belum punya mapping packaging aktif.
     *
     * @return Collection<int, object>
     */
    public function unmappedSoldProducts(CashSession $session): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('sales.cash_session_id', $session->id)
            ->where('sales.status', 'completed')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('product_packaging_mappings as ppm')
                    ->whereColumn('ppm.product_id', 'sale_items.product_id')
                    ->where('ppm.is_active', true);
            })
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_sku', 'product_categories.name')
            ->select(
                'sale_items.product_id',
                'sale_items.product_name',
                'sale_items.product_sku',
                'product_categories.name as category',
                DB::raw('SUM(sale_items.quantity) as qty_sold')
            )
            ->get();
    }

    /**
     * Jumlah adjustment approved/pending per item untuk shift.
     *
     * @return array<int, array{approved_in: float, approved_out: float, pending_in: float, pending_out: float}>
     */
    public function adjustmentTotals(CashSession $session): array
    {
        $rows = PackagingAdjustment::where('cash_session_id', $session->id)
            ->whereIn('status', ['approved', 'pending'])
            ->select('packaging_item_id', 'type', 'status', DB::raw('SUM(qty) as total'))
            ->groupBy('packaging_item_id', 'type', 'status')
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $itemId = (int) $row->packaging_item_id;
            $totals[$itemId] ??= ['approved_in' => 0, 'approved_out' => 0, 'pending_in' => 0, 'pending_out' => 0];
            $key = $row->status . '_' . $row->type; // approved_in, approved_out, pending_in, pending_out
            if (array_key_exists($key, $totals[$itemId])) {
                $totals[$itemId][$key] += (float) $row->total;
            }
        }

        return $totals;
    }

    /**
     * Data lengkap untuk section Closing Packaging.
     *
     * @return array{rows: array<int, array<string, mixed>>, unmapped: Collection, has_pending: bool}
     */
    public function closingContext(CashSession $session): array
    {
        $items = $this->activeItems();

        $openings = CashSessionPackagingOpening::where('cash_session_id', $session->id)
            ->pluck('opening_qty', 'packaging_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        $adjustments = $this->adjustmentTotals($session);
        $estimates = $this->estimateSalesUsage($session);

        // Sertakan juga item non-aktif yang sempat dipakai (punya opening / adjustment).
        $extraIds = array_diff(
            array_unique(array_merge(array_keys($openings), array_keys($adjustments), array_keys($estimates))),
            $items->pluck('id')->all()
        );
        if (! empty($extraIds)) {
            $items = $items->concat(PackagingItem::whereIn('id', $extraIds)->get())
                ->unique('id')
                ->sortBy('sort_order')
                ->values();
        }

        $rows = [];
        $hasPending = false;

        foreach ($items as $item) {
            $opening = $openings[$item->id] ?? 0.0;
            $adj = $adjustments[$item->id] ?? ['approved_in' => 0, 'approved_out' => 0, 'pending_in' => 0, 'pending_out' => 0];
            $estimated = (float) ($estimates[$item->id] ?? 0);

            $available = $opening + $adj['approved_in'] - $adj['approved_out'];
            $defaultPhysical = max($available - $estimated, 0);

            if ($adj['pending_in'] > 0 || $adj['pending_out'] > 0) {
                $hasPending = true;
            }

            $rows[] = [
                'item' => $item,
                'opening_qty' => $opening,
                'approved_in' => $adj['approved_in'],
                'approved_out' => $adj['approved_out'],
                'pending_in' => $adj['pending_in'],
                'pending_out' => $adj['pending_out'],
                'available' => $available,
                'estimated' => $estimated,
                'default_physical' => $defaultPhysical,
            ];
        }

        return [
            'rows' => $rows,
            'unmapped' => $this->unmappedSoldProducts($session),
            'has_pending' => $hasPending,
        ];
    }

    /**
     * Simpan hasil closing packaging (dipanggil saat close shift).
     *
     * @param array<int, float> $physicalByItemId packaging_item_id => stok akhir fisik
     */
    public function saveClosings(CashSession $session, array $physicalByItemId): void
    {
        $context = $this->closingContext($session);

        DB::transaction(function () use ($session, $context, $physicalByItemId) {
            foreach ($context['rows'] as $row) {
                $itemId = $row['item']->id;
                $physical = (float) ($physicalByItemId[$itemId] ?? $row['default_physical']);

                $actualUsed = $row['available'] - $physical;
                $difference = $actualUsed - $row['estimated'];

                CashSessionPackagingClosing::updateOrCreate(
                    ['cash_session_id' => $session->id, 'packaging_item_id' => $itemId],
                    [
                        'opening_qty' => $row['opening_qty'],
                        'approved_adjustment_in_qty' => $row['approved_in'],
                        'approved_adjustment_out_qty' => $row['approved_out'],
                        'pending_adjustment_in_qty' => $row['pending_in'],
                        'pending_adjustment_out_qty' => $row['pending_out'],
                        'closing_physical_qty' => $physical,
                        'actual_used_qty' => $actualUsed,
                        'estimated_sales_used_qty' => $row['estimated'],
                        'difference_qty' => $difference,
                    ]
                );
            }
        });
    }

    /**
     * Approve adjustment packaging.
     */
    public function approveAdjustment(PackagingAdjustment $adjustment, User $approver): PackagingAdjustment
    {
        if ($adjustment->status !== 'pending') {
            return $adjustment;
        }

        $adjustment->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
        ]);

        $this->resyncClosingSnapshot($adjustment);

        return $adjustment;
    }

    /**
     * Reject adjustment packaging.
     */
    public function rejectAdjustment(PackagingAdjustment $adjustment, User $approver): PackagingAdjustment
    {
        if ($adjustment->status !== 'pending') {
            return $adjustment;
        }

        $adjustment->update([
            'status' => 'rejected',
            'rejected_by' => $approver->id,
            'rejected_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->resyncClosingSnapshot($adjustment);

        return $adjustment;
    }

    /**
     * Setelah adjustment diapprove/reject, snapshot closing yang sudah tersimpan
     * (dibuat saat shift ditutup, ketika adjustment ini masih pending) jadi basi.
     * Hitung ulang baris closing terkait kalau shift-nya sudah closed.
     */
    private function resyncClosingSnapshot(PackagingAdjustment $adjustment): void
    {
        $session = $adjustment->cashSession;

        if (! $session || $session->status !== 'closed') {
            return;
        }

        $closing = CashSessionPackagingClosing::where('cash_session_id', $session->id)
            ->where('packaging_item_id', $adjustment->packaging_item_id)
            ->first();

        if (! $closing) {
            return;
        }

        $totals = $this->adjustmentTotals($session)[$adjustment->packaging_item_id]
            ?? ['approved_in' => 0, 'approved_out' => 0, 'pending_in' => 0, 'pending_out' => 0];

        $available = (float) $closing->opening_qty + $totals['approved_in'] - $totals['approved_out'];
        $actualUsed = $available - (float) $closing->closing_physical_qty;
        $difference = $actualUsed - (float) $closing->estimated_sales_used_qty;

        $closing->update([
            'approved_adjustment_in_qty' => $totals['approved_in'],
            'approved_adjustment_out_qty' => $totals['approved_out'],
            'pending_adjustment_in_qty' => $totals['pending_in'],
            'pending_adjustment_out_qty' => $totals['pending_out'],
            'actual_used_qty' => $actualUsed,
            'difference_qty' => $difference,
        ]);
    }
}
