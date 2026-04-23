<?php

namespace App\Support\Repairs;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\CostService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepairSaleItemCogsFromProductsAction
{
    public function __construct(private readonly CostService $costService)
    {
    }

    public function execute(array $config, bool $apply = false): array
    {
        $config = $this->normalizeConfig($config);
        $timestamp = now()->format('Ymd_His');
        $backupDir = $config['backup_dir'] ?: storage_path('app/private/repairs');

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Gagal membuat direktori backup repair sale item.');
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "sale_item_cogs_from_products_{$timestamp}.json";

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$config['date_from'], $config['date_to']])
            ->when(
                $config['outlet_id'] !== null,
                fn ($query) => $query->where('outlet_id', $config['outlet_id'])
            )
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get(['id', 'invoice_number', 'sale_date', 'outlet_id']);

        if ($sales->isEmpty()) {
            throw new RuntimeException('Tidak ada sale completed pada scope repair.');
        }

        $saleItems = SaleItem::query()
            ->whereIn('sale_id', $sales->pluck('id'))
            ->when($config['only_zero'], fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('cogs')->orWhere('cogs', '<=', 0);
            }))
            ->when($config['product_like'] !== null, function ($query) use ($config) {
                $query->where(function ($inner) use ($config) {
                    $inner->where('product_name', 'like', '%' . $config['product_like'] . '%')
                        ->orWhere('product_sku', 'like', '%' . $config['product_like'] . '%');
                });
            })
            ->orderBy('sale_id')
            ->orderBy('id')
            ->get();

        if ($saleItems->isEmpty()) {
            $summary = [
                'sale_count' => $sales->count(),
                'sale_item_count' => 0,
                'target_update_count' => 0,
                'old_cogs_total' => 0.0,
                'new_cogs_total' => 0.0,
                'cogs_delta_total' => 0.0,
            ];

            file_put_contents($backupPath, json_encode([
                'meta' => [
                    'created_at' => now()->toDateTimeString(),
                    'apply' => $apply,
                    'backup_path' => $backupPath,
                    'config' => $config,
                ],
                'plan' => [
                    'sale_item_updates' => [],
                    'summary' => $summary,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return [
                'status' => $apply ? 'applied' : 'dry-run',
                'backup_path' => $backupPath,
                'summary' => $summary,
                'first_update_example' => null,
            ];
        }

        $salesById = $sales->keyBy('id');
        $products = Product::query()
            ->with(['bomHeader' => function ($query) {
                $query->where('is_active', true)->with('details.component');
            }])
            ->whereIn('id', $saleItems->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $plannedUpdates = [];
        $skipped = [];

        foreach ($saleItems as $item) {
            /** @var Product|null $product */
            $product = $products->get($item->product_id);
            $sale = $salesById->get($item->sale_id);

            if (!$product || !$sale) {
                $skipped[] = [
                    'sale_item_id' => (int) $item->id,
                    'sale_id' => (int) $item->sale_id,
                    'reason' => !$product ? 'product_not_found' : 'sale_not_found',
                ];
                continue;
            }

            $expectedCogs = round($this->costService->calculateItemCogs(
                $product,
                (float) $item->quantity,
                (int) $sale->outlet_id
            ), 2);

            if ($expectedCogs <= 0) {
                $skipped[] = [
                    'sale_item_id' => (int) $item->id,
                    'sale_id' => (int) $item->sale_id,
                    'invoice_number' => $sale->invoice_number,
                    'product_name' => (string) $item->product_name,
                    'reason' => 'calculated_cogs_zero',
                ];
                continue;
            }

            $currentCogs = round((float) $item->cogs, 2);
            $delta = round($expectedCogs - $currentCogs, 2);

            if (abs($delta) <= 0.01) {
                continue;
            }

            $plannedUpdates[$item->id] = [
                'sale_item_id' => (int) $item->id,
                'sale_id' => (int) $item->sale_id,
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date?->toDateString(),
                'outlet_id' => (int) $sale->outlet_id,
                'product_id' => (int) $item->product_id,
                'product_name' => (string) $item->product_name,
                'quantity' => (float) $item->quantity,
                'old_cogs' => $currentCogs,
                'new_cogs' => $expectedCogs,
                'delta' => $delta,
            ];
        }

        $summary = [
            'sale_count' => $sales->count(),
            'sale_item_count' => $saleItems->count(),
            'target_update_count' => count($plannedUpdates),
            'skipped_count' => count($skipped),
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
        ];

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
            ],
            'plan' => [
                'sale_item_updates' => array_values($plannedUpdates),
                'skipped' => $skipped,
                'summary' => $summary,
            ],
        ];

        file_put_contents($backupPath, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            return [
                'status' => 'dry-run',
                'backup_path' => $backupPath,
                'summary' => $summary,
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
            'summary' => $summary,
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
            'product_like' => isset($config['product_like']) && trim((string) $config['product_like']) !== ''
                ? trim((string) $config['product_like'])
                : null,
            'only_zero' => filter_var($config['only_zero'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'backup_dir' => $config['backup_dir'] ?? null,
        ];
    }
}
