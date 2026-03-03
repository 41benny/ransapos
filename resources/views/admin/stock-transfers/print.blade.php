<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Pengiriman {{ $stockTransfer->transfer_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            background: #f8fafc;
        }
        .container {
            max-width: 980px;
            margin: 24px auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 24px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }
        .title {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .subtitle {
            margin: 4px 0 0;
            font-size: 12px;
            color: #475569;
        }
        .meta {
            margin: 16px 0 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .meta table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .meta td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta tr:last-child td {
            border-bottom: none;
        }
        .label {
            color: #64748b;
            width: 180px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .badge.actual {
            background: #dcfce7;
            color: #166534;
        }
        .badge.estimated {
            background: #fef3c7;
            color: #92400e;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .table th,
        .table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
        }
        .table th {
            background: #f8fafc;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: .06em;
            color: #64748b;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .total-box {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
        }
        .total-box table {
            width: 360px;
            border-collapse: collapse;
            font-size: 13px;
        }
        .total-box td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
        }
        .total-box td:first-child {
            background: #f8fafc;
            color: #475569;
            width: 55%;
        }
        .total-box td:last-child {
            font-weight: 700;
            text-align: right;
        }
        .note {
            margin-top: 12px;
            font-size: 11px;
            color: #475569;
        }
        .actions {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
            padding: 8px 12px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn.primary {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
        @media print {
            body { background: #ffffff; }
            .container {
                margin: 0;
                border: none;
                border-radius: 0;
                padding: 0;
                max-width: none;
            }
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1 class="title">Dokumen Pengiriman Antar Outlet</h1>
                <p class="subtitle">Dasar tagihan outlet tujuan berdasarkan HPP barang kirim</p>
            </div>
            <div>
                <strong>{{ config('app.name') }}</strong>
            </div>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td class="label">No Transfer</td>
                    <td><strong>{{ $stockTransfer->transfer_number }}</strong></td>
                    <td class="label">Tanggal Transfer</td>
                    <td>{{ $stockTransfer->transfer_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Dari Outlet</td>
                    <td>{{ $stockTransfer->fromOutlet->name }}</td>
                    <td class="label">Ke Outlet</td>
                    <td>{{ $stockTransfer->toOutlet->name }}</td>
                </tr>
                <tr>
                    <td class="label">Dibuat Oleh</td>
                    <td>{{ $stockTransfer->creator->name ?? '-' }}</td>
                    <td class="label">Sumber Nilai</td>
                    <td>
                        @if(($valuationSource ?? 'actual') === 'estimated')
                            <span class="badge estimated">Estimasi HPP</span>
                        @elseif(($valuationSource ?? 'actual') === 'mixed')
                            <span class="badge estimated">Campuran</span>
                        @else
                            <span class="badge actual">Aktual</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Nilai Kirim (HPP)</td>
                    <td><strong>Rp {{ number_format((float) ($transferNominalTotal ?? 0), 0, ',', '.') }}</strong></td>
                    <td class="label">Nilai Tagihan Outlet</td>
                    <td><strong>Rp {{ number_format((float) ($transferBillingNominalTotal ?? 0), 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Produk</th>
                    <th style="width: 90px;" class="text-right">Qty Kirim</th>
                    <th style="width: 90px;" class="text-right">Qty Tagih</th>
                    <th style="width: 140px;" class="text-right">HPP/Unit</th>
                    <th style="width: 160px;" class="text-right">Subtotal HPP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stockTransfer->items as $index => $item)
                    @php
                        $hppData = $itemHppMap[$item->product_id] ?? ['unit_hpp' => 0, 'billing_nominal' => 0, 'billed_qty' => 0];
                        $unitHpp = (float) ($hppData['unit_hpp'] ?? 0);
                        $lineNominal = (float) ($hppData['billing_nominal'] ?? ($unitHpp * (float) $item->quantity));
                        $billedQty = (float) ($hppData['billed_qty'] ?? (float) $item->quantity);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->product->name }}</strong><br>
                            <span style="color:#64748b; font-size:11px;">{{ $item->product->sku ?? 'NO-SKU' }}</span>
                        </td>
                        <td class="text-right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($billedQty, 2, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($unitHpp, 0, ',', '.') }}</td>
                        <td class="text-right"><strong>Rp {{ number_format($lineNominal, 0, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-box">
            <table>
                <tr>
                    <td>Total Nilai Tagihan Outlet</td>
                    <td>Rp {{ number_format((float) ($transferBillingNominalTotal ?? $transferNominalTotal), 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <p class="note">
            Catatan: Jika status transfer masih draft/pending, nilai dapat berlabel estimasi karena memakai avg cost terbaru outlet pengirim.
        </p>

        <div class="actions">
            <button class="btn" onclick="window.close()">Tutup</button>
            <button class="btn primary" onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>
