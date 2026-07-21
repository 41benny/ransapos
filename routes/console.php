<?php

use App\Models\ActivityLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use App\Support\Repairs\RepairPurchaseHppByQuantityAction;
use App\Support\Repairs\RepairSaleItemCogsFromStockAction;
use App\Support\Repairs\RepairSaleItemCogsFromProductsAction;
use App\Support\Repairs\CleanupBundleStockRecordsAction;
use App\Support\Repairs\CleanupBundleStockMutationsAction;
use App\Services\BackdateSaleService;
use App\Services\CashAccountService;
use App\Services\StockService;
use App\Models\CashSession;
use App\Models\CashSessionPackagingClosing;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMutation;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('products:generate-thumbnails {--force : Regenerate even if thumbnail already exists} {--dry-run : Show impact without writing files}', function () {
    $isForce = (bool) $this->option('force');
    $isDryRun = (bool) $this->option('dry-run');

    if (!extension_loaded('gd')) {
        $this->error('GD extension tidak aktif. Thumbnail tidak bisa dibuat.');
        return self::FAILURE;
    }

    $query = Product::query()
        ->whereNotNull('image_path')
        ->where('image_path', '<>', '');

    if (!$isForce) {
        $query->where(function ($inner) {
            $inner->whereNull('thumbnail_path')
                ->orWhere('thumbnail_path', '');
        });
    }

    $products = $query->orderBy('id')->get(['id', 'name', 'image_path', 'thumbnail_path']);
    $total = $products->count();

    if ($total === 0) {
        $this->info('Tidak ada produk yang perlu diproses.');
        return self::SUCCESS;
    }

    $this->info(sprintf(
        'Memproses %d produk (force=%s, dry-run=%s)',
        $total,
        $isForce ? 'yes' : 'no',
        $isDryRun ? 'yes' : 'no'
    ));

    $disk = Storage::disk('public');
    $processed = 0;
    $failed = 0;
    $written = 0;

    foreach ($products as $product) {
        $processed++;
        $sourceAbsolutePath = $disk->path($product->image_path);

        if (!is_file($sourceAbsolutePath)) {
            $failed++;
            $this->warn("[$processed/$total] Skip #{$product->id} {$product->name}: file image tidak ditemukan.");
            continue;
        }

        $filename = pathinfo($product->image_path, PATHINFO_FILENAME);
        $thumbnailPath = 'products/thumbnails/' . $filename . '_thumb.jpg';

        if ($isDryRun) {
            $this->line("[$processed/$total] DRY-RUN #{$product->id} -> {$thumbnailPath}");
            continue;
        }

        $imageData = @file_get_contents($sourceAbsolutePath);
        if ($imageData === false) {
            $failed++;
            $this->warn("[$processed/$total] Gagal baca file #{$product->id}.");
            continue;
        }

        $sourceImage = @imagecreatefromstring($imageData);
        if ($sourceImage === false) {
            $failed++;
            $this->warn("[$processed/$total] Format gambar tidak didukung #{$product->id}.");
            continue;
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($sourceImage);
            $failed++;
            $this->warn("[$processed/$total] Dimensi gambar invalid #{$product->id}.");
            continue;
        }

        $thumbSize = 360;
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = (int) floor(($sourceWidth - $cropSize) / 2);
        $cropY = (int) floor(($sourceHeight - $cropSize) / 2);

        $thumbnailImage = imagecreatetruecolor($thumbSize, $thumbSize);
        $background = imagecolorallocate($thumbnailImage, 255, 255, 255);
        imagefill($thumbnailImage, 0, 0, $background);

        imagecopyresampled(
            $thumbnailImage,
            $sourceImage,
            0,
            0,
            $cropX,
            $cropY,
            $thumbSize,
            $thumbSize,
            $cropSize,
            $cropSize
        );

        $thumbnailAbsolutePath = $disk->path($thumbnailPath);
        $thumbnailDir = dirname($thumbnailAbsolutePath);
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $ok = @imagejpeg($thumbnailImage, $thumbnailAbsolutePath, 82);
        imagedestroy($thumbnailImage);
        imagedestroy($sourceImage);

        if (!$ok) {
            $failed++;
            Log::warning('Failed to write product thumbnail from artisan command', [
                'product_id' => $product->id,
                'image_path' => $product->image_path,
                'thumbnail_path' => $thumbnailPath,
            ]);
            $this->warn("[$processed/$total] Gagal menulis thumbnail #{$product->id}.");
            continue;
        }

        $product->thumbnail_path = $thumbnailPath;
        $product->save();
        $written++;
        $this->info("[$processed/$total] OK #{$product->id} -> {$thumbnailPath}");
    }

    $this->newLine();
    $this->info("Selesai. Total: {$total}, berhasil: {$written}, gagal: {$failed}" . ($isDryRun ? ' (dry-run)' : ''));

    return $failed > 0 ? self::FAILURE : self::SUCCESS;
})->purpose('Generate thumbnail produk untuk image lama yang belum punya thumbnail_path');

