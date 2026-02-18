<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print PO - {{ $purchase->purchase_number }}</title>
    <style>
        :root {
            --text: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --bg-soft: #f8fafc;
            --accent: #1d4ed8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: var(--text);
            background: #ffffff;
        }

        .sheet {
            max-width: 980px;
            margin: 0 auto;
            border: 1px solid var(--line);
            padding: 24px;
        }

        .print-tools {
            position: sticky;
            top: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--text);
            padding: 10px 14px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #ffffff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .subtitle {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
        }

        .po-code {
            text-align: right;
        }

        .po-code .number {
            font-size: 20px;
            font-weight: 700;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .meta-card {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px 14px;
            background: var(--bg-soft);
        }

        .meta-title {
            margin: 0 0 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            font-weight: 700;
        }

        .meta-row {
            margin: 0 0 6px;
            font-size: 13px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            border: 1px solid var(--line);
            padding: 9px 10px;
            font-size: 13px;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            text-align: left;
        }

        .text-right {
            text-align: right;
            white-space: nowrap;
        }

        .summary {
            margin-top: 14px;
            margin-left: auto;
            width: 320px;
            border: 1px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 12px;
            font-size: 13px;
            border-bottom: 1px solid var(--line);
        }

        .summary-row:last-child {
            border-bottom: 0;
        }

        .summary-total {
            background: #eff6ff;
            font-weight: 700;
            font-size: 16px;
        }

        .notes-box {
            margin-top: 16px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px 14px;
            min-height: 72px;
        }

        .notes-title {
            margin: 0 0 6px;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--muted);
            font-weight: 700;
        }

        .notes-content {
            margin: 0;
            font-size: 13px;
            white-space: pre-line;
        }

        .signatures {
            margin-top: 42px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-label {
            font-size: 12px;
            color: var(--muted);
        }

        .sign-line {
            margin-top: 56px;
            border-top: 1px solid #334155;
            font-size: 12px;
            padding-top: 6px;
        }

        .footer {
            margin-top: 20px;
            font-size: 11px;
            color: var(--muted);
            text-align: right;
        }

        @media print {
            @page {
                margin: 12mm;
                size: A4 portrait;
            }

            body {
                padding: 0;
            }

            .print-tools {
                display: none;
            }

            .sheet {
                max-width: 100%;
                border: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="print-tools no-print">
        <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn">Kembali</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print PO</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div>
                <h1 class="title">PURCHASE ORDER</h1>
                <p class="subtitle">{{ $purchase->outlet->name ?? 'Outlet' }}</p>
                @if($purchase->outlet->address)
                    <p class="subtitle">{{ $purchase->outlet->address }}</p>
                @endif
                @if($purchase->outlet->phone)
                    <p class="subtitle">Telp: {{ $purchase->outlet->phone }}</p>
                @endif
            </div>
            <div class="po-code">
                <div class="number">{{ $purchase->purchase_number }}</div>
                <div class="subtitle">Tanggal: {{ optional($purchase->purchase_date)->format('d M Y') }}</div>
                <div class="subtitle">Status: {{ strtoupper((string) $purchase->status) }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <p class="meta-title">Supplier</p>
                <p class="meta-row"><strong>{{ $purchase->supplier->name ?? '-' }}</strong></p>
                <p class="meta-row">PIC: {{ $purchase->supplier->contact_person ?: '-' }}</p>
                <p class="meta-row">Telp: {{ $purchase->supplier->phone ?: '-' }}</p>
                <p class="meta-row">Alamat: {{ $purchase->supplier->address ?: '-' }}</p>
            </div>
            <div class="meta-card">
                <p class="meta-title">Informasi Dokumen</p>
                <p class="meta-row">Dibuat oleh: {{ $purchase->creator->name ?? '-' }}</p>
                <p class="meta-row">Tanggal dibuat: {{ optional($purchase->created_at)->format('d M Y H:i') }}</p>
                <p class="meta-row">
                    Diterima oleh:
                    @if($purchase->isReceived())
                        {{ $purchase->receiver->name ?? '-' }}
                    @else
                        -
                    @endif
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Produk</th>
                    <th style="width: 140px;">SKU</th>
                    <th class="text-right" style="width: 90px;">Qty</th>
                    <th class="text-right" style="width: 130px;">Harga</th>
                    <th class="text-right" style="width: 110px;">Diskon</th>
                    <th class="text-right" style="width: 140px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product->name ?? '-' }}</td>
                        <td>{{ $item->product->sku ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) $item->discount_amount, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>Subtotal</span>
                <strong>Rp {{ number_format((float) $purchase->subtotal, 0, ',', '.') }}</strong>
            </div>
            <div class="summary-row">
                <span>Pajak</span>
                <strong>Rp {{ number_format((float) $purchase->tax_amount, 0, ',', '.') }}</strong>
            </div>
            <div class="summary-row">
                <span>Diskon</span>
                <strong>Rp {{ number_format((float) $purchase->discount_amount, 0, ',', '.') }}</strong>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span>Rp {{ number_format((float) $purchase->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="notes-box">
            <p class="notes-title">Catatan</p>
            <p class="notes-content">{{ $purchase->notes ?: '-' }}</p>
        </div>

        <div class="signatures">
            <div class="sign-box">
                <p class="sign-label">Dibuat Oleh</p>
                <div class="sign-line">{{ $purchase->creator->name ?? '(..........................)' }}</div>
            </div>
            <div class="sign-box">
                <p class="sign-label">Disetujui</p>
                <div class="sign-line">(..........................)</div>
            </div>
            <div class="sign-box">
                <p class="sign-label">Supplier</p>
                <div class="sign-line">(..........................)</div>
            </div>
        </div>

        <div class="footer">
            Dicetak: {{ now()->format('d M Y H:i:s') }} oleh {{ auth()->user()->name ?? 'System' }}
        </div>
    </div>
</body>

</html>
