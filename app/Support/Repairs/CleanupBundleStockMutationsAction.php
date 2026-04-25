<?php

namespace App\Support\Repairs;

use App\Models\StockMutation;
use RuntimeException;

class CleanupBundleStockMutationsAction
{
    public function execute(array $config, bool $apply = false): array
    {
        $config = $this->normalizeConfig($config);
        $timestamp = now()->format('Ymd_His');
        $backupDir = $config['backup_dir'] ?: storage_path('app/private/repairs');

        if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
            throw new RuntimeException('Gagal membuat direktori backup cleanup mutasi bundle.');
        }

        $backupPath = $backupDir . DIRECTORY_SEPARATOR . "bundle_stock_mutations_cleanup_{$timestamp}.json";

        $query = StockMutation::query()
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

        $mutations = $query->get();

        $rows = $mutations->map(function (StockMutation $mutation): array {
            return [
                'mutation_id' => (int) $mutation->id,
                'product_id' => (int) $mutation->product_id,
                'product_name' => (string) ($mutation->product->name ?? '-'),
                'product_sku' => (string) ($mutation->product->sku ?? '-'),
                'outlet_id' => (int) $mutation->outlet_id,
                'outlet_name' => (string) ($mutation->outlet->name ?? '-'),
                'mutation_type' => (string) $mutation->mutation_type,
                'reference_type' => (string) ($mutation->reference_type ?? ''),
                'reference_id' => $mutation->reference_id !== null ? (int) $mutation->reference_id : null,
                'quantity' => (float) $mutation->quantity,
                'stock_after' => (float) ($mutation->stock_after ?? 0),
                'notes' => (string) ($mutation->notes ?? ''),
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
        foreach ($mutations as $mutation) {
            $mutation->delete();
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
