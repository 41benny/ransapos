<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            background: #f8fafc;
        }

        .page {
            width: 100%;
            min-height: 100vh;
            padding: 16px;
            background: #fff;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
        }

        .toolbar button {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: #0f172a;
            cursor: pointer;
            font-size: 12px;
            padding: 8px 12px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
            line-height: 1.2;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 3px 14px;
            margin-bottom: 10px;
            color: #334155;
            font-size: 9px;
        }

        .meta strong {
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            vertical-align: top;
            word-break: break-word;
        }

        th {
            background: #e5e7eb;
            color: #111827;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            font-size: 9px;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .empty {
            height: 22px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                min-height: auto;
                padding: 0;
            }

            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="toolbar">
            <button type="button" onclick="window.print()">Print A4</button>
        </div>

        <h1>{{ $title }}</h1>
        <section class="meta">
            @foreach($meta as $label => $value)
                @if($value !== null && $value !== '')
                    <div><strong>{{ $label }}:</strong> {{ $value }}</div>
                @endif
            @endforeach
            <div><strong>Generated:</strong> {{ $generatedAt }}</div>
        </section>

        <table>
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column['label'] ?? $column['key'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($columns as $column)
                            @php
                                $value = data_get($row, $column['key']);
                                $isNumber = ($column['type'] ?? 'text') === 'number';
                            @endphp
                            <td class="{{ $isNumber ? 'number' : '' }} {{ $value === null || $value === '' ? 'empty' : '' }}">
                                @if($isNumber && is_numeric($value))
                                    {{ number_format((float) $value, (int) ($column['decimals'] ?? 0), ',', '.') }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" style="text-align: center;">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
