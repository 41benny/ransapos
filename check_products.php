<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\BomHeader;

echo "=== ANALISIS PRODUK ===\n\n";

// Total produk
$totalProducts = Product::count();
echo "Total Produk: {$totalProducts}\n\n";

// Produk berdasarkan tipe
echo "Produk berdasarkan Tipe:\n";
$productsByType = Product::selectRaw('product_type, COUNT(*) as count')
    ->groupBy('product_type')
    ->get();

foreach ($productsByType as $type) {
    echo "  - {$type->product_type}: {$type->count}\n";
}

echo "\n";

// Produk dengan BOM
$productsWithBom = Product::whereHas('bomHeader')->count();
echo "Produk dengan BOM: {$productsWithBom}\n";

// Produk tanpa BOM (kecuali raw_material)
$productsWithoutBom = Product::whereDoesntHave('bomHeader')
    ->where('product_type', '!=', 'raw_material')
    ->count();
echo "Produk tanpa BOM (bukan bahan baku): {$productsWithoutBom}\n\n";

// Detail produk tanpa BOM
echo "=== DETAIL PRODUK TANPA BOM (BUKAN BAHAN BAKU) ===\n\n";
$productsNoBom = Product::whereDoesntHave('bomHeader')
    ->where('product_type', '!=', 'raw_material')
    ->get(['id', 'name', 'product_type', 'sku']);

if ($productsNoBom->count() > 0) {
    foreach ($productsNoBom as $product) {
        echo "ID: {$product->id} | SKU: {$product->sku} | Nama: {$product->name} | Tipe: {$product->product_type}\n";
    }
} else {
    echo "Tidak ada produk tanpa BOM (selain bahan baku)\n";
}

echo "\n=== REKOMENDASI ===\n\n";

if ($productsWithoutBom > 0) {
    echo "Ada {$productsWithoutBom} produk yang bukan bahan baku tetapi tidak memiliki BOM.\n";
    echo "Ini bisa menjadi:\n";
    echo "1. Bundle yang belum dibuatkan resepnya\n";
    echo "2. Produk jadi yang seharusnya punya BOM\n";
    echo "3. Produk yang salah dikategorikan\n\n";
    echo "OPSI TINDAKAN:\n";
    echo "A. Hapus semua produk di atas (kecuali yang sudah ada transaksi)\n";
    echo "B. Ubah tipe produk menjadi 'raw_material' jika memang bahan baku\n";
    echo "C. Buatkan BOM untuk produk-produk tersebut\n";
} else {
    echo "Semua produk sudah konsisten. Tidak ada masalah.\n";
}
