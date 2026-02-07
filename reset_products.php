<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\BomHeader;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  RESET SEMUA DATA PRODUK DAN TRANSAKSI TERKAIT             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "⚠️  PERINGATAN: Script ini akan menghapus:\n";
echo "   - Semua produk (" . Product::count() . " produk)\n";
echo "   - Semua penjualan (" . Sale::count() . " transaksi)\n";
echo "   - Semua pembelian (" . Purchase::count() . " transaksi)\n";
echo "   - Semua stock (" . Stock::count() . " records)\n";
echo "   - Semua BOM (" . BomHeader::count() . " BOM)\n";
echo "   - Dan semua data terkait lainnya\n\n";

echo "Apakah Anda yakin ingin melanjutkan? (ketik 'YA HAPUS SEMUA' untuk konfirmasi): ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'YA HAPUS SEMUA') {
    echo "\n❌ Dibatalkan. Tidak ada data yang dihapus.\n";
    exit(0);
}

echo "\n🔄 Memulai proses penghapusan...\n\n";

try {
    DB::beginTransaction();

    // Disable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    echo "1️⃣  Menghapus sale items...\n";
    DB::table('sale_items')->delete();

    echo "2️⃣  Menghapus sale payments...\n";
    DB::table('sale_payments')->delete();

    echo "3️⃣  Menghapus sales...\n";
    DB::table('sales')->delete();

    echo "4️⃣  Menghapus purchase items...\n";
    DB::table('purchase_items')->delete();

    echo "5️⃣  Menghapus purchase payments...\n";
    DB::table('purchase_payments')->delete();

    echo "6️⃣  Menghapus purchases...\n";
    DB::table('purchases')->delete();

    echo "7️⃣  Menghapus stock mutations...\n";
    DB::table('stock_mutations')->delete();

    echo "8️⃣  Menghapus stocks...\n";
    DB::table('stocks')->delete();

    echo "9️⃣  Menghapus BOM details...\n";
    DB::table('bom_details')->delete();

    echo "🔟 Menghapus BOM headers...\n";
    DB::table('bom_headers')->delete();

    echo "1️⃣1️⃣  Menghapus stock transfer items...\n";
    DB::table('stock_transfer_items')->delete();

    echo "1️⃣2️⃣  Menghapus stock transfers...\n";
    DB::table('stock_transfers')->delete();

    echo "1️⃣3️⃣  Menghapus products...\n";
    DB::table('products')->delete();

    echo "\n🔄 Reset auto increment...\n";
    $tables = [
        'sale_items',
        'sale_payments',
        'sales',
        'purchase_items',
        'purchase_payments',
        'purchases',
        'stock_mutations',
        'stocks',
        'bom_details',
        'bom_headers',
        'stock_transfer_items',
        'stock_transfers',
        'products'
    ];

    foreach ($tables as $table) {
        DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
    }

    // Re-enable foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    DB::commit();

    echo "\n✅ SELESAI! Semua data berhasil dihapus.\n\n";

    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  VERIFIKASI HASIL                                          ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";

    echo "Products: " . Product::count() . "\n";
    echo "Sales: " . Sale::count() . "\n";
    echo "Purchases: " . Purchase::count() . "\n";
    echo "Stocks: " . Stock::count() . "\n";
    echo "BOM Headers: " . BomHeader::count() . "\n\n";

    echo "✨ Database siap untuk import data baru!\n";
} catch (Exception $e) {
    DB::rollBack();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');

    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Rollback dilakukan, tidak ada data yang dihapus.\n";
    exit(1);
}
