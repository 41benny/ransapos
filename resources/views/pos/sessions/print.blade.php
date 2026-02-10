<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift - {{ $session->session_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            margin: 0;
            padding: 10px;
            width: 58mm;
            box-sizing: border-box;
            color: #000;
            line-height: 1.3;
        }

        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .section { margin-bottom: 8px; }
        .section-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
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
            white-space: nowrap;
            text-align: right;
        }
        .muted {
            color: #444;
        }
        .no-print { display: none; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        $formatQty = static function ($qty): string {
            $qty = (float) $qty;
            $isWhole = abs($qty - round($qty)) < 0.00001;
            return $isWhole
                ? number_format($qty, 0, ',', '.')
                : number_format($qty, 2, ',', '.');
        };
    @endphp

    <div class="text-center section">
        <div class="font-bold" style="font-size: 13px;">{{ $session->outlet?->name ?? 'Outlet' }}</div>
        <div>{{ $session->outlet?->address ?? 'Alamat Outlet' }}</div>
    </div>

    <div class="divider"></div>

    <div class="text-center font-bold section">LAPORAN SHIFT</div>

    <div class="section">
        <div class="row">
            <span class="left">Shift</span>
            <span class="right">{{ $session->session_number }}</span>
        </div>
        <div class="row">
            <span class="left">Kasir</span>
            <span class="right">{{ $session->user?->name ?? 'Kasir' }}</span>
        </div>
        <div class="row">
            <span class="left">Buka</span>
            <span class="right">{{ $session->opened_at->format('d/m H:i') }}</span>
        </div>
        <div class="row">
            <span class="left">Tutup</span>
            <span class="right">{{ $session->closed_at ? $session->closed_at->format('d/m H:i') : 'Sekarang' }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">RINGKASAN PENJUALAN</div>
        <div class="row">
            <span class="left">Total Transaksi</span>
            <span class="right">{{ $session->sales->count() }}</span>
        </div>
        <div class="row">
            <span class="left">Total Penjualan</span>
            <span class="right">{{ number_format($session->total_sales, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">METODE PEMBAYARAN</div>
        @forelse($paymentStats as $stat)
        <div class="row">
            <span class="left">{{ $stat['name'] }} x{{ $stat['count'] }}</span>
            <span class="right">{{ number_format($stat['total'], 0, ',', '.') }}</span>
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
        <div class="section-title">JENIS PENJUALAN</div>
        @forelse($salesTypeStats as $stat)
        <div class="row">
            <span class="left">{{ $stat['name'] }} x{{ $stat['count'] }}</span>
            <span class="right">{{ number_format($stat['total'], 0, ',', '.') }}</span>
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
        <div class="section-title">PER KATEGORI</div>
        @forelse($categoryStats as $stat)
        <div class="row">
            <span class="left">{{ $stat['name'] }} x{{ $formatQty($stat['qty']) }}</span>
            <span class="right">{{ number_format($stat['total'], 0, ',', '.') }}</span>
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
        <div class="section-title">PER ITEM (QTY KELUAR)</div>
        @forelse($productStats as $stat)
        <div class="row">
            <span class="left">
                {{ ($stat['sku'] ?? '') !== '' ? '[' . $stat['sku'] . '] ' : '' }}{{ $stat['name'] }}
            </span>
            <span class="right">
                {{ $formatQty($stat['qty']) }}{{ ($stat['unit'] ?? '') !== '' ? ' ' . $stat['unit'] : '' }}
            </span>
        </div>
        @empty
        <div class="row muted">
            <span class="left">-</span>
            <span class="right">0</span>
        </div>
        @endforelse

        @if(count($productStats) > 0)
        <div class="row font-bold">
            <span class="left">Total Qty Keluar</span>
            <span class="right">{{ $formatQty(collect($productStats)->sum('qty')) }}</span>
        </div>
        <div class="row">
            <span class="left">Jumlah Item/SKU</span>
            <span class="right">{{ count($productStats) }}</span>
        </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">ARUS KAS</div>
        <div class="row">
            <span class="left">Modal Awal</span>
            <span class="right">{{ number_format($session->opening_balance, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="left">Cash Masuk</span>
            <span class="right">{{ number_format($session->total_cash, 0, ',', '.') }}</span>
        </div>
        <div class="row">
            <span class="left">Kas Seharusnya</span>
            <span class="right">{{ number_format($session->expected_balance, 0, ',', '.') }}</span>
        </div>
        @if($session->actual_balance !== null)
        <div class="row">
            <span class="left">Kas Aktual</span>
            <span class="right">{{ number_format($session->actual_balance, 0, ',', '.') }}</span>
        </div>
        <div class="row font-bold">
            <span class="left">Selisih</span>
            <span class="right">{{ number_format($session->difference, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="text-center">
        <div>Dicetak: {{ now()->format('d/m/Y H:i:s') }}</div>
        <div>oleh {{ auth()->user()->name }}</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
