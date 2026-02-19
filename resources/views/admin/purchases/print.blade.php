<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #{{ $purchase->purchase_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e293b;
            --accent: #4f46e5;
            --text-main: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg-gray: #f8fafc;
            --white: #ffffff;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #f1f5f9;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .page-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: var(--white);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            position: relative;
        }

        .header-stripe {
            height: 6px;
            background: linear-gradient(90deg, var(--accent) 0%, #818cf8 100%);
        }

        .content-wrapper {
            padding: 40px;
        }

        /* Header Section */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .company-info h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }

        .company-subtitle {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-light);
            margin: 0;
        }

        .po-title {
            text-align: right;
        }

        .po-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .po-number {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -0.5px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border);
        }

        .info-card h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 600;
            color: var(--text-light);
            margin: 0 0 12px 0;
        }

        .info-content {
            font-size: 13px;
            color: var(--primary);
        }

        .info-row {
            margin-bottom: 4px;
        }

        .info-row strong {
            font-weight: 600;
        }

        /* Table Section */
        .table-container {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: var(--bg-gray);
            padding: 12px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 12px 16px;
            font-size: 13px;
            color: var(--primary);
            border-bottom: 1px solid var(--border);
        }

        td.mono {
            font-family: monospace;
            font-size: 12px;
            color: var(--text-light);
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Summary Section */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .summary-box {
            width: 300px;
            background: var(--bg-gray);
            border-radius: 8px;
            padding: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            margin-bottom: 10px;
            color: var(--text-main);
        }

        .summary-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #cbd5e1;
            font-weight: 700;
            font-size: 16px;
            color: var(--primary);
        }

        .summary-label {
            color: var(--text-light);
        }

        .total .summary-label {
            color: var(--primary);
        }

        /* Footer Info */
        .additional-info {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-top: auto;
        }

        .notes-section h4 {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 8px 0;
        }

        .notes-box {
            background: var(--bg-gray);
            border-radius: 8px;
            padding: 15px;
            font-size: 12px;
            color: var(--text-main);
            font-style: italic;
            border: 1px dashed var(--border);
        }

        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
            padding-top: 20px;
        }

        .sign-col {
            text-align: center;
            width: 30%;
        }

        .sign-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-light);
            margin-bottom: 60px;
        }

        .sign-line {
            border-top: 1px solid var(--border);
            padding-top: 8px;
            font-size: 12px;
            color: var(--primary);
            font-weight: 500;
        }

        /* Print Controls */
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            display: flex;
            gap: 10px;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-back {
            color: var(--text-main);
            background: var(--bg-gray);
            border: 1px solid var(--border);
        }

        .btn-print {
            color: white;
            background: var(--accent);
            border: 1px solid var(--accent);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        @media print {
            body {
                background: white;
            }

            .page-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
            }

            .no-print {
                display: none;
            }

            .header-stripe {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <a href="{{ route('admin.purchases.show', $purchase) }}" class="btn btn-back">← Kembali</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak PO</button>
    </div>

    <div class="page-container">
        <div class="header-stripe"></div>
        <div class="content-wrapper">
            <!-- Header -->
            <div class="header">
                <div class="company-info">
                    <h1>{{ $purchase->outlet->name ?? 'Outlet Name' }}</h1>
                    <p class="company-subtitle">{{ $purchase->outlet->address ?? 'Alamat Outlet belum diatur' }}</p>
                    <p class="company-subtitle">{{ $purchase->outlet->phone ?? '' }}</p>
                </div>
                <div class="po-title">
                    <div class="po-label">Purchase Order</div>
                    <div class="po-number">{{ $purchase->purchase_number }}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        {{ $purchase->purchase_date->format('d F Y') }}</div>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <h3>Kepada Supplier</h3>
                    <div class="info-content">
                        <div class="info-row"><strong>{{ $purchase->supplier->name }}</strong></div>
                        <div class="info-row">{{ $purchase->supplier->address ?? '-' }}</div>
                        <div class="info-row">Telp: {{ $purchase->supplier->phone ?? '-' }}</div>
                        <div class="info-row">PIC: {{ $purchase->supplier->contact_person ?? '-' }}</div>
                    </div>
                </div>
                <div class="info-card">
                    <h3>Detail Dokumen</h3>
                    <div class="info-content">
                        <div class="info-row"><span style="color: #64748b; display: inline-block; width: 80px;">Dibuat
                                Oleh</span> : {{ $purchase->creator->name ?? 'System' }}</div>
                        <div class="info-row"><span
                                style="color: #64748b; display: inline-block; width: 80px;">Status</span> : <span
                                style="text-transform: uppercase;">{{ $purchase->status }}</span></div>
                        <div class="info-row"><span style="color: #64748b; display: inline-block; width: 80px;">Tgl
                                Cetak</span> : {{ now()->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 35%;">Produk / Item</th>
                            <th style="width: 15%;">SKU</th>
                            <th class="text-right" style="width: 10%;">Qty</th>
                            <th class="text-right" style="width: 15%;">Harga Satuan</th>
                            <th class="text-right" style="width: 20%;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchase->items as $index => $item)
                            <tr>
                                <td class="text-center" style="color: #64748b;">{{ $index + 1 }}</td>
                                <td>
                                    <div style="font-weight: 500;">{{ $item->product->name }}</div>
                                </td>
                                <td class="mono">{{ $item->product->sku ?? '-' }}</td>
                                <td class="text-right" style="font-weight: 500;">
                                    {{ number_format($item->quantity, 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-right" style="font-weight: 600;">
                                    {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Section -->
            <div class="additional-info">
                <div>
                    <div class="notes-section">
                        <h4>Catatan / Instruksi</h4>
                        <div class="notes-box">
                            {{ $purchase->notes ?? 'Tidak ada catatan tambahan untuk pesanan ini.' }}
                        </div>
                    </div>
                </div>

                <div class="summary-section">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span style="font-weight: 600;">Rp
                                {{ number_format($purchase->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($purchase->discount_amount > 0)
                            <div class="summary-row">
                                <span class="summary-label">Diskon</span>
                                <span style="color: #ef4444;">- Rp
                                    {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        @if($purchase->tax_amount > 0)
                            <div class="summary-row">
                                <span class="summary-label">Pajak (Tax)</span>
                                <span>Rp {{ number_format($purchase->tax_amount, 0, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="summary-row total">
                            <span class="summary-label">TOTAL</span>
                            <span style="color: var(--accent);">Rp
                                {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="signatures">
                <div class="sign-col">
                    <div class="sign-title">Dibuat Oleh</div>
                    <div class="sign-line">{{ $purchase->creator->name ?? 'Administrator' }}</div>
                </div>
                <div class="sign-col">
                    <div class="sign-title">Disetujui Oleh</div>
                    <div class="sign-line">( Manager Operasional )</div>
                </div>
                <div class="sign-col">
                    <div class="sign-title">Diterima Supplier</div>
                    <div class="sign-line">( {{ $purchase->supplier->name }} )</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>