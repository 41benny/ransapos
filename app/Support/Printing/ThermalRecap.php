<?php

namespace App\Support\Printing;

/**
 * Membangun byte ESC/POS untuk REKAP TRANSAKSI 58mm.
 *
 * Konten dibuat menyerupai resources/views/pos/sales/history-thermal.blade.php
 * agar hasil cetak thermal langsung (Web Bluetooth) konsisten dengan versi cetak browser.
 *
 * @param array{
 *   outlet_name?: string, cashier_name?: string,
 *   filters?: array{date_from?: string, date_to?: string},
 *   summary?: array{transactions?: int, void_transactions?: int, gross_sales?: float, avg_ticket?: float},
 *   payment_breakdown?: iterable, sales_type_breakdown?: iterable, product_rows?: iterable
 * } $data
 */
class ThermalRecap
{
    private const WIDTH = 32;
    private const ESC = "\x1B";
    private const GS = "\x1D";

    public static function build(array $data): string
    {
        $outletName = (string) ($data['outlet_name'] ?? 'Outlet');
        $cashierName = (string) ($data['cashier_name'] ?? 'Kasir');
        $filters = $data['filters'] ?? [];
        $summary = $data['summary'] ?? [];
        $paymentBreakdown = $data['payment_breakdown'] ?? [];
        $salesTypeBreakdown = $data['sales_type_breakdown'] ?? [];
        $productRows = $data['product_rows'] ?? [];

        $out = '';
        $out .= self::ESC . '@';
        $out .= self::ESC . 't' . "\x00";

        // ---- Header ----
        $out .= self::center();
        $out .= self::boldOn() . self::doubleOn();
        $out .= self::line(self::ascii($outletName));
        $out .= self::doubleOff();
        $out .= self::line('REKAP TRANSAKSI');
        $out .= self::boldOff();
        $out .= self::left();
        $out .= self::divider();

        // ---- Info ----
        $out .= self::twoCols('Kasir', self::ascii($cashierName));
        $out .= self::twoCols('Periode', (string) ($filters['date_from'] ?? '-'));
        $out .= self::twoCols('', (string) ($filters['date_to'] ?? '-'));
        $out .= self::divider();

        // ---- Ringkasan ----
        $out .= self::boldOn() . self::line('RINGKASAN') . self::boldOff();
        $out .= self::twoCols('Trx Selesai', self::int($summary['transactions'] ?? 0));
        $out .= self::twoCols('Trx Void', self::int($summary['void_transactions'] ?? 0));
        $out .= self::twoCols('Total Jual', self::money($summary['gross_sales'] ?? 0));
        $out .= self::twoCols('Avg Ticket', self::money($summary['avg_ticket'] ?? 0));
        $out .= self::divider();

        // ---- Pembayaran ----
        $out .= self::boldOn() . self::line('PEMBAYARAN') . self::boldOff();
        $hasPayment = false;
        foreach ($paymentBreakdown as $row) {
            $hasPayment = true;
            $label = self::ascii((string) ($row->method_name ?? '-')) . ' x' . self::int($row->payment_count ?? 0);
            $out .= self::twoCols($label, self::money($row->total_amount ?? 0));
        }
        if (!$hasPayment) {
            $out .= self::twoCols('-', '0');
        }
        $out .= self::divider();

        // ---- Metode penjualan ----
        $out .= self::boldOn() . self::line('METODE PENJUALAN') . self::boldOff();
        $hasSalesType = false;
        foreach ($salesTypeBreakdown as $row) {
            $hasSalesType = true;
            $label = self::ascii((string) ($row->sales_type_name ?? '-')) . ' x' . self::int($row->transaction_count ?? 0);
            $out .= self::twoCols($label, self::money($row->total_amount ?? 0));
        }
        if (!$hasSalesType) {
            $out .= self::twoCols('-', '0');
        }
        $out .= self::divider();

        // ---- Produk terjual ----
        $out .= self::boldOn() . self::line('PRODUK TERJUAL (QTY)') . self::boldOff();
        $totalQty = 0.0;
        $totalProducts = 0;
        $hasProduct = false;
        foreach ($productRows as $row) {
            $hasProduct = true;
            $totalProducts++;
            $qty = (float) ($row->total_qty ?? 0);
            $totalQty += $qty;
            $out .= self::twoCols(self::ascii((string) ($row->product_name ?? '-')), self::qty($qty));
        }
        if (!$hasProduct) {
            $out .= self::line('Tidak ada produk terjual');
        } else {
            $out .= self::boldOn() . self::twoCols('Total Qty', self::qty($totalQty)) . self::boldOff();
            $out .= self::twoCols('Jumlah Produk', self::int($totalProducts));
        }
        $out .= self::divider();

        // ---- Footer ----
        $out .= self::center();
        $out .= self::line('Dicetak: ' . now()->format('d/m/Y H:i:s'));
        $out .= self::left();
        $out .= self::feed() . self::feed() . self::feed();

        return $out;
    }

    public static function buildBase64(array $data): string
    {
        return base64_encode(self::build($data));
    }

    // ---- Helper layout ----

    private static function line(string $text): string
    {
        return $text . "\n";
    }

    private static function divider(): string
    {
        return str_repeat('-', self::WIDTH) . "\n";
    }

    private static function twoCols(string $left, string $right): string
    {
        $space = self::WIDTH - mb_strlen($left) - mb_strlen($right);
        if ($space < 1) {
            $left = mb_substr($left, 0, max(0, self::WIDTH - mb_strlen($right) - 1));
            $space = max(1, self::WIDTH - mb_strlen($left) - mb_strlen($right));
        }

        return $left . str_repeat(' ', $space) . $right . "\n";
    }

    private static function money($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private static function int($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private static function qty($value): string
    {
        $value = (float) $value;
        $isWhole = abs($value - round($value)) < 0.00001;

        return $isWhole
            ? number_format($value, 0, ',', '.')
            : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private static function ascii(?string $text): string
    {
        $text = (string) $text;
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    // ---- Perintah ESC/POS ----

    private static function left(): string
    {
        return self::ESC . 'a' . "\x00";
    }

    private static function center(): string
    {
        return self::ESC . 'a' . "\x01";
    }

    private static function boldOn(): string
    {
        return self::ESC . 'E' . "\x01";
    }

    private static function boldOff(): string
    {
        return self::ESC . 'E' . "\x00";
    }

    private static function doubleOn(): string
    {
        return self::GS . '!' . "\x11";
    }

    private static function doubleOff(): string
    {
        return self::GS . '!' . "\x00";
    }

    private static function feed(): string
    {
        return "\n";
    }
}
