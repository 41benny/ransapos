<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== CEK SETTING VISIBILITY PRODUK ===\n\n";

$products = Product::all();

foreach ($products as $product) {
    echo "ID: {$product->id} | {$product->name}\n";
    echo "  - is_active: " . ($product->is_active ? '✅ Ya' : '❌ Tidak') . "\n";
    echo "  - is_sellable: " . ($product->is_sellable ? '✅ Ya' : '❌ Tidak') . "\n";
    echo "  - is_pos_available: " . ($product->is_pos_available ? '✅ Ya' : '❌ Tidak') . "\n";
    echo "  - product_type: {$product->product_type}\n";

    $showInPOS = $product->is_active
        && $product->is_sellable
        && $product->is_pos_available
        && in_array($product->product_type, ['finished_good', 'service']);

    echo "  - MUNCUL DI POS: " . ($showInPOS ? '✅ YA' : '❌ TIDAK') . "\n\n";
}

echo "\n=== KESIMPULAN ===\n";
$visibleInPOS = Product::where('is_active', true)
    ->where('is_sellable', true)
    ->where('is_pos_available', true)
    ->whereIn('product_type', ['finished_good', 'service'])
    ->count();

echo "Produk yang MUNCUL di POS: {$visibleInPOS}\n";
echo "Total Produk: " . Product::count() . "\n";
