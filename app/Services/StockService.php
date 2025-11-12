<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class StockService
{
    /**
     * Kurangi stok karena penjualan
     * 
     * @param int $productId
     * @param int $outletId
     * @param float $quantity
     * @param int $saleId
     * @param int|null $userId
     * @return void
     * @throws Exception
     */
    public function reduceSaleStock(
        int $productId, 
        int $outletId, 
        float $quantity, 
        int $saleId,
        ?int $userId = null
    ): void {
        DB::beginTransaction();
        
        try {
            // Ambil atau buat stok
            $stock = Stock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'outlet_id' => $outletId,
                ],
                [
                    'quantity' => 0,
                    'last_mutation_at' => now(),
                ]
            );

            // Cek apakah stok mencukupi (kecuali jika allow negative stock)
            $allowNegativeStock = config('app.allow_negative_stock', false);
            if (!$allowNegativeStock && $stock->quantity < $quantity) {
                $product = Product::find($productId);
                throw new Exception("Stok {$product->name} tidak mencukupi. Tersedia: {$stock->quantity}");
            }

            // Simpan stok sebelumnya
            $stockBefore = $stock->quantity;

            // Kurangi stok
            $stock->quantity -= $quantity;
            $stock->last_mutation_at = now();
            $stock->save();

            // Ambil cost dari product (purchase_price sebagai cost)
            $product = Product::find($productId);
            $unitCost = $product->purchase_price ?? 0;
            $totalCost = $unitCost * $quantity;

            // Catat mutasi dengan HPP
            StockMutation::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
                'mutation_type' => 'out',
                'quantity' => -$quantity, // Negatif karena keluar
                'unit_cost' => $unitCost, // HPP per unit
                'total_cost' => $totalCost, // Total HPP
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'sale',
                'reference_id' => $saleId,
                'mutation_date' => now()->toDateString(),
                'notes' => 'Penjualan',
                'created_by' => $userId,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tambah stok karena pembelian
     * 
     * @param int $productId
     * @param int $outletId
     * @param float $quantity
     * @param int $purchaseId
     * @param int|null $userId
     * @return void
     */
    public function addPurchaseStock(
        int $productId,
        int $outletId,
        float $quantity,
        int $purchaseId,
        ?int $userId = null
    ): void {
        DB::beginTransaction();
        
        try {
            // Ambil atau buat stok
            $stock = Stock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'outlet_id' => $outletId,
                ],
                [
                    'quantity' => 0,
                    'last_mutation_at' => now(),
                ]
            );

            // Simpan stok sebelumnya
            $stockBefore = $stock->quantity;

            // Tambah stok
            $stock->quantity += $quantity;
            $stock->last_mutation_at = now();
            $stock->save();

            // Catat mutasi
            StockMutation::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
                'mutation_type' => 'in',
                'quantity' => $quantity, // Positif karena masuk
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'purchase',
                'reference_id' => $purchaseId,
                'mutation_date' => now()->toDateString(),
                'notes' => 'Pembelian',
                'created_by' => $userId,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Adjustment stok manual
     * 
     * @param int $productId
     * @param int $outletId
     * @param float $newQuantity
     * @param string $notes
     * @param int|null $userId
     * @return void
     */
    public function adjustStock(
        int $productId,
        int $outletId,
        float $newQuantity,
        string $notes = '',
        ?int $userId = null
    ): void {
        DB::beginTransaction();
        
        try {
            // Ambil atau buat stok
            $stock = Stock::firstOrCreate(
                [
                    'product_id' => $productId,
                    'outlet_id' => $outletId,
                ],
                [
                    'quantity' => 0,
                    'last_mutation_at' => now(),
                ]
            );

            // Simpan stok sebelumnya
            $stockBefore = $stock->quantity;
            
            // Hitung selisih
            $difference = $newQuantity - $stockBefore;

            // Update stok
            $stock->quantity = $newQuantity;
            $stock->last_mutation_at = now();
            $stock->save();

            // Catat mutasi
            StockMutation::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
                'mutation_type' => 'adjustment',
                'quantity' => $difference,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'stock_opname',
                'reference_id' => null,
                'mutation_date' => now()->toDateString(),
                'notes' => $notes ?: 'Adjustment stok manual',
                'created_by' => $userId,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cek ketersediaan stok
     * 
     * @param int $productId
     * @param int $outletId
     * @return float
     */
    public function getAvailableStock(int $productId, int $outletId): float
    {
        $stock = Stock::where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        return $stock ? $stock->quantity : 0;
    }
}

