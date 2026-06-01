<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk</title>
</head>
<body class="{{ request()->boolean('embedded') ? 'embedded-print' : '' }}">
<div class="no-print print-tools">
    <button type="button" id="printNowBtn" class="tool-btn">Cetak Sekarang</button>
    <button type="button" id="closeWindowBtn" class="tool-btn secondary">Kembali</button>
</div>
<div class="no-print print-help">
    <p><strong>Jika printer tidak muncul:</strong></p>
    <ol>
        <li>Pastikan printer thermal online dan jadi default printer di OS.</li>
        <li>Izinkan popup/print dialog untuk domain POS ini.</li>
        <li>Tutup dialog print lalu klik <em>Cetak Sekarang</em>.</li>
    </ol>
    <p id="printStatus" class="print-status" aria-live="polite"></p>
</div>
<div class="receipt-container">
    @php
        $companyLogo = \App\Models\Setting::getValue('company_logo');
        $companyName = \App\Models\Setting::getValue('company_name', $sale->outlet->name);
        $companyAddress = \App\Models\Setting::getValue('company_address', $sale->outlet->address);
        $companyPhone = \App\Models\Setting::getValue('company_phone', $sale->outlet->phone);
        $receiptHeader = ($sale->outlet->receipt_header ?? null) ?: \App\Models\Setting::getValue('receipt_header');
        $receiptFooter = ($sale->outlet->receipt_footer ?? null) ?: \App\Models\Setting::getValue('receipt_footer');
    @endphp

    <div class="header">
        <div class="brand-row">
            @if($companyLogo)
                <img src="{{ asset('storage/' . $companyLogo) }}" alt="Logo" class="receipt-logo">
            @endif
            <div class="brand-text">
                <h2>{{ $companyName }}</h2>
                @if($receiptHeader)
                    <div class="receipt-header-text">{!! nl2br(e($receiptHeader)) !!}</div>
                @endif
            </div>
        </div>
        @if($companyAddress)
            <p>{{ $companyAddress }}</p>
        @endif
        @if($companyPhone)
            <p>{{ $companyPhone }}</p>
        @endif
    </div>

    <div class="divider">================================</div>

    <div class="meta">
        <div class="flex-between">
            <span>No: {{ $sale->invoice_number }}</span>
            <span>{{ $sale->sale_date->format('d/m/Y') }} {{ $sale->created_at->format('H:i') }}</span>
        </div>
        <div class="flex-between">
            <span>Kasir: {{ $sale->user->name }}</span>
            <span>{{ $sale->customer ? 'Cust: ' . Str::limit($sale->customer->name, 10) : 'Umum' }}</span>
        </div>
    </div>

    <div class="divider">--------------------------------</div>

    <div class="items">
        @foreach($sale->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->product_name }}</div>
            <div class="flex-between item-detail">
                <span>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($item->discount_amount > 0)
            <div class="flex-between item-discount">
                <span>(Disc)</span>
                <span>-{{ number_format($item->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="divider">--------------------------------</div>

    <div class="summary">
        <div class="flex-between">
            <span>Subtotal</span>
            <span>{{ number_format($sale->subtotal, 0, ',', '.') }}</span>
        </div>
        
        @if($sale->discount_amount > 0)
        <div class="flex-between">
            <span>Diskon</span>
            <span>-{{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif

        @if($sale->service_charge_amount > 0)
        <div class="flex-between">
            <span>Service Charge</span>
            <span>{{ number_format($sale->service_charge_amount, 0, ',', '.') }}</span>
        </div>
        @endif

        @if($sale->tax_amount > 0)
        <div class="flex-between">
            <span>Pajak</span>
            <span>{{ number_format($sale->tax_amount, 0, ',', '.') }}</span>
        </div>
        @endif

        @if((float) ($sale->rounding_amount ?? 0) !== 0.0)
        <div class="flex-between">
            <span>Pembulatan</span>
            <span>
                {{ (float) $sale->rounding_amount > 0 ? '+' : '-' }}
                {{ number_format(abs((float) $sale->rounding_amount), 2, ',', '.') }}
            </span>
        </div>
        @endif

        <div class="divider">================================</div>
        
        <div class="flex-between total">
            <span>TOTAL</span>
            <span>{{ number_format($sale->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="divider">--------------------------------</div>

    <div class="payment">
        @foreach($sale->payments as $payment)
        <div class="flex-between">
            <span>{{ $payment->paymentMethod?->name ?? '-' }}</span>
            <span>{{ number_format($payment->amount, 0, ',', '.') }}</span>
        </div>
        @endforeach
        
        @php
            $tenderedAmount = (float) ($sale->payments->sum(function ($payment) {
                return (float) ($payment->tendered_amount ?? 0);
            }));
            $fallbackPaid = (float) $sale->payments->sum('amount');
            $totalTendered = $tenderedAmount > 0 ? $tenderedAmount : $fallbackPaid;
            $change = max(0, $totalTendered - (float) $sale->total_amount);
            $hasTenderedAmount = $tenderedAmount > 0;
            $hasCashPayment = $sale->payments->contains(function ($payment) {
                $code = strtoupper(trim((string) ($payment->paymentMethod?->code ?? '')));
                $name = strtolower(trim((string) ($payment->paymentMethod?->name ?? '')));

                return $code === 'CASH'
                    || str_contains($name, 'cash')
                    || str_contains($name, 'tunai')
                    || (int) $payment->payment_method_id === 1;
            });
        @endphp

        @if($hasCashPayment && $totalTendered > 0)
        <div class="flex-between">
            <span>Uang Diterima</span>
            <span>{{ number_format($totalTendered, 0, ',', '.') }}</span>
        </div>
        @endif

        @if($change > 0)
        <div class="flex-between">
            <span>Kembali</span>
            <span>{{ number_format($change, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div class="footer">
        @if($receiptFooter)
            {!! nl2br(e($receiptFooter)) !!}
        @else
            <p>Terima Kasih atas Kunjungan Anda</p>
        @endif
    </div>
</div>

<style>
    /* Reset & Base */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 12px;
        line-height: 1.2;
        color: #000;
        background: #fff;
    }

    body.embedded-print .no-print {
        display: none !important;
    }

    .print-tools {
        width: 58mm;
        margin: 8px auto 6px;
        display: flex;
        gap: 6px;
    }
    .tool-btn {
        flex: 1;
        border: 1px solid #111;
        background: #111;
        color: #fff;
        border-radius: 4px;
        padding: 6px 8px;
        font-size: 10px;
        cursor: pointer;
    }
    .tool-btn.secondary {
        background: #fff;
        color: #111;
    }
    .print-help {
        width: 58mm;
        margin: 0 auto 8px;
        border: 1px dashed #999;
        border-radius: 4px;
        padding: 6px;
        font-size: 10px;
        color: #333;
    }
    .print-help ol {
        margin: 4px 0 0;
        padding-left: 14px;
    }
    .print-status {
        margin-top: 6px;
        font-size: 10px;
    }
    /* Receipt Container (58mm width aprox 220px-240px safe area) */
    .receipt-container {
        width: 58mm; /* Standard thermal paper */
        margin: 0 auto;
        padding: 5px;
        background: #fff;
    }

    /* Typography Utilities */
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: bold; }
    
    .flex-between {
        display: flex;
        justify-content: space-between;
    }

    /* Sections */
    .header {
        text-align: center;
        margin-bottom: 10px;
    }
    .header h2 {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 2px;
        text-transform: uppercase;
    }

    .brand-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 6px;
    }

    .brand-text {
        text-align: left;
    }

    .brand-text h2 {
        margin-bottom: 0;
    }
    
    .divider {
        text-align: center;
        margin: 5px 0;
        overflow: hidden;
        white-space: nowrap;
    }

    .item {
        margin-bottom: 4px;
    }
    .item-name {
        font-weight: bold;
    }
    .item-discount {
        font-style: italic;
    }

    .summary .total {
        font-size: 14px;
        font-weight: bold;
        margin-top: 5px;
    }

    .footer {
        text-align: center;
        margin-top: 15px;
        font-size: 10px;
    }

    .receipt-logo {
        max-width: 18mm;
        max-height: 16mm;
        object-fit: contain;
        flex-shrink: 0;
    }

    .receipt-header-text {
        font-style: italic;
        margin-top: 2px;
    }

    /* Print Settings */
    @media print {
        @page {
            margin: 0;
            size: 58mm auto; /* continuous roll */
        }
        body {
            margin: 0;
        }
        /* Hide everything else if rendered inside layout */
        header, footer, nav, .no-print {
            display: none !important;
        }
    }
</style>

<script>
    (function () {
        function setPrintStatus(message, isError) {
            var statusElement = document.getElementById('printStatus');
            if (!statusElement) return;

            statusElement.textContent = message || '';
            statusElement.style.color = isError ? '#B91C1C' : '#374151';
        }

        function triggerPrint() {
            if (typeof window.print !== 'function') {
                setPrintStatus('Browser ini tidak mendukung fitur print.', true);
                return;
            }

            try {
                window.focus();
                window.print();
                setPrintStatus('Dialog print dibuka. Pilih printer struk lalu klik Print.', false);
            } catch (error) {
                var message = error && error.message ? error.message : 'Terjadi kesalahan saat membuka dialog print.';
                setPrintStatus(message, true);
            }
        }

        function shouldAutoPrint() {
            var params = new URLSearchParams(window.location.search || '');
            return params.get('autoprint') !== '0';
        }

        document.addEventListener('DOMContentLoaded', function () {
            var printNowButton = document.getElementById('printNowBtn');
            if (printNowButton) {
                printNowButton.addEventListener('click', triggerPrint);
            }

            var closeWindowButton = document.getElementById('closeWindowBtn');
            if (closeWindowButton) {
                closeWindowButton.addEventListener('click', function () {
                    if (window.opener && !window.opener.closed) {
                        window.close();
                        return;
                    }

                    if (window.history.length > 1) {
                        window.history.back();
                        return;
                    }

                    window.location.href = '{{ route('pos.sales.create') }}';
                });
            }

            if (!shouldAutoPrint()) {
                return;
            }

            setPrintStatus('Menyiapkan dialog print...', false);
            setTimeout(triggerPrint, 350);
        });

        window.addEventListener('afterprint', function () {
            setPrintStatus('Cetak selesai. Jika struk belum keluar, cek koneksi printer di perangkat ini.', false);
        });
    })();
</script>
</body>
</html>
