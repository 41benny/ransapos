<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - {{ $cashTransaction->transaction_number }}</title>
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
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 30px;
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
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
            margin-bottom: 20px;
        }

        .info-group {
            width: 48%;
        }

        .row {
            display: flex;
            margin-bottom: 8px;
        }

        .label {
            width: 120px;
            font-weight: bold;
        }

        .value {
            flex: 1;
            border-bottom: 1px dotted #ccc;
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
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
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
            margin-top: 30px;
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

    <div class="container">
        <div class="header">
            <div class="company-name">MORESTO</div>
            <div>Jalan Raya Sekincau, Lampung Barat</div>
            <div class="voucher-title">
                {{ $cashTransaction->type == 'in' ? 'BUKTI KAS MASUK' : 'BUKTI KAS KELUAR' }}
            </div>
        </div>

        <div class="voucher-info">
            <div class="info-group">
                <div class="row">
                    <span class="label">No. Transaksi</span>
                    <span class="value">: {{ $cashTransaction->transaction_number }}</span>
                </div>
                <div class="row">
                    <span class="label">Keterangan</span>
                    <span class="value">:
                        {{ $cashTransaction->reference_type ? ucfirst($cashTransaction->reference_type) . ' #' . $cashTransaction->reference_id : '-' }}</span>
                </div>
            </div>
            <div class="info-group">
                <div class="row">
                    <span class="label">Tanggal</span>
                    <span class="value">: {{ $cashTransaction->transaction_date->format('d M Y') }}</span>
                </div>
                <div class="row">
                    <span class="label">Akun Kas/Bank</span>
                    <span class="value">: {{ $cashTransaction->cashAccount->name }}
                        ({{ $cashTransaction->cashAccount->code }})</span>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <div class="row">
                <span class="label">Dibayarkan Kepada /<br>Diterima Dari</span>
                <span class="value" style="height: 40px; vertical-align: bottom; display: flex; align-items: end;">
                    : {{ $cashTransaction->description }}
                </span>
            </div>
            <div class="row">
                <span class="label">Akun Lawan (COA)</span>
                <span class="value">: {{ $cashTransaction->coaAccount->code ?? '-' }} -
                    {{ $cashTransaction->coaAccount->name ?? '-' }}</span>
            </div>
            <div class="row">
                <span class="label">Catatan</span>
                <span class="value">: {{ $cashTransaction->notes ?? '-' }}</span>
            </div>
        </div>

        <div class="amount-box">
            Rp {{ number_format($cashTransaction->amount, 2, ',', '.') }}
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
            Dicetak pada: {{ now()->format('d M Y H:i:s') }} oleh {{ auth()->user()->name ?? 'System' }} | Ref ID:
            {{ $cashTransaction->id }}
        </div>
    </div>
</body>

</html>