Artisan::command('balances:recalculate {--cash : Hitung ulang saldo kas/bank saja} {--stock : Hitung ulang mutasi stok saja}', function () {
    $cashOnly = (bool) $this->option('cash');
    $stockOnly = (bool) $this->option('stock');
    $runCash = $cashOnly || (!$cashOnly && !$stockOnly);
    $runStock = $stockOnly || (!$cashOnly && !$stockOnly);

    if ($runCash) {
        $this->info('Recalculate saldo kas/bank dimulai...');
        app(CashAccountService::class)->recalculateAllBalances();
        $this->info('Saldo kas/bank selesai dihitung ulang.');
    }

    if ($runStock) {
        $this->info('Recalculate mutasi stok dimulai...');
        app(StockService::class)->recalculateAllMutationBalances();
        $this->info('Mutasi stok selesai dihitung ulang.');
    }

    $this->newLine();
    $this->info('Recalculate selesai.');

    return self::SUCCESS;
})->purpose('Hitung ulang saldo historis kas/bank dan mutasi stok');

Artisan::command('backdate-sales:purge
    {--apply : Terapkan penghapusan ke database}
    {--date-from= : Filter tanggal awal sale_date, format YYYY-MM-DD}
    {--date-to= : Filter tanggal akhir sale_date, format YYYY-MM-DD}
    {--outlet-id= : Filter outlet ID tertentu}
    {--keep-sessions : Jangan hapus cash session backdate yang sudah kosong}', function (StockService $stockService) {
    $apply = (bool) $this->option('apply');
    $dateFrom = trim((string) $this->option('date-from'));
    $dateTo = trim((string) $this->option('date-to'));
    $outletId = trim((string) $this->option('outlet-id'));

    $saleQuery = Sale::query()
        ->where('is_backdated', true)
        ->when($dateFrom !== '', fn ($query) => $query->whereDate('sale_date', '>=', $dateFrom))
        ->when($dateTo !== '', fn ($query) => $query->whereDate('sale_date', '<=', $dateTo))
        ->when($outletId !== '', fn ($query) => $query->where('outlet_id', (int) $outletId));

    $summary = (clone $saleQuery)
        ->selectRaw('COUNT(*) as total_sales, COALESCE(SUM(total_amount), 0) as total_amount')
        ->first();

    $saleIds = (clone $saleQuery)->pluck('id');
    $sessionIds = (clone $saleQuery)
        ->whereNotNull('cash_session_id')
        ->pluck('cash_session_id')
        ->unique()
        ->values();

    $stockPairs = StockMutation::query()
        ->whereIn('reference_id', $saleIds)
        ->whereIn('reference_type', ['sale', 'sale_cancellation'])
        ->select('product_id', 'outlet_id', DB::raw('MIN(mutation_date) as first_mutation_date'), DB::raw('COUNT(*) as total_mutations'))
        ->groupBy('product_id', 'outlet_id')
        ->orderBy('product_id')
        ->orderBy('outlet_id')
        ->get();

    $stockMutationCount = (int) $stockPairs->sum('total_mutations');

    $this->line(json_encode([
        'mode' => $apply ? 'apply' : 'dry-run',
        'filters' => [
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'outlet_id' => $outletId !== '' ? (int) $outletId : null,
        ],
        'backdate_sales' => (int) ($summary->total_sales ?? 0),
        'backdate_total_amount' => (float) ($summary->total_amount ?? 0),
        'cash_sessions_affected' => $sessionIds->count(),
        'stock_mutations_to_delete' => $stockMutationCount,
        'stock_pairs_to_recalculate' => $stockPairs->count(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($saleIds->isEmpty()) {
        $this->info('Tidak ada transaksi backdate yang cocok dengan filter.');

        return self::SUCCESS;
    }

    if (! $apply) {
        $this->warn('Dry-run saja. Jalankan lagi dengan --apply untuk benar-benar menghapus.');

        return self::SUCCESS;
    }

    DB::transaction(function () use ($saleIds, $sessionIds, $stockPairs, $stockService): void {
        $customerRollbacks = Sale::query()
            ->whereIn('id', $saleIds)
            ->whereNotNull('customer_id')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as total_transactions'),
                DB::raw('COALESCE(SUM(total_amount), 0) as total_spending'),
                DB::raw('COALESCE(SUM(loyalty_points_earned), 0) as loyalty_points')
            )
            ->groupBy('customer_id')
            ->get();

        $voucherRollbacks = Sale::query()
            ->whereIn('id', $saleIds)
            ->whereNotNull('voucher_id')
            ->select('voucher_id', DB::raw('COUNT(*) as used_count'))
            ->groupBy('voucher_id')
            ->get();

        StockMutation::query()
            ->whereIn('reference_id', $saleIds)
            ->whereIn('reference_type', ['sale', 'sale_cancellation'])
            ->delete();

        Sale::query()
            ->whereIn('id', $saleIds)
            ->delete();

        foreach ($sessionIds as $sessionId) {
            $totalSales = (float) DB::table('sales')
                ->where('cash_session_id', $sessionId)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $paymentTotals = DB::table('payments')
                ->join('sales', 'sales.id', '=', 'payments.sale_id')
                ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
                ->where('sales.cash_session_id', $sessionId)
                ->where('sales.status', '!=', 'cancelled')
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code = 'CASH' OR payments.payment_method_id = 1 THEN payments.amount ELSE 0 END), 0) as total_cash")
                ->selectRaw("COALESCE(SUM(CASE WHEN payment_methods.code <> 'CASH' AND payments.payment_method_id <> 1 THEN payments.amount ELSE 0 END), 0) as total_non_cash")
                ->first();

            $session = CashSession::query()->find($sessionId);
            if (! $session) {
                continue;
            }

            $session->total_sales = $totalSales;
            $session->total_cash = (float) ($paymentTotals->total_cash ?? 0);
            $session->total_non_cash = (float) ($paymentTotals->total_non_cash ?? 0);
            $session->expected_balance = (float) $session->opening_balance + (float) $session->total_cash;
            $session->save();
        }

        foreach ($customerRollbacks as $rollback) {
            $customer = Customer::query()->find($rollback->customer_id);
            if (! $customer) {
                continue;
            }

            $customer->loyalty_points = max(0, (int) $customer->loyalty_points - (int) $rollback->loyalty_points);
            $customer->total_spending = max(0, (float) $customer->total_spending - (float) $rollback->total_spending);
            $customer->total_transactions = max(0, (int) $customer->total_transactions - (int) $rollback->total_transactions);
            $customer->last_visit = Sale::query()
                ->where('customer_id', $customer->id)
                ->where('status', '!=', 'cancelled')
                ->latest('sale_date')
                ->value('sale_date');
            $customer->save();
            $customer->updateMemberTier();
        }

        foreach ($voucherRollbacks as $rollback) {
            $voucher = Voucher::query()->find($rollback->voucher_id);
            if (! $voucher) {
                continue;
            }

            $voucher->used_count = max(0, (int) $voucher->used_count - (int) $rollback->used_count);
            $voucher->save();
        }

        foreach ($stockPairs as $pair) {
            $stockService->recalculateMutationBalances(
                (int) $pair->product_id,
                (int) $pair->outlet_id,
                (string) $pair->first_mutation_date
            );
        }
    });

    $deletedSessions = 0;
    if (! (bool) $this->option('keep-sessions')) {
        $deletedSessions = CashSession::query()
            ->whereIn('id', $sessionIds)
            ->where('session_type', 'backdate_correction')
            ->whereDoesntHave('sales')
            ->delete();
    }

    $this->info("Selesai hapus {$saleIds->count()} transaksi backdate.");
    $this->info("Cash session backdate kosong yang dihapus: {$deletedSessions}.");

    return self::SUCCESS;
})->purpose('Dry-run/apply hapus semua input penjualan backdate beserta mutasi stok terkait');

