<?php

namespace App\Support\Repairs;

use App\Models\Stock;
use RuntimeException;

class CleanupBundleStockRecordsAction
{
    public function execute(array $config, bool $apply = false): array
    {
        $config = $this->normalizeConfig($config);
        $timestamp = now()->format('Ymd_His');
        $backupDir = $config['backup_dir'] ?: storage_path('app/private/repairs');

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Gagal membuat direktori backup cleanup stok bundle.');
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "bundle_stock_cleanup_{$timestamp}.json";

        $query = Stock::query()
            ->with(['product', 'outlet'])
            ->whereHas('product', fn ($q) => $q->whereHas('bomHeader'))
            ->when($config['outlet_id'] !== null, fn ($q) => $q->where('outlet_id', $config['outlet_id']))
            ->when($config['product_like'] !== null, function ($q) use ($config) {
                $q->whereHas('product', function ($productQuery) use ($config) {
                    $productQuery->where('name', 'like', '%' . $config['product_like'] . '%')
                        ->orWhere('sku', 'like', '%' . $config['product_like'] . '%');
                });
            })
            ->orderBy('product_id')
            ->orderBy('outlet_id')
            ->orderBy('id');

        $stocks = $query->get();

        $rows = $stocks->map(function (Stock $stock): array {
            return [
                'stock_id' => (int) $stock->id,
                'product_id' => (int) $stock->product_id,
                'product_name' => (string) ($stock->product->name ?? '-'),
                'product_sku' => (string) ($stock->product->sku ?? '-'),
                'outlet_id' => (int) $stock->outlet_id,
                'outlet_name' => (string) ($stock->outlet->name ?? '-'),
                'quantity' => (float) $stock->quantity,
            ];
        })->values()->all();

        $summary = [
            'target_count' => count($rows),
            'total_quantity' => round(array_sum(array_map(fn (array $row) => $row['quantity'], $rows)), 2),
        ];

        file_put_contents($backupPath, json_encode([
            'meta' => [
                'created_at' => now()->toDateTimeString(),
                'apply' => $apply,
                'backup_path' => $backupPath,
                'config' => $config,
            ],
            'plan' => [
                'rows' => $rows,
                'summary' => $summary,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (!$apply) {
            return [
                'status' => 'dry-run',
                'backup_path' => $backupPath,
                'summary' => $summary,
                'first_row' => $rows[0] ?? null,
            ];
        }

        $deleted = 0;
        foreach ($stocks as $stock) {
            $stock->delete();
            $deleted++;
        }

        return [
            'status' => 'applied',
            'backup_path' => $backupPath,
            'summary' => $summary + ['deleted_count' => $deleted],
            'first_row' => $rows[0] ?? null,
        ];
    }

    private function normalizeConfig(array $config): array
    {
        return [
            'outlet_id' => isset($config['outlet_id']) && $config['outlet_id'] !== ''
                ? (int) $config['outlet_id']
                : null,
            'product_like' => isset($config['product_like']) && trim((string) $config['product_like']) !== ''
                ? trim((string) $config['product_like'])
                : null,
            'backup_dir' => $config['backup_dir'] ?? null,
        ];
    }
}
