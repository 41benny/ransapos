<?php

namespace App\Services;

use App\Models\BomHeader;
use App\Models\Production;
use App\Models\ProductionMaterial;
use App\Models\Stock;
use App\Models\StockMutation;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function __construct(
        private readonly CostService $costService,
        private readonly StockService $stockService
    ) {
    }

    public function createProduction(array $data): Production
    {
        $quantity = (float) $data['quantity'];
        if ($quantity <= 0) {
            throw new Exception('Jumlah hasil produksi harus lebih dari 0.');
        }

        return DB::transaction(function () use ($data, $quantity) {
            $bom = BomHeader::query()
                ->where('source_type', 'production')
                ->where('is_active', true)
                ->with(['product', 'details.component'])
                ->lockForUpdate()
                ->findOrFail((int) $data['bom_id']);

            if ($bom->details->isEmpty()) {
                throw new Exception('BOM produksi belum memiliki bahan.');
            }

            $outletId = (int) $data['outlet_id'];
            $productionDate = Carbon::parse($data['production_date'] ?? now())->toDateString();

            $this->ensureMaterialsAvailable($bom, $outletId, $quantity);

            $production = Production::create([
                'production_number' => $this->generateProductionNumber($outletId, $productionDate),
                'bom_id' => $bom->id,
                'product_id' => $bom->product_id,
                'outlet_id' => $outletId,
                'production_date' => $productionDate,
                'quantity' => $quantity,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $totalCost = 0.0;

            foreach ($bom->details as $detail) {
                $materialQty = (float) $detail->quantity * $quantity;
                $unitCost = $this->costService->getAvgCost((int) $detail->component_product_id, $outletId);
                $lineCost = $unitCost * $materialQty;
                $totalCost += $lineCost;

                ProductionMaterial::create([
                    'production_id' => $production->id,
                    'product_id' => $detail->component_product_id,
                    'quantity' => $materialQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'uom' => $detail->uom ?: $detail->component?->unit,
                ]);

                $this->moveStock(
                    productId: (int) $detail->component_product_id,
                    outletId: $outletId,
                    quantity: -$materialQty,
                    unitCost: $unitCost,
                    productionId: $production->id,
                    mutationType: 'out',
                    mutationDate: $productionDate,
                    notes: 'Produksi: bahan untuk ' . ($bom->product?->name ?? 'produk'),
                );
            }

            $unitCost = $quantity > 0 ? $totalCost / $quantity : 0;

            $this->moveStock(
                productId: (int) $bom->product_id,
                outletId: $outletId,
                quantity: $quantity,
                unitCost: $unitCost,
                productionId: $production->id,
                mutationType: 'in',
                mutationDate: $productionDate,
                notes: 'Hasil produksi: ' . ($bom->product?->name ?? 'produk'),
            );

            $this->costService->updateAvgCostOnReceive(
                productId: (int) $bom->product_id,
                outletId: $outletId,
                receivedQty: $quantity,
                unitPrice: $unitCost,
            );

            $production->update([
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
            ]);

            return $production->fresh()->load(['bom.details.component', 'product', 'outlet', 'materials.product', 'creator']);
        });
    }

    private function ensureMaterialsAvailable(BomHeader $bom, int $outletId, float $outputQty): void
    {
        if ((bool) config('app.allow_negative_stock', false)) {
            return;
        }

        foreach ($bom->details as $detail) {
            $requiredQty = (float) $detail->quantity * $outputQty;
            $availableQty = (float) $this->stockService->getAvailableStock((int) $detail->component_product_id, $outletId);

            if ($availableQty < $requiredQty) {
                $componentName = $detail->component?->name ?? 'bahan';
                throw new Exception(
                    'Stok bahan ' . $componentName . ' tidak mencukupi. Tersedia: ' .
                    rtrim(rtrim(number_format($availableQty, 4, '.', ''), '0'), '.') .
                    ', dibutuhkan: ' .
                    rtrim(rtrim(number_format($requiredQty, 4, '.', ''), '0'), '.')
                );
            }
        }
    }

    private function moveStock(
        int $productId,
        int $outletId,
        float $quantity,
        float $unitCost,
        int $productionId,
        string $mutationType,
        string $mutationDate,
        string $notes
    ): void {
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

        $stockBefore = (float) $stock->quantity;
        $stock->quantity = $stockBefore + $quantity;
        $stock->last_mutation_at = now();
        $stock->save();

        StockMutation::create([
            'product_id' => $productId,
            'outlet_id' => $outletId,
            'mutation_type' => $mutationType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => abs($quantity) * $unitCost,
            'stock_before' => $stockBefore,
            'stock_after' => $stock->quantity,
            'reference_type' => 'production',
            'reference_id' => $productionId,
            'mutation_date' => $mutationDate,
            'notes' => $notes,
            'created_by' => auth()->id(),
        ]);

        $this->stockService->recalculateMutationBalances($productId, $outletId, $mutationDate);
    }

    private function generateProductionNumber(int $outletId, string $productionDate): string
    {
        $date = Carbon::parse($productionDate)->format('Ymd');
        $prefix = 'PRD-' . str_pad((string) $outletId, 3, '0', STR_PAD_LEFT) . '-' . $date . '-';

        $lastProduction = Production::query()
            ->where('production_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('production_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastProduction && preg_match('/(\d+)$/', $lastProduction->production_number, $matches) === 1) {
            $nextNumber = (int) $matches[1] + 1;
        }

        do {
            $candidate = $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Production::query()->where('production_number', $candidate)->lockForUpdate()->exists());

        return $candidate;
    }
}
