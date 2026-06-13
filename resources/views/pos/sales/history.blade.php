@extends('layouts.pos_theme')

@section('content')
    @php
        $printMode = (bool) ($printMode ?? false);
    @endphp
    <div class="space-y-4">
        <div class="bg-surface-light rounded-2xl shadow-soft flex flex-col relative overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 no-print">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span class="material-icons-round text-primary text-xl">assessment</span>
                        Laporan Penjualan Kasir
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Rekap transaksi sesi open/closed milik Anda</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="printReportThermal()"
                        class="flex items-center gap-2 bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-all text-sm">
                        <span class="material-icons-round text-base">print</span>
                        Print
                    </button>
                    <a href="{{ route('pos.dashboard') }}"
                        class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-all text-sm">
                        <span class="material-icons-round text-base">arrow_back</span>
                        Kembali
                    </a>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-gray-100 print-only hidden">
                <h1 class="text-lg font-bold text-gray-900">Laporan Penjualan Kasir</h1>
                <p class="text-xs text-gray-600 mt-1">
                    Periode: {{ $filters['date_from'] ?? '-' }} s.d {{ $filters['date_to'] ?? '-' }}
                </p>
                @if(!empty($filters['q'] ?? ''))
                    <p class="text-xs text-gray-500 mt-1">Pencarian: {{ $filters['q'] }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Dicetak: {{ now()->format('d/m/Y H:i:s') }}</p>
            </div>

            <form method="GET" action="{{ route('pos.sales.history') }}" class="px-6 py-4 border-b border-gray-100 no-print">
                <input type="hidden" name="view" value="{{ $viewMode ?? 'invoice' }}">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <div>
                        <label for="date_from" class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Mulai</label>
                        <input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="date_to" class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Akhir</label>
                        <input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                    </div>
                    <div class="md:col-span-2">
                        <label for="q" class="block text-xs font-semibold text-gray-600 mb-1">Cari</label>
                        <div class="relative">
                            <span class="material-icons-round absolute left-3 top-1/2 -translate-y-1/2 text-base text-gray-400">search</span>
                            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                                placeholder="{{ ($viewMode ?? 'invoice') === 'product' ? 'Cari produk...' : 'Cari invoice, customer, produk...' }}"
                                class="w-full rounded-lg border-gray-300 pl-9 text-sm focus:border-primary focus:ring-primary">
                        </div>
                    </div>
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit"
                            class="inline-flex items-center gap-2 bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition">
                            <span class="material-icons-round text-base">filter_alt</span>
                            Terapkan Filter
                        </button>
                        <a href="{{ route('pos.sales.history') }}"
                            class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-sm transition">
                            <span class="material-icons-round text-base">refresh</span>
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/40 flex items-center gap-2 no-print">
                <a href="{{ route('pos.sales.history', ['view' => 'invoice', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'q' => $filters['q'] ?? '']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ ($viewMode ?? 'invoice') === 'invoice' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100' }}">
                    <span class="material-icons-round text-sm">receipt_long</span>
                    Per Invoice
                </a>
                <a href="{{ route('pos.sales.history', ['view' => 'product', 'date_from' => $filters['date_from'] ?? '', 'date_to' => $filters['date_to'] ?? '', 'q' => $filters['q'] ?? '']) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ ($viewMode ?? 'invoice') === 'product' ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-100' }}">
                    <span class="material-icons-round text-sm">inventory_2</span>
                    Per Produk
                </a>
                <span class="ml-auto text-[11px] text-gray-500">Maks. periode 1 bulan</span>
            </div>

            <div class="p-6 grid grid-cols-2 lg:grid-cols-4 gap-3 border-b border-gray-100">
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Transaksi Selesai</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($summary['transactions'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Transaksi Void</p>
                    <p class="text-xl font-bold text-red-700 mt-1">{{ number_format($summary['void_transactions'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Total Penjualan</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">Rp {{ number_format($summary['gross_sales'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Rata-rata Ticket</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">Rp {{ number_format($summary['avg_ticket'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            @if($paymentBreakdown->count() > 0)
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-800 mb-3">Ringkasan Metode Pembayaran</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($paymentBreakdown as $row)
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <p class="text-xs text-gray-500">{{ $row->method_name }}</p>
                                <p class="text-sm font-bold text-gray-900 mt-1">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">{{ number_format($row->payment_count, 0, ',', '.') }} pembayaran</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="p-0">
                @if(($viewMode ?? 'invoice') === 'product')
                    @if($productRows->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-gray-600 font-medium text-xs uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 border-b border-gray-100">Produk</th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-right">Qty Terjual</th>
                                        @foreach(($productPaymentMethods ?? []) as $method)
                                            <th class="px-6 py-3 border-b border-gray-100 text-right whitespace-nowrap">{{ $method->name }}</th>
                                        @endforeach
                                        <th class="px-6 py-3 border-b border-gray-100 text-right">Jumlah Transaksi</th>
                                        <th class="px-6 py-3 border-b border-gray-100 text-right">Total Penjualan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    @foreach($productRows as $row)
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900">{{ $row->product_name }}</td>
                                            <td class="px-6 py-4 text-right font-semibold text-gray-900">{{ number_format((float) $row->total_qty, 0, ',', '.') }}</td>
                                            @foreach(($productPaymentMethods ?? []) as $method)
                                                @php
                                                    $methodKey = (string) $method->id;
                                                    $methodAmount = (float) (($row->payment_amounts ?? [])[$methodKey] ?? 0);
                                                @endphp
                                                <td class="px-6 py-4 text-right text-gray-700 whitespace-nowrap">
                                                    {{ $methodAmount > 0 ? 'Rp ' . number_format($methodAmount, 0, ',', '.') : '-' }}
                                                </td>
                                            @endforeach
                                            <td class="px-6 py-4 text-right text-gray-700">{{ number_format((int) $row->total_transactions, 0, ',', '.') }}</td>
                                            <td class="px-6 py-4 text-right font-bold text-gray-900">Rp {{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($productRows, 'links'))
                            <div class="px-6 py-4 border-t border-gray-100 no-print">
                                {{ $productRows->links() }}
                            </div>
                        @endif
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                                <span class="material-icons-round text-3xl">inventory_2</span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Belum ada data produk</h3>
                            <p class="text-gray-500 text-sm max-w-sm mt-1">Tidak ada produk terjual pada periode yang dipilih.</p>
                        </div>
                    @endif
                @elseif($sales->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 font-medium text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-100">Invoice</th>
                                    <th class="px-6 py-3 border-b border-gray-100">Waktu</th>
                                    <th class="px-6 py-3 border-b border-gray-100">Item</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-right">Qty</th>
                                    <th class="px-6 py-3 border-b border-gray-100">Pelanggan</th>
                                    <th class="px-6 py-3 border-b border-gray-100">Metode Bayar</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-right">Total</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-center">Status</th>
                                    <th class="px-6 py-3 border-b border-gray-100 text-center no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                @foreach($sales as $sale)
                                    @php
                                        $items = $sale->items;
                                        $rowCount = max($items->count(), 1);
                                    @endphp

                                    @if($items->count() > 0)
                                        @foreach($items as $index => $item)
                                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                                @if($index === 0)
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 font-mono font-medium text-gray-900 align-top">
                                                        {{ $sale->invoice_number }}
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 text-gray-500 align-top">
                                                        {{ $sale->created_at->format('d M Y H:i') }}
                                                    </td>
                                                @endif

                                                <td class="px-6 py-4 text-gray-700 text-xs">
                                                    <span class="font-medium">{{ $item->product_name ?? '-' }}</span>
                                                </td>
                                                <td class="px-6 py-4 text-right font-semibold text-gray-800 align-top">
                                                    {{ number_format((float) $item->quantity, 0, ',', '.') }}
                                                </td>

                                                @if($index === 0)
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 text-gray-900 align-top">
                                                        {{ $sale->resolved_customer_name }}
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 text-gray-600 align-top">
                                                        @foreach($sale->payments as $payment)
                                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 text-xs mb-1">
                                                                {{ $payment->paymentMethod?->name ?? '-' }}
                                                            </span>
                                                        @endforeach
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 font-bold text-gray-900 text-right align-top">
                                                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 text-center align-top">
                                                        @if($sale->status == 'completed')
                                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                                Selesai
                                                            </span>
                                                        @elseif($sale->status == 'cancelled')
                                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-100">
                                                                Void
                                                            </span>
                                                        @else
                                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                                {{ ucfirst($sale->status) }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td rowspan="{{ $rowCount }}" class="px-6 py-4 text-center align-top no-print">
                                                        <div class="flex items-center justify-center gap-2 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <a href="{{ route('pos.sales.print', $sale->id) }}" target="_blank"
                                                                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Print Struk">
                                                                <span class="material-icons-round text-xl">print</span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="hover:bg-gray-50/50 transition-colors group">
                                            <td class="px-6 py-4 font-mono font-medium text-gray-900">
                                                {{ $sale->invoice_number }}
                                            </td>
                                            <td class="px-6 py-4 text-gray-500">
                                                {{ $sale->created_at->format('d M Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 text-gray-700 text-xs">-</td>
                                            <td class="px-6 py-4 text-right font-semibold text-gray-800">0</td>
                                            <td class="px-6 py-4 text-gray-900">
                                                {{ $sale->resolved_customer_name }}
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                @foreach($sale->payments as $payment)
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 text-xs mb-1">
                                                        {{ $payment->paymentMethod?->name ?? '-' }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="px-6 py-4 font-bold text-gray-900 text-right">
                                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($sale->status == 'completed')
                                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                        Selesai
                                                    </span>
                                                @elseif($sale->status == 'cancelled')
                                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-100">
                                                        Void
                                                    </span>
                                                @else
                                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                        {{ ucfirst($sale->status) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-center no-print">
                                                <a href="{{ route('pos.sales.print', $sale->id) }}" target="_blank"
                                                    class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Print Struk">
                                                    <span class="material-icons-round text-xl">print</span>
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($sales, 'links'))
                        <div class="px-6 py-4 border-t border-gray-100 no-print">
                            {{ $sales->links() }}
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                            <span class="material-icons-round text-3xl">history_toggle_off</span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Belum ada data</h3>
                        <p class="text-gray-500 text-sm max-w-sm mt-1">Belum ada transaksi kasir pada periode yang dipilih.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .print-only { display: none; }

        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: #fff !important; }
            .shadow-soft { box-shadow: none !important; }
            .rounded-2xl { border-radius: 0 !important; }
        }
    </style>

    @if($printMode)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 120);
            });
        </script>
    @else
        <script>
            // Cetak Laporan ke printer thermal Bluetooth (mengikuti mode print yang
            // tersimpan dari halaman kasir). Fallback ke dialog browser bila bukan thermal.
            (function () {
                const OUTLET_ID = @json(auth()->user()->outlet_id ?? null);
                const USER_ID = @json(auth()->id() ?? null);
                const SERVICE_UUIDS = [0xFFE0, 0xFF00, 0x18F0, 0xFEE7,
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455', '0000ff00-0000-1000-8000-00805f9b34fb'];

                function storageKey() {
                    const o = OUTLET_ID ? `outlet_${OUTLET_ID}` : 'outlet_unknown';
                    const u = USER_ID ? `user_${USER_ID}` : 'user_unknown';
                    return `Ransa_pos_print_settings_${o}_${u}`;
                }
                function getPrintEngine() {
                    const allowed = ['browser', 'bridge', 'rawbt', 'webbt', 'ransapos_android'];
                    const read = (raw) => {
                        if (!raw) return null;
                        try { const p = JSON.parse(raw); return allowed.includes(p.printEngine) ? p.printEngine : null; }
                        catch (e) { return null; }
                    };
                    let e = read(localStorage.getItem(storageKey()));
                    if (e) return e;
                    const suffix = USER_ID ? `_user_${USER_ID}` : null;
                    let any = null;
                    for (let i = 0; i < localStorage.length; i++) {
                        const k = localStorage.key(i);
                        if (!k || k.indexOf('Ransa_pos_print_settings_') !== 0) continue;
                        const v = read(localStorage.getItem(k));
                        if (!v) continue;
                        if (suffix && k.endsWith(suffix)) return v;
                        any = any || v;
                    }
                    return any || 'browser';
                }
                function escposUrl() {
                    const params = new URLSearchParams(window.location.search || '');
                    params.set('print', '1');
                    params.set('format', 'escpos');
                    return window.location.pathname + '?' + params.toString();
                }
                async function fetchBase64() {
                    const r = await fetch(escposUrl(), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    const text = await r.text();
                    if (!r.ok) throw new Error('server balas HTTP ' + r.status);
                    let d;
                    try { d = JSON.parse(text); }
                    catch (e) { throw new Error('respon bukan JSON (kode rekap belum ter-deploy di server?)'); }
                    if (!d || !d.base64) throw new Error('data ESC/POS kosong');
                    return d.base64;
                }
                function base64ToBytes(b64) {
                    const b = atob(b64);
                    const a = new Uint8Array(b.length);
                    for (let i = 0; i < b.length; i++) a[i] = b.charCodeAt(i);
                    return a;
                }
                function openRansaposAndroidPrint(base64, jobId) {
                    window.location.href = 'ransaposprint://print'
                        + '?job_id=' + encodeURIComponent(jobId || ('RECAP-' + Date.now()))
                        + '&source=ransapos'
                        + '&base64=' + encodeURIComponent(base64);
                }
                async function discover(device) {
                    const s = await device.gatt.connect();
                    const svcs = await s.getPrimaryServices();
                    for (const svc of svcs) {
                        let chs = [];
                        try { chs = await svc.getCharacteristics(); } catch (e) { continue; }
                        const w = chs.find(c => c.properties.write) || chs.find(c => c.properties.writeWithoutResponse);
                        if (w) return w;
                    }
                    return null;
                }
                async function getCharacteristic() {
                    if (!navigator.bluetooth) {
                        alert('Browser ini tidak mendukung Web Bluetooth, atau halaman tidak diakses lewat koneksi aman (HTTPS/localhost).');
                        return null;
                    }
                    if (typeof navigator.bluetooth.getDevices === 'function') {
                        try {
                            const ds = await navigator.bluetooth.getDevices();
                            for (const d of ds) {
                                try { const c = await discover(d); if (c) return c; } catch (e) { }
                            }
                        } catch (e) { }
                    }
                    try {
                        const d = await navigator.bluetooth.requestDevice({ acceptAllDevices: true, optionalServices: SERVICE_UUIDS });
                        return await discover(d);
                    } catch (e) { return null; }
                }
                async function writeBytes(ch, bytes) {
                    const size = 20;
                    const withResponse = !!(ch.properties && ch.properties.write)
                        && typeof ch.writeValueWithResponse === 'function';
                    for (let i = 0; i < bytes.length; i += size) {
                        const c = bytes.slice(i, i + size);
                        if (withResponse) {
                            await ch.writeValueWithResponse(c);
                        } else {
                            await ch.writeValue(c);
                            await new Promise(r => setTimeout(r, 25));
                        }
                    }
                }

                window.printReportThermal = async function () {
                    const engine = getPrintEngine();

                    if (engine === 'rawbt') {
                        try {
                            const b = await fetchBase64();
                            window.location.href = 'intent:base64,' + b + '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';
                            return;
                        } catch (e) { console.error('RawBT gagal:', e); }
                    }

                    if (engine === 'ransapos_android') {
                        try {
                            const b = await fetchBase64();
                            openRansaposAndroidPrint(b, 'RECAP-' + Date.now());
                            return;
                        } catch (e) { console.error('Ransapos Printer Service gagal:', e); }
                    }

                    if (engine === 'webbt') {
                        let ch = null;
                        try { ch = await getCharacteristic(); } catch (e) { ch = null; }
                        if (ch) {
                            try {
                                const b = await fetchBase64();
                                await writeBytes(ch, base64ToBytes(b));
                                return;
                            } catch (e) {
                                console.error('Cetak thermal gagal:', e);
                                alert('Gagal cetak laporan ke printer thermal: ' + (e && e.message ? e.message : e) + '\n\nMembuka cetak biasa.');
                            }
                        } else {
                            alert('Printer Bluetooth tidak tersedia. Membuka cetak biasa.');
                        }
                    }

                    window.print();
                };
            })();
        </script>
    @endif
@endsection
