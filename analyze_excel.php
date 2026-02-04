<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $inputFileName = __DIR__ . '/Produk.xlsx';
    if (!file_exists($inputFileName)) {
        echo "File Produk.xlsx not found in " . __DIR__;
        exit(1);
    }

    $spreadsheet = IOFactory::load($inputFileName);
    $worksheet = $spreadsheet->getActiveSheet();

    $rows = [];
    foreach ($worksheet->getRowIterator() as $index => $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE);
        $cells = [];
        foreach ($cellIterator as $cell) {
            $cells[] = $cell->getValue();
        }
        $rows[] = $cells;
        if (count($rows) >= 5) break; // Read first 5 rows
    }
    echo json_encode($rows);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
