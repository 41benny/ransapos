<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Models\Product;

// 1. EXECUTE RESET SQL (Driver Aware + Robust)
echo "--- Executing Database Reset ---\n";

try {
    $driver = DB::connection()->getDriverName();
    echo "Database Driver: {$driver}\n";

    // Disable Foreign Key Checks
    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF');
    } else { // mysql
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    }

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
        try {
            DB::table($table)->delete();

            // Reset Auto Increment
            if ($driver === 'sqlite') {
                DB::statement("DELETE FROM sqlite_sequence WHERE name = '{$table}'");
            } else {
                DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = 1");
            }
            echo "[OK] Cleared table: {$table}\n";
        } catch (\Exception $e) {
            echo "[WARN] Could not clear table '{$table}': " . $e->getMessage() . "\n";
        }
    }

    // Enable Foreign Key Checks
    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = ON');
    } else {
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    echo "[OK] Database reset process finished.\n";

    // Verification
    echo "Products count: " . Product::count() . "\n";
} catch (\Exception $e) {
    echo "[ERROR] Error during reset process: " . $e->getMessage() . "\n";
}

// 2. GENERATE EXCEL TEMPLATE
echo "\n--- Generating Excel Template ---\n";

try {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set Headers
    $headers = [
        'A1' => 'nama_produk',
        'B1' => 'sku',
        'C1' => 'kategori',
        'D1' => 'jenis_produk',
        'E1' => 'satuan',
        'F1' => 'harga_beli',
        'G1' => 'harga_jual',
        'H1' => 'stok',
        'I1' => 'deskripsi',
        'J1' => 'bom_komponen_sku',
        'K1' => 'bom_komponen_nama',
        'L1' => 'bom_qty',
        'M1' => 'bom_uom',
    ];

    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getColumnDimension(substr($cell, 0, 1))->setAutoSize(true);
    }

    // Add Instructions / Comments
    $sheet->getComment('A1')->getText()->createTextRun("Wajib diisi\nContoh: Kopi Susu");
    $sheet->getComment('B1')->getText()->createTextRun("Opsional\nJika kosong, akan auto-generate.\nKhusus baris BOM: SKU wajib diisi.");
    $sheet->getComment('C1')->getText()->createTextRun("Contoh: Minuman, Makanan, Bahan Baku");
    $sheet->getComment('D1')->getText()->createTextRun("PENTING! Isi dengan:\n- 'bahan' (untuk bahan baku)\n- 'produk' (untuk dijual)\n- 'bundle' (untuk menu/bundle)\n- 'jasa' (untuk layanan)\nJika kosong = produk");
    $sheet->getComment('J1')->getText()->createTextRun("Opsional.\nIsi SKU bahan untuk resep bundle.\nBahan harus sudah ada (baris sebelumnya atau sudah ada di database).");
    $sheet->getComment('K1')->getText()->createTextRun("Opsional.\nNama bahan untuk resep bundle (dipakai jika SKU kosong).\nNama harus unik.");
    $sheet->getComment('L1')->getText()->createTextRun("Opsional.\nQty bahan per 1 produk bundle.\nJika isi BOM, nilai wajib > 0.");
    $sheet->getComment('M1')->getText()->createTextRun("Opsional.\nSatuan resep. Contoh: gram, ml, pcs.");

    // Add Sample Data (Row 2-3: Bahan Baku)
    $sheet->setCellValue('A2', 'Biji Kopi Arabica');
    $sheet->setCellValue('B2', 'RAW-KOPI-01');
    $sheet->setCellValue('C2', 'Bahan Baku');
    $sheet->setCellValue('D2', 'bahan');
    $sheet->setCellValue('E2', 'gram');
    $sheet->setCellValue('F2', '150');
    $sheet->setCellValue('G2', '0');
    $sheet->setCellValue('H2', '1000');
    $sheet->setCellValue('I2', 'Bahan baku kopi');

    $sheet->setCellValue('A3', 'Susu Fresh');
    $sheet->setCellValue('B3', 'RAW-SUSU-01');
    $sheet->setCellValue('C3', 'Bahan Baku');
    $sheet->setCellValue('D3', 'bahan');
    $sheet->setCellValue('E3', 'ml');
    $sheet->setCellValue('F3', '12');
    $sheet->setCellValue('G3', '0');
    $sheet->setCellValue('H3', '5000');
    $sheet->setCellValue('I3', 'Bahan baku susu');

    // Add Sample Data (Row 4-5: Bundle dengan 2 komponen resep)
    $sheet->setCellValue('A4', 'Es Kopi Susu');
    $sheet->setCellValue('B4', 'BND-ESKOPI-01');
    $sheet->setCellValue('C4', 'Minuman');
    $sheet->setCellValue('D4', 'bundle');
    $sheet->setCellValue('E4', 'cup');
    $sheet->setCellValue('F4', '0');
    $sheet->setCellValue('G4', '18000');
    $sheet->setCellValue('H4', '0');
    $sheet->setCellValue('I4', 'Bundle minuman kopi susu');
    $sheet->setCellValue('J4', 'RAW-KOPI-01');
    $sheet->setCellValue('L4', '18');
    $sheet->setCellValue('M4', 'gram');

    // Ulangi baris produk bundle yang sama untuk komponen berikutnya
    $sheet->setCellValue('A5', 'Es Kopi Susu');
    $sheet->setCellValue('B5', 'BND-ESKOPI-01');
    $sheet->setCellValue('C5', 'Minuman');
    $sheet->setCellValue('D5', 'bundle');
    $sheet->setCellValue('E5', 'cup');
    $sheet->setCellValue('F5', '0');
    $sheet->setCellValue('G5', '18000');
    $sheet->setCellValue('H5', '0');
    $sheet->setCellValue('I5', 'Bundle minuman kopi susu');
    $sheet->setCellValue('J5', 'RAW-SUSU-01');
    $sheet->setCellValue('L5', '120');
    $sheet->setCellValue('M5', 'ml');

    // Add Sample Data (Row 6: Produk non-bundle)
    $sheet->setCellValue('A6', 'Espresso');
    $sheet->setCellValue('B6', 'BEV-001');
    $sheet->setCellValue('C6', 'Minuman');
    $sheet->setCellValue('D6', 'produk');
    $sheet->setCellValue('E6', 'cup');
    $sheet->setCellValue('F6', '5000');
    $sheet->setCellValue('G6', '15000');
    $sheet->setCellValue('H6', '0');
    $sheet->setCellValue('I6', 'Single shot espresso');

    // Data Validation for 'jenis_produk' (Column D)
    $validation = $sheet->getCell('D2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"bahan,produk,bundle,jasa"');

    // Clone validation to rows 3-200
    for ($i = 3; $i <= 200; $i++) {
        $sheet->getCell("D$i")->setDataValidation(clone $validation);
    }

    // Make directory if not exists
    $path = __DIR__ . '/public/templates';
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }

    $writer = new Xlsx($spreadsheet);
    $filePath = $path . '/template_import_produk.xlsx';
    $writer->save($filePath);

    echo "[OK] Template generated at: {$filePath}\n";
} catch (\Exception $e) {
    echo "[ERROR] Error generating template: " . $e->getMessage() . "\n";
}


