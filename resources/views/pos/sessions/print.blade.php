<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift - {{ $session->session_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 58mm auto; /* Thermal printer width */
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 58mm;
            box-sizing: border-box;
            color: #000;
            line-height: 1.2;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .border-t { border-top: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; }
        .no-print { display: none; }
        
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-2">
        <div class="font-bold" style="font-size: 14px;">{{ $session->outlet?->name ?? 'Outlet' }}</div>
        <div>{{ $session->outlet?->address ?? 'Alamat Outlet' }}</div>
    </div>

    <div class="border-t"></div>

    <div class="text-center font-bold mb-2">LAPORAN SHIFT</div>

    <div class="mb-2">
        <div class="flex">
            <span>Shift:</span>
            <span>{{ $session->session_number }}</span>
        </div>
        <div class="flex">
            <span>Kasir:</span>
            <span>{{ $session->user?->name ?? 'Kasir' }}</span>
        </div>
        <div class="flex">
            <span>Buka:</span>
            <span>{{ $session->opened_at->format('d/m H:i') }}</span>
        </div>
        <div class="flex">
            <span>Tutup:</span>
            <span>{{ $session->closed_at ? $session->closed_at->format('d/m H:i') : 'Sekarang' }}</span>
        </div>
    </div>

    <div class="border-t"></div>

    <div class="mb-2">
        <div class="font-bold mb-1">RINGKASAN PENJUALAN</div>
        <div class="flex">
            <span>Total Transaksi:</span>
            <span>{{ $session->sales->count() }}</span>
        </div>
        <div class="flex">
            <span>Total Penjualan:</span>
            <span>{{ number_format($session->total_sales, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="border-t"></div>

    <div class="mb-2">
        <div class="font-bold mb-1">METODE PEMBAYARAN</div>
        @foreach($paymentStats as $stat)
        <div class="flex">
            <span>{{ $stat['name'] }}:</span>
            <span>{{ number_format($stat['total'], 0, ',', '.') }}</span>
        </div>
        @endforeach
    </div>

    <div class="border-t"></div>

    <div class="mb-2">
        <div class="font-bold mb-1">ARUS KAS</div>
        <div class="flex">
            <span>Modal Awal:</span>
            <span>{{ number_format($session->opening_balance, 0, ',', '.') }}</span>
        </div>
        <div class="flex">
            <span>Total Cash Masuk:</span>
            <span>{{ number_format($session->total_cash, 0, ',', '.') }}</span>
        </div>
        <div class="flex">
            <span>Kas Seharusnya:</span>
            <span>{{ number_format($session->expected_balance, 0, ',', '.') }}</span>
        </div>
        @if($session->actual_balance !== null)
        <div class="flex">
            <span>Kas Aktual:</span>
            <span>{{ number_format($session->actual_balance, 0, ',', '.') }}</span>
        </div>
        <div class="flex font-bold">
            <span>Selisih:</span>
            <span>{{ number_format($session->difference, 0, ',', '.') }}</span>
        </div>
        @endif
    </div>

    <div class="border-t"></div>
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
