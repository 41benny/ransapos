<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
    $addComment = function (string $cell, string $message) use ($sheet): void {
        $comment = $sheet->getComment($cell);
        $comment->getText()->createTextRun($message);
        $comment->setWidth('280pt');
        $comment->setHeight('140pt');
    };

    $addComment('A1', "nama_produk\n- WAJIB untuk produk baru.\n- Resep-only: boleh dipakai sebagai identitas bundle jika SKU tidak tahu.\n- Jika pakai nama, nama bundle harus unik di master produk.");
    $addComment('B1', "sku\n- Opsional.\n- Disarankan isi jika tahu (lebih aman).\n- Jika kosong: sistem cari produk dari nama_produk.\n- Jika nama duplikat, import ditolak.\nContoh: BND-MIE-JAWA / RAW-KOPI-01");
    $addComment('C1', "kategori\n- WAJIB untuk produk baru.\n- Update existing: opsional (boleh kosong).\nContoh: Maincourse, Minuman, Bahan Baku.");
    $addComment('D1', "jenis_produk\n- WAJIB untuk produk baru (disarankan).\n- Nilai valid: bahan | produk | bundle | jasa.\n- Jika kosong: default = produk (finished_good).");
    $addComment('E1', "satuan\n- WAJIB untuk produk baru.\n- Update existing: opsional (boleh kosong, pakai satuan lama).\nContoh: gram, ml, pcs, porsi, cup.");
    $addComment('F1', "harga_beli\n- Opsional.\n- Jika kosong saat update produk existing: harga beli lama dipertahankan.\n- Untuk bahan baru: isi harga dasar per satuan.");
    $addComment('G1', "harga_jual\n- Opsional.\n- Jika kosong saat update produk existing: harga jual lama dipertahankan.\n- Untuk bundle baru: jika kosong maka 0.\n- Jika 1 produk punya banyak baris BOM: isi harga_jual di BARIS PERTAMA saja, baris berikutnya kosong.");
    $addComment('H1', "stok\n- Opsional.\n- Saat ini tidak dipakai oleh importer produk.\n- Boleh isi 0.");
    $addComment('I1', "deskripsi\n- Opsional.\n- Jika kosong saat update existing: deskripsi lama dipertahankan.");
    $addComment('J1', "bom_komponen_sku\n- Opsional.\n- Untuk baris resep/BOM, isi J atau K.\n- Disarankan pakai J jika tahu SKU.\n- Komponen harus sudah ada di database/file.");
    $addComment('K1', "bom_komponen_nama\n- Alternatif jika SKU komponen tidak tahu.\n- Untuk baris resep/BOM, isi J atau K.\n- Jika pakai nama, nama komponen harus unik.\n- Jika nama duplikat, import ditolak.");
    $addComment('L1', "bom_qty\n- WAJIB untuk baris resep/BOM.\n- Nilai harus > 0.\nContoh: 160, 80, 18.");
    $addComment('M1', "bom_uom\n- Opsional.\n- Jika kosong, sistem pakai satuan komponen.\nContoh: gram, ml, pcs.");

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
    $sheet->setCellValue('G5', '');
    $sheet->setCellValue('H5', '0');
    $sheet->setCellValue('I5', 'Baris komponen lanjutan - harga_jual kosongkan');
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

    $sheet->freezePane('A2');

    // Sheet 2: Panduan wajib/opsional
    $guide = $spreadsheet->createSheet();
    $guide->setTitle('PETUNJUK_IMPORT');

    $guide->setCellValue('A1', 'PANDUAN IMPORT PRODUK & RESEP');
    $guide->mergeCells('A1:E1');
    $guide->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $guide->setCellValue('A3', 'MODE');
    $guide->setCellValue('B3', 'TUJUAN');
    $guide->setCellValue('C3', 'KOLOM WAJIB');
    $guide->setCellValue('D3', 'KOLOM OPSIONAL');
    $guide->setCellValue('E3', 'CATATAN');
    $guide->getStyle('A3:E3')->getFont()->setBold(true);

    $guide->setCellValue('A4', 'MASTER_ONLY');
    $guide->setCellValue('B4', 'Import produk tanpa resep/BOM');
    $guide->setCellValue('C4', 'A,C,D,E (B disarankan)');
    $guide->setCellValue('D4', 'F,G,H,I');
    $guide->setCellValue('E4', 'J,K,L,M dikosongkan');

    $guide->setCellValue('A5', 'MIXED');
    $guide->setCellValue('B5', 'Import produk + resep bundle');
    $guide->setCellValue('C5', 'A,B,C,D,E dan (J/K + L)');
    $guide->setCellValue('D5', 'F,G,H,I,M');
    $guide->setCellValue('E5', 'Baris bundle diulang per komponen');

    $guide->setCellValue('A6', 'RESEP_ONLY');
    $guide->setCellValue('B6', 'Upload resep untuk bundle existing');
    $guide->setCellValue('C6', '(A atau B) dan (J atau K) + L');
    $guide->setCellValue('D6', 'C,D,E,F,G,H,I,M');
    $guide->setCellValue('E6', 'Harga kosong aman; master existing dipertahankan');

    $guide->setCellValue('A8', 'RULE PENTING');
    $guide->getStyle('A8')->getFont()->setBold(true);
    $guide->setCellValue('A9', '1) Untuk baris resep: isi nama/SKU bundle (A/B) + SKU/Nama komponen (J/K) + qty (L).');
    $guide->setCellValue('A10', '2) Jika pakai nama komponen (K), namanya harus unik.');
    $guide->setCellValue('A11', '3) Qty resep harus lebih dari 0.');
    $guide->setCellValue('A12', '4) Komponen tidak boleh sama dengan bundle itu sendiri.');
    $guide->setCellValue('A13', '5) Satu bundle boleh punya banyak baris (1 baris = 1 komponen).');
    $guide->setCellValue('A14', '6) Harga jual adalah harga final produk (bukan proporsional per komponen). Isi di baris pertama saja.');

    foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
        $guide->getColumnDimension($col)->setAutoSize(true);
    }
    $guide->getStyle('A9:A14')->getAlignment()->setWrapText(true);
    $guide->getStyle('A1:E14')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

    // Keep first sheet as default active sheet.
    $spreadsheet->setActiveSheetIndex(0);

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


