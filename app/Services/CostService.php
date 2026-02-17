<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCost;
use App\Models\Stock;
use App\Models\BomHeader;

class CostService
{
    /**
     * Ambil avg cost aktif untuk produk di outlet tertentu.
     * Fallback ke product.purchase_price jika belum ada record product_costs.
     */
    public function getAvgCost(int $productId, int $outletId): float
    {
        $record = ProductCost::where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        if ($record && (float) $record->avg_cost > 0) {
            return (float) $record->avg_cost;
        }

        // Fallback ke purchase_price dari master produk
        $product = Product::find($productId);
        return (float) ($product->purchase_price ?? 0);
    }

    /**
     * Update avg cost setelah receive purchase (moving average).
     *
     * Formula: ((qty_lama × avg_lama) + (qty_masuk × harga_beli_neto)) / (qty_lama + qty_masuk)
     *
     * PENTING: method ini harus dipanggil SETELAH stok sudah ditambah oleh StockService,
     * karena kita perlu qty SEBELUM receive (qty_lama = stock.quantity - receivedQty).
     */
    public function updateAvgCostOnReceive(
        int $productId,
        int $outletId,
        float $receivedQty,
        float $unitPrice
    ): float {
        // Ambil stok saat ini (sudah termasuk qty baru dari addPurchaseStock)
        $stock = Stock::where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        $currentQty = (float) ($stock->quantity ?? 0);
        $previousQty = $currentQty - $receivedQty;

        // Ambil atau buat record product_costs
        $costRecord = ProductCost::firstOrCreate(
            ['product_id' => $productId, 'outlet_id' => $outletId],
            ['avg_cost' => 0]
        );

        $oldAvgCost = (float) $costRecord->avg_cost;

        // Jika belum punya avg cost (record baru), fallback ke purchase_price
        if ($oldAvgCost <= 0 && $previousQty <= 0) {
            // Receive pertama: avg cost = harga beli
            $newAvgCost = $unitPrice;
        } elseif ($previousQty <= 0) {
            // Qty sebelumnya 0 atau negatif: avg cost = harga beli baru
            $newAvgCost = $unitPrice;
        } else {
            // Moving average formula
            $totalOldValue = $previousQty * $oldAvgCost;
            $totalNewValue = $receivedQty * $unitPrice;
            $newAvgCost = ($totalOldValue + $totalNewValue) / ($previousQty + $receivedQty);
        }

        // Simpan avg cost baru
        $costRecord->update([
            'avg_cost' => round($newAvgCost, 4),
            'last_calculated_at' => now(),
        ]);

        return round($newAvgCost, 4);
    }

    /**
     * Hitung COGS per item berdasarkan tipe produk:
     * - service: COGS = 0
     * - raw_material: COGS = avg_cost × quantity
     * - finished_good + BOM: COGS = sum(komponen avg_cost × bom_qty × sale_qty)
     * - finished_good tanpa BOM: COGS = avg_cost × quantity (fallback)
     */
    public function calculateItemCogs(Product $product, float $quantity, int $outletId): float
    {
        $type = $product->product_type ?? 'finished_good';

        if ($type === 'service') {
            return 0;
        }

        if ($type === 'raw_material') {
            $avgCost = $this->getAvgCost($product->id, $outletId);
            return $avgCost * $quantity;
        }

        // finished_good: cek BOM aktif
        $bom = $product->bomHeader && $product->bomHeader->is_active
            ? $product->bomHeader
            : null;

        if ($bom) {
            // Ada BOM: COGS = sum(component avg_cost × bom_detail.quantity × sale_quantity)
            $totalCogs = 0;
            foreach ($bom->details as $detail) {
                $componentAvgCost = $this->getAvgCost($detail->component_product_id, $outletId);
                $totalCogs += $componentAvgCost * $detail->quantity * $quantity;
            }
            return $totalCogs;
        }

        // Tidak ada BOM: fallback ke avg cost produk itu sendiri
        $avgCost = $this->getAvgCost($product->id, $outletId);
        return $avgCost * $quantity;
    }
}
