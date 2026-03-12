<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportExport
{
    /**
     * @param array<int, array<string, mixed>> $columns
     * @param iterable<mixed> $rows
     */
    public static function xlsx(string $filename, string $sheetTitle, array $columns, iterable $rows): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        foreach ($columns as $index => $column) {
            $col = self::columnLetter($index + 1);
            $sheet->setCellValue($col . '1', (string) ($column['label'] ?? $column['key'] ?? ''));
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            foreach ($columns as $index => $column) {
                $col = self::columnLetter($index + 1);
                $cell = $col . $rowNum;
                $value = Arr::get((array) $row, $column['key']);
                $type = $column['type'] ?? 'text';
                $decimals = (int) ($column['decimals'] ?? 0);

                if ($type === 'number' && is_numeric($value)) {
                    $numValue = (float) $value;
                    $sheet->setCellValue($cell, $numValue);
                    $formatCode = $decimals > 0
                        ? '#,##0.' . str_repeat('0', $decimals)
                        : '#,##0';
                    $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($formatCode);
                } else {
                    $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
                }
            }
            $rowNum++;
        }

        foreach (range(1, count($columns)) as $index) {
            $sheet->getColumnDimension(self::columnLetter($index))->setAutoSize(true);
        }

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        // Send file using raw PHP headers to guarantee correct filename
        self::sendFile($tempFile, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     * @param iterable<mixed> $rows
     */
    public static function pdf(string $filename, string $title, array $columns, iterable $rows): void
    {
        $htmlRows = '';
        foreach ($rows as $row) {
            $htmlRows .= '<tr>';
            foreach ($columns as $column) {
                $type = $column['type'] ?? 'text';
                $value = Arr::get((array) $row, $column['key']);

                if ($type === 'number' && is_numeric($value)) {
                    $decimals = (int) ($column['decimals'] ?? 0);
                    $value = number_format((float) $value, $decimals, ',', '.');
                }

                $htmlRows .= '<td>' . e((string) ($value ?? '')) . '</td>';
            }
            $htmlRows .= '</tr>';
        }

        if ($htmlRows === '') {
            $htmlRows = '<tr><td colspan="' . count($columns) . '" style="text-align:center;">Tidak ada data.</td></tr>';
        }

        $headers = implode('', array_map(fn ($column) => '<th>' . e((string) ($column['label'] ?? $column['key'])) . '</th>', $columns));

        $generatedAt = now()->format('Y-m-d H:i:s');

        $html = <<<HTML
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        .meta { font-size: 10px; color: #6b7280; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
    </style>
</head>
<body>
    <h1>{$title}</h1>
    <div class="meta">Generated: {$generatedAt}</div>
    <table>
        <thead><tr>{$headers}</tr></thead>
        <tbody>{$htmlRows}</tbody>
    </table>
</body>
</html>
HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        file_put_contents($tempFile, $dompdf->output());

        // Send file using raw PHP headers to guarantee correct filename
        self::sendFile($tempFile, $filename, 'application/pdf');
    }

    /**
     * Send a file download using raw PHP headers (bypasses Laravel response layer).
     */
    private static function sendFile(string $filePath, string $downloadName, string $contentType): void
    {
        // Clean ALL output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        $fileSize = filesize($filePath);

        header('Content-Description: File Transfer');
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);

        readfile($filePath);

        // Cleanup temp file
        @unlink($filePath);

        exit;
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) floor(($index - $mod) / 26);
        }

        return $letter;
    }
}
