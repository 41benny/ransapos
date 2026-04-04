<?php

namespace App\Support\Repairs;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMutation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepairSaleItemCogsFromStockAction
{
    public function execute(array $config, bool $apply = false): array
    {
        $config = $this->normalizeConfig($config);
        $timestamp = now()->format('Ymd_His');
        $backupDir = $config['backup_dir'] ?: storage_path('app/private/repairs');

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Gagal membuat direktori backup repair sale item.');
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "sale_item_cogs_repair_{$timestamp}.json";

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$config['date_from'], $config['date_to']])
            ->when(
                $config['outlet_id'] !== null,
                fn ($query) => $query->where('outlet_id', $config['outlet_id'])
            )
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get([
                'id',
                'invoice_number',
                'sale_date',
                'outlet_id',
                'subtotal',
                'total_amount',
            ]);

        if ($sales->isEmpty()) {
            throw new RuntimeException('Tidak ada sale completed pada scope repair.');
        }

        $saleIds = $sales->pluck('id')->all();
        $salesById = $sales->keyBy('id');

        $saleItems = SaleItem::query()
            ->whereIn('sale_id', $saleIds)
            ->orderBy('sale_id')
            ->orderBy('id')
            ->get();

        if ($saleItems->isEmpty()) {
            throw new RuntimeException('Tidak ada sale_items pada scope repair.');
        }

        $duplicateKeys = $saleItems
            ->groupBy(fn (SaleItem $item) => $this->makeKey((int) $item->sale_id, (string) $item->product_name))
            ->filter(fn (Collection $items) => $items->count() > 1);

        if ($duplicateKeys->isNotEmpty()) {
            throw new RuntimeException(
                'Repair dibatalkan karena ada sale_items duplikat untuk pasangan sale + product_name. ' .
                json_encode(
                    $duplicateKeys->map(function (Collection $items, string $key) {
                        /** @var SaleItem $first */
                        $first = $items->first();

                        return [
                            'key' => $key,
                            'sale_id' => (int) $first->sale_id,
                            'product_name' => (string) $first->product_name,
                            'sale_item_ids' => $items->pluck('id')->values()->all(),
                        ];
                    })->values()->all(),
                    JSON_UNESCAPED_UNICODE
                )
            );
        }

        $stockMutations = StockMutation::query()
            ->where('reference_type', 'sale')
            ->where('mutation_type', 'out')
            ->whereIn('reference_id', $saleIds)
            ->when(
                $config['outlet_id'] !== null,
                fn ($query) => $query->where('outlet_id', $config['outlet_id'])
            )
            ->orderBy('mutation_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $invalidMutationNotes = $stockMutations->filter(function (StockMutation $mutation) {
            return $this->extractProductNameFromNotes((string) $mutation->notes) === null;
        });

        if ($invalidMutationNotes->isNotEmpty()) {
            throw new RuntimeException(
                'Repair dibatalkan karena ada mutasi sale yang note-nya tidak bisa dipetakan ke nama menu. ' .
                json_encode(
                    $invalidMutationNotes->take(20)->map(fn (StockMutation $mutation) => [
                        'id' => $mutation->id,
                        'reference_id' => (int) $mutation->reference_id,
                        'notes' => $mutation->notes,
                    ])->values()->all(),
                    JSON_UNESCAPED_UNICODE
                )
            );
        }

        $mutationSums = $stockMutations
            ->groupBy(fn (StockMutation $mutation) => $this->makeKey(
                (int) $mutation->reference_id,
                (string) $this->extractProductNameFromNotes((string) $mutation->notes)
            ))
            ->map(fn (Collection $mutations) => round(
                $mutations->sum(fn (StockMutation $mutation) => abs((float) $mutation->total_cost)),
                2
            ));

        $saleItemKeys = $saleItems
            ->map(fn (SaleItem $item) => $this->makeKey((int) $item->sale_id, (string) $item->product_name))
            ->unique()
            ->values();

        $orphanMutationKeys = $mutationSums
            ->keys()
            ->filter(fn (string $key) => !$saleItemKeys->contains($key))
            ->values();

        if ($orphanMutationKeys->isNotEmpty()) {
            throw new RuntimeException(
                'Repair dibatalkan karena ada mutasi sale tanpa pasangan sale_item. ' .
                json_encode($orphanMutationKeys->take(20)->all(), JSON_UNESCAPED_UNICODE)
            );
        }

        $plannedUpdates = [];
        $unmatchedSaleItems = [];

        foreach ($saleItems as $item) {
            $key = $this->makeKey((int) $item->sale_id, (string) $item->product_name);
            $expectedCogs = $mutationSums->get($key);
            $currentCogs = round((float) $item->cogs, 2);

            if ($expectedCogs === null) {
                if (abs($currentCogs) > 0.01) {
                    $sale = $salesById->get($item->sale_id);
                    $unmatchedSaleItems[] = [
                        'sale_item_id' => $item->id,
                        'sale_id' => (int) $item->sale_id,
                        'invoice_number' => $sale?->invoice_number,
                        'product_name' => (string) $item->product_name,
                        'current_cogs' => $currentCogs,
                    ];
                }

                continue;
            }

            $delta = round($expectedCogs - $currentCogs, 2);

            if (abs($delta) <= 0.01) {
                continue;
            }

            $sale = $salesById->get($item->sale_id);

            $plannedUpdates[$item->id] = [
                'sale_item_id' => $item->id,
                'sale_id' => (int) $item->sale_id,
                'invoice_number' => $sale?->invoice_number,
                'sale_date' => $sale?->sale_date?->toDateString(),
                'outlet_id' => $sale?->outlet_id,
                'product_name' => (string) $item->product_name,
                'old_cogs' => $currentCogs,
                'new_cogs' => $expectedCogs,
                'delta' => $delta,
            ];
        }

        if (!empty($unmatchedSaleItems)) {
            throw new RuntimeException(
                'Repair dibatalkan karena ada sale_item mismatch tanpa mutasi stok pasangan. ' .
                json_encode(array_slice($unmatchedSaleItems, 0, 20), JSON_UNESCAPED_UNICODE)
            );
        }

        $plannedKeys = array_flip(array_map(
            fn (array $row) => $this->makeKey((int) $row['sale_id'], (string) $row['product_name']),
            array_values($plannedUpdates)
        ));

        $backupPayload = [
            'meta' => [
                'created_at' => now()->toDateTimeString(),
                'apply' => $apply,
                'backup_path' => $backupPath,
                'config' => $config,
            ],
            'before' => [
                'sales' => $sales->toArray(),
                'sale_items' => $saleItems
                    ->filter(fn (SaleItem $item) => isset($plannedUpdates[$item->id]))
                    ->values()
                    ->toArray(),
                'stock_mutations' => $stockMutations
                    ->filter(function (StockMutation $mutation) use ($plannedKeys) {
                        $key = $this->makeKey(
                            (int) $mutation->reference_id,
                            (string) $this->extractProductNameFromNotes((string) $mutation->notes)
                        );

                        return isset($plannedKeys[$key]);
                    })
                    ->values()
                    ->toArray(),
            ],
            'plan' => [
                'sale_item_updates' => array_values($plannedUpdates),
                'summary' => [
                    'sale_count' => $sales->count(),
                    'sale_item_count' => $saleItems->count(),
                    'stock_mutation_count' => $stockMutations->count(),
                    'target_update_count' => count($plannedUpdates),
                    'old_cogs_total' => round(array_sum(array_map(
                        fn (array $row) => $row['old_cogs'],
                        $plannedUpdates
                    )), 2),
                    'new_cogs_total' => round(array_sum(array_map(
                        fn (array $row) => $row['new_cogs'],
                        $plannedUpdates
                    )), 2),
                    'cogs_delta_total' => round(array_sum(array_map(
                        fn (array $row) => $row['delta'],
                        $plannedUpdates
                    )), 2),
                ],
            ],
        ];

        file_put_contents($backupPath, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            return [
                'status' => 'dry-run',
                'backup_path' => $backupPath,
                'summary' => $backupPayload['plan']['summary'],
                'first_update_example' => array_values($plannedUpdates)[0] ?? null,
            ];
        }

        $saleItemsById = $saleItems->keyBy('id');

        DB::transaction(function () use ($plannedUpdates, $saleItemsById): void {
            foreach ($plannedUpdates as $saleItemId => $plan) {
                /** @var SaleItem $item */
                $item = $saleItemsById->get($saleItemId);
                $item->cogs = $plan['new_cogs'];
                $item->save();
            }
        });

        return [
            'status' => 'applied',
            'backup_path' => $backupPath,
            'summary' => $backupPayload['plan']['summary'],
            'first_update_example' => array_values($plannedUpdates)[0] ?? null,
        ];
    }

    private function normalizeConfig(array $config): array
    {
        $dateFrom = (string) ($config['date_from'] ?? '');
        $dateTo = (string) ($config['date_to'] ?? '');

        if ($dateFrom === '' || $dateTo === '') {
            throw new RuntimeException('date_from dan date_to wajib diisi.');
        }

        if ($dateFrom > $dateTo) {
            throw new RuntimeException('date_from tidak boleh lebih besar dari date_to.');
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'outlet_id' => isset($config['outlet_id']) && $config['outlet_id'] !== ''
                ? (int) $config['outlet_id']
                : null,
            'backup_dir' => $config['backup_dir'] ?? null,
        ];
    }

    private function makeKey(int $saleId, string $productName): string
    {
        return $saleId . '|' . trim($productName);
    }

    private function extractProductNameFromNotes(string $notes): ?string
    {
        $prefix = 'Penjualan: ';

        if (!str_starts_with($notes, $prefix)) {
            return null;
        }

        $productName = trim(substr($notes, strlen($prefix)));

        return $productName !== '' ? $productName : null;
    }
}
