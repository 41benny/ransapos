<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $voucherNumber }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 24px;
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .voucher-title {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            display: inline-block;
            border: 1px solid #333;
            padding: 5px 20px;
        }

        .voucher-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 16px;
        }

        .info-group {
            width: 50%;
        }

        .row {
            display: flex;
            margin-bottom: 8px;
        }

        .label {
            width: 130px;
            font-weight: bold;
        }

        .value {
            flex: 1;
            border-bottom: 1px dotted #ccc;
        }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }

        .lines-table th,
        .lines-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            vertical-align: top;
        }

        .lines-table th {
            background: #f8fafc;
            text-align: left;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
            white-space: nowrap;
        }

        .amount-box {
            background-color: #f9f9f9;
            border: 2px solid #333;
            padding: 10px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }

        .signatures {
            margin-top: 44px;
            display: flex;
            justify-content: space-between;
            padding: 0 10px;
        }

        .signature-box {
            text-align: center;
            width: 30%;
        }

        .signature-line {
            margin-top: 60px;
            border-top: 1px solid #333;
            padding-top: 5px;
        }

        .footer {
            margin-top: 26px;
            font-size: 10px;
            text-align: center;
            color: #777;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #4f46e5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        @media print {
            .print-btn {
                display: none;
            }

            .container {
                border: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }

            body {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <button class="print-btn" onclick="window.print()">Cetak Voucher</button>

    @php
        $firstLine = $voucherTransactions->first() ?? $cashTransaction;
        $notesList = $voucherTransactions
            ->pluck('notes')
            ->filter(fn ($note) => trim((string) $note) !== '')
            ->unique()
            ->values();
        $lineCount = $voucherTransactions->count();
    @endphp

    <div class="container">
        <div class="header">
            <div class="company-name">MORESTO</div>
            <div>Jalan Raya Sekincau, Lampung Barat</div>
            <div class="voucher-title">
                {{ $firstLine->type == 'in' ? 'BUKTI KAS MASUK' : 'BUKTI KAS KELUAR' }}
            </div>
        </div>

        <div class="voucher-info">
            <div class="info-group">
                <div class="row">
                    <span class="label">No. Voucher</span>
                    <span class="value">: {{ $voucherNumber }}</span>
                </div>
                <div class="row">
                    <span class="label">Jumlah Baris</span>
                    <span class="value">: {{ $lineCount }} baris</span>
                </div>
            </div>
            <div class="info-group">
                <div class="row">
                    <span class="label">Tanggal</span>
                    <span class="value">: {{ optional($firstLine->transaction_date)->format('d M Y') }}</span>
                </div>
                <div class="row">
                    <span class="label">Akun Kas/Bank</span>
                    <span class="value">: {{ $firstLine->cashAccount->name ?? '-' }}
                        ({{ $firstLine->cashAccount->code ?? '-' }})</span>
                </div>
            </div>
        </div>

        <table class="lines-table">
            <thead>
                <tr>
                    <th style="width: 52px;">No</th>
                    <th>Keterangan</th>
                    <th style="width: 220px;">Akun Lawan (COA)</th>
                    <th style="width: 150px;" class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucherTransactions as $index => $line)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $line->description }}</td>
                        <td>{{ $line->coaAccount->code ?? '-' }} - {{ $line->coaAccount->name ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format((float) $line->amount, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-bottom: 14px;">
            <div class="row">
                <span class="label">Keterangan</span>
                <span class="value">:
                    {{ $firstLine->reference_type ? ucfirst($firstLine->reference_type) . (filled($firstLine->reference_id) ? ' #' . $firstLine->reference_id : '') : '-' }}
                </span>
            </div>
            <div class="row">
                <span class="label">Catatan</span>
                <span class="value">:
                    @if($notesList->isEmpty())
                        -
                    @else
                        {{ $notesList->implode(' | ') }}
                    @endif
                </span>
            </div>
        </div>

        <div class="amount-box">
            TOTAL: Rp {{ number_format((float) $totalAmount, 2, ',', '.') }}
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div>Disetujui Oleh,</div>
                <div class="signature-line">Manager/Keuangan</div>
            </div>
            <div class="signature-box">
                <div>Dibayar/Diterima Oleh,</div>
                <div class="signature-line">Kasir</div>
            </div>
            <div class="signature-box">
                <div>Penerima/Penyetor,</div>
                <div class="signature-line">(.....................................)</div>
            </div>
        </div>

        <div class="footer">
            Dicetak pada: {{ now()->format('d M Y H:i:s') }} oleh {{ auth()->user()->name ?? 'System' }} | Voucher:
            {{ $voucherNumber }}
        </div>
    </div>
</body>

</html>
