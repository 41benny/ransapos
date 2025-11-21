<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Order {{ $sale->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin:0; padding:0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #111827;
        }
        .ticket {
            width: 80mm;
            padding: 8px 10px;
        }
        .title {
            text-align: center;
            margin-bottom: 4px;
            font-weight: 700;
            font-size: 13px;
        }
        .subtitle {
            text-align: center;
            margin-bottom: 8px;
            font-size: 10px;
        }
        .muted { color:#6b7280; }
        .row { display:flex; justify-content:space-between; margin-bottom:2px; }
        .items { margin-top:6px; border-top:1px dashed #9ca3af; padding-top:4px; }
        .item-row { margin-bottom:3px; }
        .item-name { font-weight:600; }
        .item-notes { font-size:10px; color:#b45309; margin-left:10px; }
        .total { margin-top:6px; border-top:1px dashed #9ca3af; padding-top:4px; }
        .mb-1 { margin-bottom:4px; }
        .mb-2 { margin-bottom:8px; }
        .text-right { text-align:right; }
        @media print {
            body { margin:0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">
        <div class="title">ORDER TICKET</div>
        <div class="subtitle muted">
            {{ $sale->outlet->name ?? 'Outlet' }}
        </div>

        <div class="mb-2">
            <div class="row">
                <span class="muted">Invoice</span>
                <span>{{ $sale->invoice_number }}</span>
            </div>
            <div class="row">
                <span class="muted">Tanggal</span>
                <span>{{ $sale->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
            </div>
            @if($sale->customer_name)
            <div class="row">
                <span class="muted">Customer</span>
                <span>{{ $sale->customer_name }}</span>
            </div>
            @endif
            @if($sale->user)
            <div class="row">
                <span class="muted">Kasir</span>
                <span>{{ $sale->user->name }}</span>
            </div>
            @endif
        </div>

        @if($sale->notes)
            <div class="mb-2" style="border:1px dashed #f59e0b; padding:4px;">
                <div style="font-weight:600; font-size:10px; margin-bottom:2px;">Catatan Order:</div>
                <div style="font-size:10px;">{{ $sale->notes }}</div>
            </div>
        @endif

        <div class="items">
            @foreach($sale->items as $item)
                <div class="item-row">
                    <div>
                        <span>{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}x</span>
                        <span class="item-name">{{ $item->product_name }}</span>
                    </div>
                    @if($item->notes)
                        <div class="item-notes">• {{ $item->notes }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="total">
            <div class="row mb-1">
                <span class="muted">Subtotal</span>
                <span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="row mb-1">
                <span class="muted">Diskon</span>
                <span>- Rp {{ number_format($sale->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="row">
                <span style="font-weight:600;">Total</span>
                <span style="font-weight:700;">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</body>
</html>

