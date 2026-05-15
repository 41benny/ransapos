<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\Product;
use Illuminate\Support\Carbon;
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
        ?int $userId = null,
        ?string $notes = 'Penjualan',
        ?string $mutationDate = null
    ): void {
        DB::beginTransaction();

        try {
            $mutationDate = $this->normalizeMutationDate($mutationDate);

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

            if (! config('app.allow_negative_stock', false) && (float) $stock->quantity < $quantity) {
                $productName = Product::query()->find($productId)?->name ?? 'produk';
                throw new Exception(
                    'Stok ' . $productName . ' tidak mencukupi. Tersedia: ' . rtrim(rtrim(number_format((float) $stock->quantity, 4, '.', ''), '0'), '.')
                );
            }

            // Simpan stok sebelumnya
            $stockBefore = $stock->quantity;

            // Kurangi stok
            $stock->quantity -= $quantity;
            $stock->last_mutation_at = now();
            $stock->save();

            // Ambil cost dari CostService (moving average)
            $unitCost = app(CostService::class)->getAvgCost($productId, $outletId);
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
                'mutation_date' => $mutationDate,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $this->recalculateMutationBalancesIfNeeded($productId, $outletId, $mutationDate);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     */
    public function restoreSaleStock(
        int $productId,
        int $outletId,
        float $quantity,
        int $saleId,
        ?int $userId = null,
        ?string $notes = null,
        ?string $mutationDate = null
    ): void {
        DB::beginTransaction();

        try {
            $mutationDate = $this->normalizeMutationDate($mutationDate);

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

            $stockBefore = $stock->quantity;
            $stock->quantity += $quantity;
            $stock->last_mutation_at = now();
            $stock->save();

            // Ambil cost dari mutasi sale asli (snapshot saat penjualan terjadi)
            $originalMutation = StockMutation::where('product_id', $productId)
                ->where('outlet_id', $outletId)
                ->where('reference_type', 'sale')
                ->where('reference_id', $saleId)
                ->where('mutation_type', 'out')
                ->first();

            $unitCost = $originalMutation->unit_cost ?? app(CostService::class)->getAvgCost($productId, $outletId);
            $totalCost = $unitCost * $quantity;

            StockMutation::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
                'mutation_type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'sale_cancellation',
                'reference_id' => $saleId,
                'mutation_date' => $mutationDate,
                'notes' => $notes ?: 'Pembatalan transaksi',
                'created_by' => $userId,
            ]);

            $this->recalculateMutationBalancesIfNeeded($productId, $outletId, $mutationDate);

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
        ?int $userId = null,
        float $unitPrice = 0,
        ?string $mutationDate = null
    ): void {
        DB::beginTransaction();

        try {
            $mutationDate = $this->normalizeMutationDate($mutationDate);

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

            // Catat mutasi dengan cost
            StockMutation::create([
                'product_id' => $productId,
                'outlet_id' => $outletId,
                'mutation_type' => 'in',
                'quantity' => $quantity, // Positif karena masuk
                'unit_cost' => $unitPrice,
                'total_cost' => $unitPrice * $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'purchase',
                'reference_id' => $purchaseId,
                'mutation_date' => $mutationDate,
                'notes' => 'Pembelian',
                'created_by' => $userId,
            ]);

            $this->recalculateMutationBalancesIfNeeded($productId, $outletId, $mutationDate);

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
        ?int $userId = null,
        ?string $mutationDate = null
    ): void {
        DB::beginTransaction();

        try {
            $mutationDate = $this->normalizeMutationDate($mutationDate);

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
            $unitCost = app(CostService::class)->getAvgCost($productId, $outletId);
            $totalCost = abs($difference) * $unitCost;

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
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'stock_before' => $stockBefore,
                'stock_after' => $stock->quantity,
                'reference_type' => 'stock_opname',
                'reference_id' => null,
                'mutation_date' => $mutationDate,
                'notes' => $notes ?: 'Adjustment stok manual',
                'created_by' => $userId,
            ]);

            $this->recalculateMutationBalancesIfNeeded($productId, $outletId, $mutationDate);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recalculateMutationBalances(int $productId, int $outletId, string $fromDate): void
    {
        $fromDate = $this->normalizeMutationDate($fromDate);
        $stock = Stock::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        $mutations = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->whereDate('mutation_date', '>=', $fromDate)
            ->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $lastMutationBefore = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->whereDate('mutation_date', '<', $fromDate)
            ->orderBy('mutation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastMutationBefore) {
            $runningStock = (float) $lastMutationBefore->stock_after;
        } else {
            $netMutationFromDate = (float) StockMutation::query()
                ->where('product_id', $productId)
                ->where('outlet_id', $outletId)
                ->whereDate('mutation_date', '>=', $fromDate)
                ->sum('quantity');

            $runningStock = round((float) ($stock?->quantity ?? 0) - $netMutationFromDate, 2);
        }

        foreach ($mutations as $mutation) {
            $stockBefore = round($runningStock, 2);
            $runningStock = round($runningStock + (float) $mutation->quantity, 2);
            $stockAfter = round($runningStock, 2);

            if (
                round((float) $mutation->stock_before, 2) === $stockBefore
                && round((float) $mutation->stock_after, 2) === $stockAfter
            ) {
                continue;
            }

            $mutation->stock_before = $stockBefore;
            $mutation->stock_after = $stockAfter;
            $mutation->save();
        }

        $latestMutation = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->orderBy('mutation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

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

        if ($latestMutation) {
            $stock->quantity = $latestMutation->stock_after;
            $stock->last_mutation_at = $latestMutation->created_at ?? Carbon::parse($latestMutation->mutation_date);
        } else {
            $stock->quantity = 0;
            $stock->last_mutation_at = now();
        }

        $stock->save();
    }

    private function recalculateMutationBalancesIfNeeded(int $productId, int $outletId, string $mutationDate): void
    {
        if (! $this->shouldRecalculateMutationBalances($productId, $outletId, $mutationDate)) {
            return;
        }

        $this->recalculateMutationBalances($productId, $outletId, $mutationDate);
    }

    private function shouldRecalculateMutationBalances(int $productId, int $outletId, string $mutationDate): bool
    {
        $date = $this->normalizeMutationDate($mutationDate);

        if (Carbon::parse($date)->lt(today())) {
            return true;
        }

        return StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->whereDate('mutation_date', '>', $date)
            ->exists();
    }

    public function recalculateAllMutationBalances(): void
    {
        $pairs = StockMutation::query()
            ->select('product_id', 'outlet_id', DB::raw('MIN(mutation_date) as first_mutation_date'))
            ->groupBy('product_id', 'outlet_id')
            ->orderBy('product_id')
            ->orderBy('outlet_id')
            ->get();

        foreach ($pairs as $pair) {
            $this->recalculateMutationBalances(
                (int) $pair->product_id,
                (int) $pair->outlet_id,
                (string) $pair->first_mutation_date
            );
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

    protected function normalizeMutationDate(?string $mutationDate = null): string
    {
        return Carbon::parse($mutationDate ?? now())->toDateString();
    }
}
