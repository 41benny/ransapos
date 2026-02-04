<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk</title>
</head>
<body>
<div class="receipt-container">
    <div class="header">
        <h2>{{ $sale->outlet->name }}</h2>
        <p>{{ $sale->outlet->address ?? 'Alamat Outlet' }}</p>
        <p>{{ $sale->outlet->phone ?? '' }}</p>
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
            <span>{{ $payment->paymentMethod->name }}</span>
            <span>{{ number_format($payment->amount, 0, ',', '.') }}</span>
        </div>
        @endforeach
        
        @php
            $totalPaid = $sale->payments->sum('amount');
            $change = $totalPaid - $sale->total_amount;
        @endphp

        @if($change > 0)
        <div class="flex-between">
            <span>Kembali</span>
            <span>{{ number_format($change, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Terima Kasih atas Kunjungan Anda</p>
        <p>Password Wifi: kopienak123</p>
        <p>IG: @morest.coffee</p>
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
        margin-bottom: 4px;
        text-transform: uppercase;
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
    window.onload = function() {
        window.print();
        // Optional: window.close() after print?
        // window.onafterprint = function() { window.close(); }
    }
</script>
</body>
</html>
