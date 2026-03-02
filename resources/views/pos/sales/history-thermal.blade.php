<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Transaksi Thermal</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 8px;
            width: 58mm;
            box-sizing: border-box;
            color: #000;
            line-height: 1.3;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .section { margin-bottom: 8px; }
        .title { font-size: 13px; font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 2px;
        }
        .left {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .right {
            flex: 0 0 auto;
            text-align: right;
            white-space: nowrap;
        }
        .muted { color: #444; }
    </style>
</head>
<body>
    @php
        $outletName = auth()->user()->outlet->name ?? 'Outlet';
        $cashierName = auth()->user()->name ?? 'Kasir';
        $formatQty = static function ($qty): string {
            $qty = (float) $qty;
            $isWhole = abs($qty - round($qty)) < 0.00001;
            return $isWhole
                ? number_format($qty, 0, ',', '.')
                : number_format($qty, 2, ',', '.');
        };
    @endphp

    <div class="center section">
        <div class="title">{{ $outletName }}</div>
        <div class="bold">REKAP TRANSAKSI</div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="row">
            <span class="left">Kasir</span>
            <span class="right">{{ $cashierName }}</span>
        </div>
        <div class="row">
            <span class="left">Periode</span>
            <span class="right">{{ $filters['date_from'] ?? '-' }}</span>
        </div>
        <div class="row">
            <span class="left"></span>
            <span class="right">{{ $filters['date_to'] ?? '-' }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="bold">RINGKASAN</div>
        <div class="row">
            <span class="left">Trx Selesai</span>
            <span class="right">{{ number_format($summary['transactions'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="left">Trx Void</span>
            <span class="right">{{ number_format($summary['void_transactions'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="left">Total Jual</span>
            <span class="right">{{ number_format($summary['gross_sales'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="left">Avg Ticket</span>
            <span class="right">{{ number_format($summary['avg_ticket'] ?? 0, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="bold">PEMBAYARAN</div>
        @forelse($paymentBreakdown as $row)
            <div class="row">
                <span class="left">{{ $row->method_name }} x{{ number_format($row->payment_count, 0, ',', '.') }}</span>
                <span class="right">{{ number_format($row->total_amount, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="row muted">
                <span class="left">-</span>
                <span class="right">0</span>
            </div>
        @endforelse
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="bold">METODE PENJUALAN</div>
        @forelse(($salesTypeBreakdown ?? []) as $row)
            <div class="row">
                <span class="left">{{ $row->sales_type_name }} x{{ number_format($row->transaction_count, 0, ',', '.') }}</span>
                <span class="right">{{ number_format($row->total_amount, 0, ',', '.') }}</span>
            </div>
        @empty
            <div class="row muted">
                <span class="left">-</span>
                <span class="right">0</span>
            </div>
        @endforelse
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="bold">PRODUK TERJUAL (QTY)</div>
        @php
            $totalQtySold = collect($productRows ?? [])->sum('total_qty');
            $totalProducts = collect($productRows ?? [])->count();
        @endphp
        @forelse(($productRows ?? []) as $row)
            <div class="row">
                <span class="left">
                    {{ $row->product_name }}
                </span>
                <span class="right">{{ $formatQty($row->total_qty) }}</span>
            </div>
        @empty
            <div class="row muted">
                <span class="left">Tidak ada produk terjual</span>
                <span class="right">-</span>
            </div>
        @endforelse
        @if($totalProducts > 0)
            <div class="row bold">
                <span class="left">Total Qty</span>
                <span class="right">{{ $formatQty($totalQtySold) }}</span>
            </div>
            <div class="row">
                <span class="left">Jumlah Produk</span>
                <span class="right">{{ number_format($totalProducts, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="center muted">
        <div>Dicetak: {{ now()->format('d/m/Y H:i:s') }}</div>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 120);
        });
    </script>
</body>
</html>
