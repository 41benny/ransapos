<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function addHeaderComments(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
{
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
}

function addGuideSheet(Spreadsheet $spreadsheet): void
{
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
}

function setHeaders(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
{
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
}

function addProductTypeValidation(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
{
    $validation = $sheet->getCell('D2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setShowDropDown(true);
    $validation->setFormula1('"bahan,produk,bundle,jasa"');

    for ($i = 3; $i <= 200; $i++) {
        $sheet->getCell("D{$i}")->setDataValidation(clone $validation);
    }
}

function writeRows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $rows, int $startRow = 2): void
{
    $rowIndex = $startRow;
    foreach ($rows as $row) {
        $col = 'A';
        foreach ($row as $value) {
            $sheet->setCellValue($col . $rowIndex, $value);
            $col++;
        }
        $rowIndex++;
    }
}

function saveSpreadsheetWithFallback(Spreadsheet $spreadsheet, string $targetPath): string
{
    try {
        (new Xlsx($spreadsheet))->save($targetPath);
        return $targetPath;
    } catch (\Throwable $e) {
        $pathInfo = pathinfo($targetPath);
        for ($i = 1; $i <= 10; $i++) {
            $suffix = '_updated_' . date('Ymd_His') . '_' . $i;
            $fallbackPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . $suffix . '.xlsx';

            try {
                (new Xlsx($spreadsheet))->save($fallbackPath);
                return $fallbackPath;
            } catch (\Throwable $inner) {
                // Try another fallback filename.
            }
        }

        throw new \RuntimeException("Tidak bisa menyimpan file template. Tutup file Excel yang sedang terbuka, lalu coba lagi.");
    }
}

$path = __DIR__ . '/public/templates';
if (!is_dir($path)) {
    mkdir($path, 0777, true);
}

// Template umum (produk + BOM)
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('DATA_IMPORT');
setHeaders($sheet);
addHeaderComments($sheet);
addProductTypeValidation($sheet);
$sheet->freezePane('A2');

writeRows($sheet, [
    ['Biji Kopi Arabica', 'RAW-KOPI-01', 'Bahan Baku', 'bahan', 'gram', 150, 0, 1000, 'Bahan baku kopi', '', '', '', ''],
    ['Susu Fresh', 'RAW-SUSU-01', 'Bahan Baku', 'bahan', 'ml', 12, 0, 5000, 'Bahan baku susu', '', '', '', ''],
    ['Es Kopi Susu', 'BND-ESKOPI-01', 'Minuman', 'bundle', 'cup', '', 18000, 0, 'Bundle minuman kopi susu', 'RAW-KOPI-01', '', 18, 'gram'],
    ['Es Kopi Susu', 'BND-ESKOPI-01', 'Minuman', 'bundle', 'cup', '', '', 0, 'Baris komponen lanjutan - harga_jual kosongkan', 'RAW-SUSU-01', '', 120, 'ml'],
    ['Espresso', 'BEV-001', 'Minuman', 'produk', 'cup', 5000, 15000, 0, 'Single shot espresso', '', '', '', ''],
]);

addGuideSheet($spreadsheet);
$spreadsheet->setActiveSheetIndex(0);
$produkTemplatePath = saveSpreadsheetWithFallback($spreadsheet, $path . '/template_import_produk.xlsx');

// Template resep-only
$recipeSpreadsheet = new Spreadsheet();
$recipeSheet = $recipeSpreadsheet->getActiveSheet();
$recipeSheet->setTitle('RESEP_ONLY');
setHeaders($recipeSheet);
addHeaderComments($recipeSheet);
addProductTypeValidation($recipeSheet);
$recipeSheet->freezePane('A2');

writeRows($recipeSheet, [
    ['Mie goreng jawa', '', '', '', '', '', '', '', '', '', 'mie atom bulan', 160, 'gram'],
    ['Mie goreng jawa', '', '', '', '', '', '', '', '', '', 'telor ayam', 80, 'gram'],
    ['Mie goreng jawa', '', '', '', '', '', '', '', '', '', 'kol putih bdg', 20, 'gram'],
    ['Dimsum ayam', '', '', '', '', '', '', '', '', '', 'ayam suir', 60, 'gram'],
    ['Dimsum ayam', '', '', '', '', '', '', '', '', '', 'tepung tapioka', 40, 'gram'],
    ['Es kopi susu', '', '', '', '', '', '', '', '', '', 'biji kopi arabica', 18, 'gram'],
    ['Es kopi susu', '', '', '', '', '', '', '', '', '', 'susu fresh', 120, 'ml'],
]);

addGuideSheet($recipeSpreadsheet);
$recipeSpreadsheet->setActiveSheetIndex(0);
$recipeTemplatePath = saveSpreadsheetWithFallback($recipeSpreadsheet, $path . '/template_import_resep_only.xlsx');

echo "[OK] Generated: {$produkTemplatePath}\n";
echo "[OK] Generated: {$recipeTemplatePath}\n";
