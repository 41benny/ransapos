<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Stock;
use App\Models\SaleItem;
use App\Models\PurchaseItem;
use App\Models\StockMutation;

echo "=== CEK PENGGUNAAN PRODUK ===\n\n";

$products = Product::all();

foreach ($products as $product) {
    echo "ID: {$product->id} | {$product->name} ({$product->sku})\n";

    $stockCount = Stock::where('product_id', $product->id)->count();
    $saleCount = SaleItem::where('product_id', $product->id)->count();
    $purchaseCount = PurchaseItem::where('product_id', $product->id)->count();
    $mutationCount = StockMutation::where('product_id', $product->id)->count();

    echo "  - Stock records: {$stockCount}\n";
    echo "  - Penjualan: {$saleCount}\n";
    echo "  - Pembelian: {$purchaseCount}\n";
    echo "  - Mutasi: {$mutationCount}\n";

    $hasTransaction = ($stockCount + $saleCount + $purchaseCount + $mutationCount) > 0;

    if ($hasTransaction) {
        echo "  ⚠️  SUDAH ADA TRANSAKSI - JANGAN DIHAPUS!\n";
    } else {
        echo "  ✅ AMAN DIHAPUS - Belum ada transaksi\n";
    }

    echo "\n";
}

echo "\n=== KESIMPULAN ===\n";
$safeToDelete = Product::whereDoesntHave('stocks')
    ->whereDoesntHave('saleItems')
    ->whereDoesntHave('purchaseItems')
    ->whereDoesntHave('stockMutations')
    ->count();

echo "Produk yang AMAN dihapus (belum ada transaksi): {$safeToDelete}\n";
echo "Produk yang TIDAK BOLEH dihapus (sudah ada transaksi): " . (Product::count() - $safeToDelete) . "\n";
