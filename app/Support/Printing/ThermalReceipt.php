<?php

namespace App\Support\Printing;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Membangun byte ESC/POS untuk struk penjualan 58mm.
 *
 * Konten dibuat menyerupai struk HTML di resources/views/pos/sales/print.blade.php
 * agar hasil cetak thermal (mis. via RawBT di Android) konsisten dengan cetak browser.
 */
class ThermalReceipt
{
    /** Lebar kertas 58mm pada Font A = 32 karakter. */
    private const WIDTH = 32;

    // Perintah ESC/POS dasar.
    private const ESC = "\x1B";
    private const GS = "\x1D";

    public static function build(Sale $sale): string
    {
        $sale->loadMissing(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);

        $companyName = Setting::getValue('company_name', $sale->outlet->name ?? 'Outlet');
        $companyAddress = Setting::getValue('company_address', $sale->outlet->address ?? null);
        $companyPhone = Setting::getValue('company_phone', $sale->outlet->phone ?? null);
        $receiptHeader = ($sale->outlet->receipt_header ?? null) ?: Setting::getValue('receipt_header');
        $receiptFooter = ($sale->outlet->receipt_footer ?? null) ?: Setting::getValue('receipt_footer');

        $out = '';
        $out .= self::ESC . '@';            // init
        $out .= self::ESC . 't' . "\x00";   // codepage PC437 (aman untuk ASCII)

        // ---- Header ----
        $out .= self::center();
        $out .= self::boldOn() . self::doubleOn();
        $out .= self::line(self::ascii($companyName));
        $out .= self::doubleOff() . self::boldOff();
        if ($companyAddress) {
            foreach (self::wrap(self::ascii($companyAddress)) as $row) {
                $out .= self::line($row);
            }
        }
        if ($companyPhone) {
            $out .= self::line(self::ascii($companyPhone));
        }
        if ($receiptHeader) {
            foreach (self::wrap(self::ascii($receiptHeader)) as $row) {
                $out .= self::line($row);
            }
        }
        $out .= self::left();
        $out .= self::line(str_repeat('=', self::WIDTH));

        // ---- Meta transaksi ----
        $out .= self::line('No: ' . $sale->invoice_number);
        $out .= self::twoCols(
            'Tgl: ' . optional($sale->sale_date)->format('d/m/Y'),
            optional($sale->created_at)->format('H:i')
        );
        $customer = $sale->customer ? 'Cust: ' . Str::limit($sale->customer->name, 14, '') : 'Umum';
        $out .= self::twoCols('Kasir: ' . self::ascii($sale->user->name ?? '-'), $customer);
        $out .= self::line(str_repeat('-', self::WIDTH));

        // ---- Item ----
        foreach ($sale->items as $item) {
            foreach (self::wrap(self::ascii($item->product_name)) as $row) {
                $out .= self::line($row);
            }
            $qtyLine = self::qty($item->quantity) . ' x ' . self::money($item->unit_price);
            $out .= self::twoCols($qtyLine, self::money($item->subtotal));
            if ((float) $item->discount_amount > 0) {
                $out .= self::twoCols('  (Disc)', '-' . self::money($item->discount_amount));
            }
        }
        $out .= self::line(str_repeat('-', self::WIDTH));

        // ---- Ringkasan ----
        $out .= self::twoCols('Subtotal', self::money($sale->subtotal));
        if ((float) $sale->discount_amount > 0) {
            $out .= self::twoCols('Diskon', '-' . self::money($sale->discount_amount));
        }
        if ((float) $sale->service_charge_amount > 0) {
            $out .= self::twoCols('Service Charge', self::money($sale->service_charge_amount));
        }
        if ((float) $sale->tax_amount > 0) {
            $out .= self::twoCols('Pajak', self::money($sale->tax_amount));
        }
        $rounding = (float) ($sale->rounding_amount ?? 0);
        if ($rounding !== 0.0) {
            $out .= self::twoCols('Pembulatan', ($rounding > 0 ? '+' : '-') . self::money(abs($rounding)));
        }
        $out .= self::line(str_repeat('=', self::WIDTH));
        $out .= self::boldOn();
        $out .= self::twoCols('TOTAL', self::money($sale->total_amount));
        $out .= self::boldOff();
        $out .= self::line(str_repeat('-', self::WIDTH));

        // ---- Pembayaran ----
        foreach ($sale->payments as $payment) {
            $name = $payment->paymentMethod?->name ?? '-';
            $out .= self::twoCols(self::ascii($name), self::money($payment->amount));
        }
        $totalPaid = (float) $sale->payments->sum('amount');
        $change = $totalPaid - (float) $sale->total_amount;
        if ($change > 0) {
            $out .= self::twoCols('Kembali', self::money($change));
        }

        // ---- Footer ----
        $out .= self::feed();
        $out .= self::center();
        $footerText = $receiptFooter ?: 'Terima Kasih atas Kunjungan Anda';
        foreach (self::wrap(self::ascii($footerText)) as $row) {
            $out .= self::line($row);
        }
        $out .= self::left();

        // Maju kertas (RPP02N tidak punya cutter).
        $out .= self::feed() . self::feed() . self::feed();

        return $out;
    }

    /** ESC/POS bytes dalam bentuk base64 (untuk intent RawBT). */
    public static function buildBase64(Sale $sale): string
    {
        return base64_encode(self::build($sale));
    }

    // ---- Helper layout ----

    private static function line(string $text): string
    {
        return $text . "\n";
    }

    private static function twoCols(string $left, string $right): string
    {
        $space = self::WIDTH - mb_strlen($left) - mb_strlen($right);
        if ($space < 1) {
            // Potong sisi kiri agar muat satu baris.
            $left = mb_substr($left, 0, max(0, self::WIDTH - mb_strlen($right) - 1));
            $space = max(1, self::WIDTH - mb_strlen($left) - mb_strlen($right));
        }

        return $left . str_repeat(' ', $space) . $right . "\n";
    }

    /** @return string[] */
    private static function wrap(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        return explode("\n", wordwrap($text, self::WIDTH, "\n", true));
    }

    private static function money($value): string
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

    /** Buang karakter non-ASCII agar tidak jadi mojibake di printer. */
    private static function ascii(?string $text): string
    {
        $text = (string) $text;
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    // ---- Helper perintah ESC/POS ----

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
        return self::GS . '!' . "\x11"; // gandakan lebar + tinggi
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