/**
 * Pindahkan penjualan backdate dari sesi koreksi sintetis (CS-BACKDATE-*) ke shift kasir yang
 * benar-benar berjalan pada tanggal tersebut. Selama transaksi backdate menempel di sesi
 * terpisah, pemakaian packaging-nya tidak pernah terhitung di shift mana pun sehingga laporan
 * selisih cup/box selalu meleset.
 *
 * Tanpa --apply, seluruh perubahan dijalankan lalu di-rollback, jadi angka yang ditampilkan
 * adalah hasil sungguhan dan bukan perkiraan.
 */
Artisan::command('backdate-sales:migrate-to-shift
    {--apply : Terapkan perpindahan ke database}
    {--outlet-id= : Filter outlet ID tertentu}
    {--map=* : Tujuan untuk tanggal ambigu, format sesiBackdateId:shiftTujuanId}
    {--keep-sessions : Jangan tandai cash session backdate yang sudah kosong sebagai closed}', function (BackdateSaleService $backdateSaleService) {
    $apply = (bool) $this->option('apply');
    $outletId = trim((string) $this->option('outlet-id'));

    $overrides = [];
    foreach ((array) $this->option('map') as $pair) {
        if (! preg_match('/^(\d+):(\d+)$/', trim((string) $pair), $matches)) {
            $this->error("Format --map salah: {$pair}. Gunakan sesiBackdateId:shiftTujuanId.");

            return self::FAILURE;
        }

        $overrides[(int) $matches[1]] = (int) $matches[2];
    }

    $backdateSessions = CashSession::query()
        ->where('session_type', 'backdate_correction')
        ->when($outletId !== '', fn ($query) => $query->where('outlet_id', (int) $outletId))
        ->orderBy('id')
        ->get();

    if ($backdateSessions->isEmpty()) {
        $this->info('Tidak ada sesi backdate yang perlu dipindah.');

        return self::SUCCESS;
    }

    $moves = [];
    $planRows = [];
    $targetIds = [];

    foreach ($backdateSessions as $session) {
        $sales = Sale::query()
            ->with('payments')
            ->where('cash_session_id', $session->id)
            ->orderBy('id')
            ->get();

        if ($sales->isEmpty()) {
            continue;
        }

        // `business_date` hanya terisi pada sesi backdate; sesi reguler dari POS membiarkannya
        // NULL, jadi pencocokan shift memakai tanggal `opened_at`.
        $date = $session->business_date?->toDateString() ?? $session->opened_at->toDateString();

        $candidates = CashSession::query()
            ->with('user:id,name')
            ->where('outlet_id', $session->outlet_id)
            ->where('session_type', '!=', 'backdate_correction')
            ->whereDate('opened_at', $date)
            ->orderBy('opened_at')
            ->get();

        $target = null;

        if (isset($overrides[$session->id])) {
            $target = $candidates->firstWhere('id', $overrides[$session->id]);
            $plan = $target
                ? 'dipilih manual -> #' . $target->id
                : 'TUJUAN --map TIDAK VALID untuk tanggal ini';
        } elseif ($candidates->count() === 1) {
            $target = $candidates->first();
            $plan = 'pindah -> #' . $target->id . ' (' . ($target->user?->name ?? '-') . ')';
        } elseif ($candidates->count() > 1) {
            $plan = 'AMBIGU, ' . $candidates->count() . ' shift: ' . $candidates->pluck('id')->implode(', ')
                . ' — pakai --map=' . $session->id . ':<id>';
        } else {
            $plan = 'DILEWATI, tidak ada shift di tanggal ini';
        }

        $planRows[] = [
            $session->id,
            $session->outlet_id,
            $date,
            $sales->count(),
            'Rp ' . number_format((float) $sales->sum('total_amount'), 0, ',', '.'),
            $plan,
        ];

        if (! $target) {
            continue;
        }

        $targetIds[] = $target->id;
        foreach ($sales as $sale) {
            $moves[] = ['sale' => $sale, 'target' => $target];
        }
    }

    $this->table(['Sesi backdate', 'Outlet', 'Tanggal', 'Sale', 'Nilai', 'Rencana'], $planRows);

    if (empty($moves)) {
        $this->warn('Tidak ada transaksi yang bisa dipindah otomatis.');

        return self::SUCCESS;
    }

    $snapshot = function () use ($targetIds) {
        if (empty($targetIds)) {
            return [];
        }

        return CashSessionPackagingClosing::query()
            ->with('packagingItem:id,name')
            ->whereIn('cash_session_id', array_unique($targetIds))
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->cash_session_id . '|' . ($row->packagingItem?->name ?? $row->packaging_item_id) => [
                    'estimated' => (float) $row->estimated_sales_used_qty,
                    'difference' => (float) $row->difference_qty,
                ],
            ])
            ->all();
    };

    $format = fn (float $value) => rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

    if (! $apply) {
        $this->comment('MODE SIMULASI — perubahan dijalankan lalu dibatalkan untuk melihat dampak nyata.');
    }

    DB::beginTransaction();

    try {
        $before = $snapshot();

        foreach ($moves as $move) {
            $backdateSaleService->moveSaleToSession($move['sale'], $move['target']);
        }

        $emptied = [];
        if (! (bool) $this->option('keep-sessions')) {
            foreach ($backdateSessions as $session) {
                if ($session->status === 'closed' || Sale::where('cash_session_id', $session->id)->exists()) {
                    continue;
                }

                $session->forceFill([
                    'status' => 'closed',
                    'closed_at' => now(),
                    'actual_balance' => 0,
                    'difference' => 0,
                    'notes' => trim(($session->notes ? $session->notes . "\n" : '')
                        . 'Dikosongkan oleh backdate-sales:migrate-to-shift, transaksi dipindah ke shift asli.'),
                ])->save();

                $emptied[] = '#' . $session->id;
            }
        }

        $after = $snapshot();
        $diffRows = [];

        foreach ($after as $key => $now) {
            $was = $before[$key] ?? null;
            if ($was && $was['difference'] === $now['difference'] && $was['estimated'] === $now['estimated']) {
                continue;
            }

            [$sessionId, $itemName] = explode('|', $key, 2);
            $diffRows[] = [
                $sessionId,
                $itemName,
                $was ? $format($was['estimated']) . ' -> ' . $format($now['estimated']) : $format($now['estimated']),
                $was ? $format($was['difference']) . ' -> ' . $format($now['difference']) : $format($now['difference']),
            ];
        }

        if (empty($diffRows)) {
            $this->info('Tidak ada baris closing packaging yang berubah.');
        } else {
            $this->info('Perubahan closing packaging:');
            $this->table(['Shift', 'Item', 'Estimasi', 'Selisih'], $diffRows);
            $this->comment('Selisih negatif = terjual lebih banyak daripada packaging yang hilang dari rak, '
                . 'artinya hitungan fisik saat closing kemungkinan kelebihan.');
        }

        if ($emptied) {
            $this->info('Sesi backdate kosong yang ditandai closed: ' . implode(', ', $emptied));
        }

        if ($apply) {
            DB::commit();
            $this->info('Selesai. ' . count($moves) . ' transaksi dipindah.');
        } else {
            DB::rollBack();
            $this->comment('Simulasi dibatalkan — tidak ada data yang berubah.');
            $this->comment('Jalankan ulang dengan --apply bila hasil di atas sudah sesuai.');
        }
    } catch (\Throwable $e) {
        DB::rollBack();
        $this->error('Gagal: ' . $e->getMessage());

        return self::FAILURE;
    }

    return self::SUCCESS;
})->purpose('Dry-run/apply pindahkan penjualan backdate ke shift kasir aslinya dan hitung ulang packaging');

