<?php
$zip = new ZipArchive;
if ($zip->open('Produk.xlsx') === TRUE) {
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        echo "--- SHARED STRINGS XML SAMPLE ---\n";
        echo substr($xml, 0, 1000) . "\n\n";
    }

    if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        echo "--- SHEET1 XML SAMPLE ---\n";
        $sample = substr($sheetXml, 0, 5000);
        preg_match_all('/<t>(.*?)<\/t>/', $sample, $matches);
        echo "Headers: " . implode(", ", $matches[1]) . "\n";
    }
    $zip->close();
} else {
    echo "Failed to open xlsx file.\n";
}
