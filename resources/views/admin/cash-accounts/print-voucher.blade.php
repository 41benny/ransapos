<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $voucherNumber }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-in: #10b981;
            --brand-out: #f43f5e;
            --slate-900: #0f172a;
            --slate-700: #334155;
            --slate-500: #64748b;
            --slate-400: #94a3b8;
            --slate-200: #e2e8f0;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
        }

        @page {
            size: portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--slate-900);
            background: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-size: 11px;
        }

        .voucher-sheet {
            width: 100%;
            max-width: 210mm;
            height: auto;
            min-height: 145mm;
            margin: 0 auto;
            background: #fff;
            position: relative;
            overflow: hidden;
            border-radius: 4px;
            display: flex;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* Side Branding */
        .side-bar {
            width: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            color: rgba(255, 255, 255, 0.9);
            font-weight: 700;
            letter-spacing: 4px;
            text-transform: uppercase;
            font-size: 14px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px 30px;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .company-brand h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
            color: var(--slate-900);
            letter-spacing: -0.5px;
        }

        .company-brand p {
            margin: 2px 0 0;
            color: var(--slate-500);
            font-size: 10px;
            font-weight: 400;
        }

        .voucher-meta {
            text-align: right;
        }

        .voucher-meta .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--slate-400);
            font-weight: 600;
        }

        .voucher-meta .number {
            font-size: 16px;
            font-weight: 700;
            color: var(--slate-900);
            font-family: monospace;
            display: block;
            margin-top: 2px;
        }

        /* Info Dashboard */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
            background: var(--slate-50);
            padding: 10px 15px;
            border-radius: 12px;
            border: 1px solid var(--slate-100);
        }

        .dash-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .dash-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--slate-400);
            font-weight: 700;
        }

        .dash-value {
            font-size: 11px;
            font-weight: 600;
            color: var(--slate-700);
        }

        /* Table Style */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--slate-400);
            padding: 0 4px 6px 4px;
            font-weight: 600;
            border-bottom: 2px solid var(--slate-100);
        }

        .items-table td {
            padding: 3px 4px;
            font-size: 11px;
            color: var(--slate-700);
            border: none;
        }

        .items-table td:first-child {
            color: var(--slate-400);
            font-weight: 500;
            text-align: center;
            width: 30px;
        }

        .items-table td:last-child {
            font-weight: 700;
            text-align: right;
            color: var(--slate-900);
        }

        /* Bottom Section */
        .footer-action {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: auto;
        }

        .notes-and-ref {
            max-width: 60%;
        }

        .mini-label {
            font-size: 8px;
            text-transform: uppercase;
            color: var(--slate-400);
            font-weight: 700;
            margin-bottom: 4px;
            display: block;
        }

        .mini-text {
            font-size: 10px;
            color: var(--slate-500);
            font-style: italic;
        }

        .total-container {
            text-align: right;
        }

        .total-display {
            background: var(--slate-50);
            padding: 10px 20px;
            border-radius: 12px;
            color: var(--slate-900);
            border: 1px solid var(--slate-200);
        }

        .total-display .t-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            display: block;
            margin-bottom: 2px;
        }

        .total-display .t-amount {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* Signatures */
        .signatures {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-name {
            margin-top: 40px;
            font-size: 10px;
            font-weight: 600;
            color: var(--slate-900);
            border-top: 1px solid var(--slate-200);
            padding-top: 4px;
        }

        .sign-title {
            font-size: 8px;
            text-transform: uppercase;
            color: var(--slate-400);
            font-weight: 700;
        }

        /* UI Buttons */
        .controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-print {
            background: var(--slate-900);
            color: #fff;
        }

        .btn-back {
            background: #fff;
            color: var(--slate-900);
            border: 1px solid var(--slate-200);
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }

            .voucher-sheet {
                box-shadow: none;
                margin: 0;
                width: 100%;
                height: auto;
            }

            .controls {
                display: none;
            }
        }
    </style>
</head>

<body>
    @php
        $firstLine = $voucherTransactions->first() ?? $cashTransaction;
        $isIncome = $firstLine->type == 'in';
        $brandColor = $isIncome ? 'var(--brand-in)' : 'var(--brand-out)';
    @endphp

    <div class="controls">
        <a href="javascript:history.back()" class="btn btn-back">← Kembali</a>
        <button onclick="window.print()" class="btn btn-print">Cetak Voucher</button>
    </div>

    <div class="voucher-sheet">
        <div class="side-bar" style="background: {{ $brandColor }}">
            {{ $isIncome ? 'Cash Reciept' : 'Payment Voucher' }}
        </div>

        <div class="main-content">
            <header class="header">
                <div class="company-brand">
                    <h1>MORESTO</h1>
                    <p>Modern Resto Management System</p>
                </div>
                <div class="voucher-meta">
                    <span class="label">Dokumen Nomor</span>
                    <span class="number">{{ $voucherNumber }}</span>
                </div>
            </header>

            <section class="dashboard">
                <div class="dash-item">
                    <span class="dash-label">Tanggal Transaksi</span>
                    <span class="dash-value">{{ optional($firstLine->transaction_date)->format('d F Y') }}</span>
                </div>
                <div class="dash-item">
                    <span class="dash-label">Sumber Dana</span>
                    <span class="dash-value">{{ $firstLine->cashAccount->name }}</span>
                </div>
                <div class="dash-item">
                    <span class="dash-label">Ref. Sistem</span>
                    <span
                        class="dash-value">{{ $firstLine->reference_type ? ucfirst($firstLine->reference_type) : 'Manual Entry' }}</span>
                </div>
            </section>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Deskripsi Transaksi</th>
                        <th style="width: 150px;">COA / Kategori</th>
                        <th style="width: 120px;" class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($voucherTransactions as $index => $line)
                        <tr>
                            <td>{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $line->description }}</td>
                            <td>{{ $line->coaAccount->code ?? '-' }} <span
                                    style="font-size: 8px; color: var(--slate-400)">{{ Str::limit($line->coaAccount->name ?? '', 15) }}</span>
                            </td>
                            <td class="text-right">Rp {{ number_format($line->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="footer-action">
                <div class="notes-and-ref">
                    <span class="mini-label">Informasi Tambahan / Catatan</span>
                    <p class="mini-text">
                        {{ $firstLine->notes ?? 'Transaksi tervalidasi oleh sistem keuangan Moresto. Saldo akun telah diperbarui secara otomatis.' }}
                    </p>
                </div>

                <div class="total-container">
                    <div class="total-display" style="border-left: 4px solid {{ $brandColor }}">
                        <span class="t-label">Total {{ $isIncome ? 'Diterima' : 'Dibayarkan' }}</span>
                        <span class="t-amount">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="signatures">
                <div class="sign-box">
                    <span class="sign-title">Otorisasi</span>
                    <div class="sign-name">Manager Finance</div>
                </div>
                <div class="sign-box">
                    <span class="sign-title">Verifikasi</span>
                    <div class="sign-name">Accounting</div>
                </div>
                <div class="sign-box">
                    <span class="sign-title">Pelaksana</span>
                    <div class="sign-name">Cashier</div>
                </div>
                <div class="sign-box">
                    <span class="sign-title">{{ $isIncome ? 'Penyetor' : 'Penerima' }}</span>
                    <div class="sign-name">Pihak Luar</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>