Artisan::command('repair:purchase-hpp-by-qty 
    {--apply : Terapkan perubahan ke database}
    {--purchase-ids=112,113 : ID purchase target, pisahkan dengan koma}
    {--product-id=156 : Product ID target}
    {--outlet-id=6 : Outlet ID target}
    {--unit-price=16.3 : Harga per unit yang benar}', function (RepairPurchaseHppByQuantityAction $action) {
    $purchaseIds = collect(explode(',', (string) $this->option('purchase-ids')))
        ->map(fn (string $value) => (int) trim($value))
        ->filter(fn (int $value) => $value > 0)
        ->values()
        ->all();

    try {
        $result = $action->execute([
            'purchase_ids' => $purchaseIds,
            'product_id' => (int) $this->option('product-id'),
            'outlet_id' => (int) $this->option('outlet-id'),
            'target_unit_price' => (float) $this->option('unit-price'),
        ], (bool) $this->option('apply'));

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Dry-run/apply koreksi HPP purchase yang salah qty lalu sinkronkan mutasi stok turunannya');

Artisan::command('repair:sale-item-cogs-from-stock
    {--apply : Terapkan perubahan ke database}
    {--outlet-id=6 : Outlet ID target}
    {--date-from=2026-03-27 : Tanggal awal sale}
    {--date-to=2026-04-04 : Tanggal akhir sale}', function (RepairSaleItemCogsFromStockAction $action) {
    try {
        $result = $action->execute([
            'outlet_id' => $this->option('outlet-id'),
            'date_from' => (string) $this->option('date-from'),
            'date_to' => (string) $this->option('date-to'),
        ], (bool) $this->option('apply'));

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Dry-run/apply sinkronisasi sale_items.cogs dari mutasi stok penjualan');

Artisan::command('repair:sale-item-cogs-from-products
    {--apply : Terapkan perubahan ke database}
    {--outlet-id= : Outlet ID target, kosongkan untuk semua outlet}
    {--date-from=2026-01-01 : Tanggal awal sale}
    {--date-to=2026-12-31 : Tanggal akhir sale}
    {--product-like=Frozen : Filter nama/SKU produk, contoh Frozen}
    {--only-zero=1 : Hanya repair cogs yang kosong/nol}', function (RepairSaleItemCogsFromProductsAction $action) {
    try {
        $result = $action->execute([
            'outlet_id' => $this->option('outlet-id'),
            'date_from' => (string) $this->option('date-from'),
            'date_to' => (string) $this->option('date-to'),
            'product_like' => (string) $this->option('product-like'),
            'only_zero' => $this->option('only-zero'),
        ], (bool) $this->option('apply'));

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Dry-run/apply hitung ulang sale_items.cogs dari master produk dan BOM aktif');

Artisan::command('stocks:cleanup-bundle-records
    {--apply : Hapus record stok bundle dari database}
    {--outlet-id= : Outlet ID target, kosongkan untuk semua outlet}
    {--product-like= : Filter nama/SKU produk bundle}', function (CleanupBundleStockRecordsAction $action) {
    try {
        $result = $action->execute([
            'outlet_id' => $this->option('outlet-id'),
            'product_like' => (string) $this->option('product-like'),
        ], (bool) $this->option('apply'));

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Dry-run/apply hapus record stok yang terlanjur terbentuk untuk produk bundle/BOM');

Artisan::command('stocks:cleanup-bundle-mutations
    {--apply : Hapus mutasi stok bundle dari database}
    {--outlet-id= : Outlet ID target, kosongkan untuk semua outlet}
    {--product-like= : Filter nama/SKU produk bundle}', function (CleanupBundleStockMutationsAction $action) {
    try {
        $result = $action->execute([
            'outlet_id' => $this->option('outlet-id'),
            'product_like' => (string) $this->option('product-like'),
        ], (bool) $this->option('apply'));

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error($e->getMessage());

        return self::FAILURE;
    }
})->purpose('Dry-run/apply hapus mutasi historis yang terlanjur terbentuk untuk produk bundle/BOM');

/*
 * Log Aktivitas — hapus log lama.
 * Default: 90 hari. Bisa override dengan --days=180
 *
 * Contoh manual:
 *   php artisan activity-logs:prune
 *   php artisan activity-logs:prune --days=30
 */
Artisan::command('activity-logs:prune {--days=90 : Jumlah hari log yang disimpan}', function () {
    $days = max(1, (int) $this->option('days'));
    $cutoff = now()->subDays($days);

    $deleted = ActivityLog::query()
        ->where('created_at', '<', $cutoff)
        ->delete();

    $this->info("Log aktivitas lebih dari {$days} hari dihapus: {$deleted} baris.");
    Log::info("activity-logs:prune selesai", ['days' => $days, 'deleted' => $deleted]);

    return self::SUCCESS;
})->purpose('Hapus log aktivitas lama (default: lebih dari 90 hari)');

/*
 * Jadwal otomatis: hapus log aktivitas setiap hari jam 01.00
 */
Schedule::command('activity-logs:prune')->dailyAt('01:00');
