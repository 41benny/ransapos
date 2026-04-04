<?php

namespace App\Support\Repairs;

use App\Models\ProductCost;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepairPurchaseHppByQuantityAction
{
    public function execute(array $config, bool $apply = false): array
    {
        $config = $this->normalizeConfig($config);
        $timestamp = now()->format('Ymd_His');
        $backupDir = $config['backup_dir'] ?: storage_path('app/private/repairs');

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Gagal membuat direktori backup repair.');
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "purchase_hpp_repair_{$timestamp}.json";

        $purchaseIds = $config['purchase_ids'];
        $productId = $config['product_id'];
        $outletId = $config['outlet_id'];
        $targetUnitPrice = $config['target_unit_price'];

        $purchases = Purchase::query()
            ->with(['items.product', 'outlet', 'supplier', 'creator', 'receiver'])
            ->whereIn('id', $purchaseIds)
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        if ($purchases->count() !== count($purchaseIds)) {
            throw new RuntimeException('Purchase target tidak lengkap.');
        }

        $purchaseItems = PurchaseItem::query()
            ->with('product')
            ->whereIn('purchase_id', $purchaseIds)
            ->where('product_id', $productId)
            ->orderBy('purchase_id')
            ->get()
            ->keyBy('purchase_id');

        if ($purchaseItems->count() !== count($purchaseIds)) {
            throw new RuntimeException('Item produk target pada purchase tidak lengkap.');
        }

        $purchaseMutations = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->where('reference_type', 'purchase')
            ->whereIn('reference_id', $purchaseIds)
            ->orderBy('mutation_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->keyBy('reference_id');

        if ($purchaseMutations->count() !== count($purchaseIds)) {
            throw new RuntimeException('Mutasi purchase target tidak lengkap.');
        }

        $firstTargetMutation = $purchaseMutations
            ->sortBy(fn (StockMutation $mutation) => implode('|', [
                $mutation->mutation_date?->format('Y-m-d'),
                $mutation->created_at?->format('Y-m-d H:i:s.u'),
                str_pad((string) $mutation->id, 20, '0', STR_PAD_LEFT),
            ]))
            ->first();

        if (!$firstTargetMutation) {
            throw new RuntimeException('Mutasi target pertama tidak ditemukan.');
        }

        $allMutationsFromDate = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->whereDate('mutation_date', '>=', $firstTargetMutation->mutation_date->toDateString())
            ->orderBy('mutation_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $downstreamMutations = $this->sliceDownstreamMutations($allMutationsFromDate, $firstTargetMutation->id);

        $unsupportedMutations = $downstreamMutations->filter(function (StockMutation $mutation) use ($purchaseIds) {
            if ($mutation->reference_type === 'sale' && $mutation->mutation_type === 'out') {
                return false;
            }

            return !($mutation->reference_type === 'purchase' && in_array((int) $mutation->reference_id, $purchaseIds, true));
        });

        if ($unsupportedMutations->isNotEmpty()) {
            throw new RuntimeException(
                'Repair dibatalkan karena ada mutasi downstream selain purchase target dan sale. ' .
                json_encode(
                    $unsupportedMutations->map(fn (StockMutation $mutation) => [
                        'id' => $mutation->id,
                        'reference_type' => $mutation->reference_type,
                        'reference_id' => $mutation->reference_id,
                        'mutation_type' => $mutation->mutation_type,
                    ])->values()->all(),
                    JSON_UNESCAPED_UNICODE
                )
            );
        }

        $plannedItems = [];
        $plannedPurchaseMutations = [];
        $plannedSaleMutations = [];

        foreach ($purchaseIds as $purchaseId) {
            $item = $purchaseItems->get($purchaseId);
            $mutation = $purchaseMutations->get($purchaseId);

            if (!$item || !$mutation) {
                throw new RuntimeException("Data purchase {$purchaseId} tidak lengkap.");
            }

            $discountAmount = (float) $item->discount_amount;
            $currentSubtotal = (float) $item->subtotal;
            $newQuantity = round(($currentSubtotal + $discountAmount) / $targetUnitPrice, 2);
            $newSubtotal = round(($newQuantity * $targetUnitPrice) - $discountAmount, 2);
            $newNetUnitCost = round($newQuantity > 0 ? ($newSubtotal / $newQuantity) : 0, 2);

            if (abs($newSubtotal - $currentSubtotal) > 0.01) {
                throw new RuntimeException("Subtotal purchase {$purchaseId} tidak pas saat dihitung balik.");
            }

            $plannedItems[$purchaseId] = [
                'purchase_item_id' => $item->id,
                'purchase_id' => $purchaseId,
                'old_quantity' => (float) $item->quantity,
                'new_quantity' => $newQuantity,
                'old_unit_price' => (float) $item->unit_price,
                'new_unit_price' => round($targetUnitPrice, 2),
                'old_subtotal' => $currentSubtotal,
                'new_subtotal' => $newSubtotal,
                'new_net_unit_cost' => $newNetUnitCost,
            ];

            $plannedPurchaseMutations[$mutation->id] = [
                'mutation_id' => $mutation->id,
                'purchase_id' => $purchaseId,
                'old_quantity' => (float) $mutation->quantity,
                'new_quantity' => $newQuantity,
                'old_unit_cost' => (float) $mutation->unit_cost,
                'new_unit_cost' => $newNetUnitCost,
                'old_total_cost' => (float) $mutation->total_cost,
                'new_total_cost' => $newSubtotal,
            ];
        }

        foreach ($downstreamMutations as $mutation) {
            if ($mutation->reference_type !== 'sale' || $mutation->mutation_type !== 'out') {
                continue;
            }

            $plannedSaleMutations[$mutation->id] = [
                'mutation_id' => $mutation->id,
                'reference_id' => (int) $mutation->reference_id,
                'old_unit_cost' => (float) $mutation->unit_cost,
                'new_unit_cost' => round($targetUnitPrice, 2),
                'old_total_cost' => (float) $mutation->total_cost,
                'new_total_cost' => round(abs((float) $mutation->quantity) * $targetUnitPrice, 2),
                'quantity' => (float) $mutation->quantity,
                'notes' => $mutation->notes,
            ];
        }

        $currentProductCost = ProductCost::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        $currentStock = Stock::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first();

        $backupPayload = [
            'meta' => [
                'created_at' => now()->toDateTimeString(),
                'apply' => $apply,
                'backup_path' => $backupPath,
                'config' => $config,
                'from_mutation_date' => $firstTargetMutation->mutation_date->toDateString(),
                'first_target_mutation_id' => $firstTargetMutation->id,
            ],
            'before' => [
                'purchases' => $purchases->values()->toArray(),
                'purchase_items' => $purchaseItems->values()->toArray(),
                'purchase_mutations' => $purchaseMutations->values()->toArray(),
                'downstream_mutations' => $downstreamMutations->map(fn (StockMutation $mutation) => $mutation->toArray())->all(),
                'product_cost' => $currentProductCost?->toArray(),
                'stock' => $currentStock?->toArray(),
            ],
            'plan' => [
                'purchase_items' => array_values($plannedItems),
                'purchase_mutations' => array_values($plannedPurchaseMutations),
                'sale_mutations' => array_values($plannedSaleMutations),
                'summary' => [
                    'target_purchase_count' => count($plannedItems),
                    'downstream_sale_mutation_count' => count($plannedSaleMutations),
                    'stock_quantity_delta' => array_sum(array_map(
                        fn (array $row) => $row['new_quantity'] - $row['old_quantity'],
                        $plannedItems
                    )),
                    'target_unit_price' => round($targetUnitPrice, 2),
                ],
            ],
        ];

        file_put_contents($backupPath, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            return [
                'status' => 'dry-run',
                'backup_path' => $backupPath,
                'summary' => $backupPayload['plan']['summary'],
                'purchase_items' => array_values($plannedItems),
                'purchase_mutations' => array_values($plannedPurchaseMutations),
                'first_sale_example' => array_values($plannedSaleMutations)[0] ?? null,
            ];
        }

        DB::transaction(function () use (
            $purchaseIds,
            $purchases,
            $purchaseItems,
            $purchaseMutations,
            $plannedItems,
            $plannedPurchaseMutations,
            $plannedSaleMutations,
            $productId,
            $outletId,
            $targetUnitPrice,
            $firstTargetMutation
        ): void {
            foreach ($purchaseIds as $purchaseId) {
                $item = $purchaseItems->get($purchaseId);
                $plan = $plannedItems[$purchaseId];

                $item->quantity = $plan['new_quantity'];
                $item->unit_price = $plan['new_unit_price'];
                $item->subtotal = $plan['new_subtotal'];
                $item->save();
            }

            foreach ($purchaseIds as $purchaseId) {
                $purchase = $purchases->get($purchaseId);
                $subtotal = round((float) $purchase->items()->sum('subtotal'), 2);
                $taxAmount = (float) $purchase->tax_amount;
                $discountAmount = (float) $purchase->discount_amount;

                $purchase->subtotal = $subtotal;
                $purchase->total_amount = round($subtotal + $taxAmount - $discountAmount, 2);
                $purchase->save();
            }

            foreach ($plannedPurchaseMutations as $mutationId => $plan) {
                $mutation = $purchaseMutations->firstWhere('id', $mutationId);
                $mutation->quantity = $plan['new_quantity'];
                $mutation->unit_cost = $plan['new_unit_cost'];
                $mutation->total_cost = $plan['new_total_cost'];
                $mutation->save();
            }

            foreach ($plannedSaleMutations as $mutationId => $plan) {
                $mutation = StockMutation::query()->findOrFail($mutationId);
                $mutation->unit_cost = $plan['new_unit_cost'];
                $mutation->total_cost = $plan['new_total_cost'];
                $mutation->save();
            }

            app(StockService::class)->recalculateMutationBalances(
                $productId,
                $outletId,
                $firstTargetMutation->mutation_date->toDateString()
            );

            ProductCost::query()->updateOrCreate(
                [
                    'product_id' => $productId,
                    'outlet_id' => $outletId,
                ],
                [
                    'avg_cost' => round($targetUnitPrice, 4),
                    'last_calculated_at' => now(),
                ]
            );
        });

        $afterPurchaseItems = PurchaseItem::query()
            ->whereIn('purchase_id', $purchaseIds)
            ->where('product_id', $productId)
            ->orderBy('purchase_id')
            ->get(['id', 'purchase_id', 'product_id', 'quantity', 'unit_price', 'subtotal']);

        $afterPurchaseMutations = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->where('reference_type', 'purchase')
            ->whereIn('reference_id', $purchaseIds)
            ->orderBy('mutation_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'reference_id', 'quantity', 'unit_cost', 'total_cost', 'stock_before', 'stock_after']);

        $firstSaleAfterId = array_key_first($plannedSaleMutations);
        $afterFirstSale = $firstSaleAfterId
            ? StockMutation::query()->find($firstSaleAfterId, [
                'id',
                'reference_id',
                'quantity',
                'unit_cost',
                'total_cost',
                'stock_before',
                'stock_after',
                'notes',
            ])
            : null;

        $afterProductCost = ProductCost::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first(['product_id', 'outlet_id', 'avg_cost', 'last_calculated_at']);

        $afterStock = Stock::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->first(['product_id', 'outlet_id', 'quantity', 'last_mutation_at']);

        $highCostSalesRemaining = StockMutation::query()
            ->where('product_id', $productId)
            ->where('outlet_id', $outletId)
            ->where('reference_type', 'sale')
            ->where('mutation_type', 'out')
            ->where('id', '>', $firstTargetMutation->id)
            ->where('unit_cost', '>=', 100)
            ->count();

        $backupPayload['after'] = [
            'purchase_items' => $afterPurchaseItems->toArray(),
            'purchase_mutations' => $afterPurchaseMutations->toArray(),
            'first_sale_after' => $afterFirstSale?->toArray(),
            'product_cost' => $afterProductCost?->toArray(),
            'stock' => $afterStock?->toArray(),
            'high_cost_sales_remaining' => $highCostSalesRemaining,
        ];

        file_put_contents($backupPath, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'status' => 'applied',
            'backup_path' => $backupPath,
            'summary' => $backupPayload['plan']['summary'],
            'verification' => $backupPayload['after'],
        ];
    }

    private function normalizeConfig(array $config): array
    {
        $purchaseIds = collect($config['purchase_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($purchaseIds === []) {
            throw new RuntimeException('purchase_ids wajib diisi.');
        }

        $targetUnitPrice = round((float) ($config['target_unit_price'] ?? 0), 2);

        if ($targetUnitPrice <= 0) {
            throw new RuntimeException('target_unit_price harus lebih besar dari 0.');
        }

        return [
            'purchase_ids' => $purchaseIds,
            'product_id' => (int) ($config['product_id'] ?? 0),
            'outlet_id' => (int) ($config['outlet_id'] ?? 0),
            'target_unit_price' => $targetUnitPrice,
            'backup_dir' => $config['backup_dir'] ?? null,
        ];
    }

    private function sliceDownstreamMutations(Collection $mutations, int $firstTargetMutationId): Collection
    {
        $downstreamMutations = collect();
        $started = false;

        foreach ($mutations as $mutation) {
            if (!$started && (int) $mutation->id === $firstTargetMutationId) {
                $started = true;
            }

            if (!$started) {
                continue;
            }

            $downstreamMutations->push($mutation);
        }

        return $downstreamMutations;
    }
}
