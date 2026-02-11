@extends('layouts.admin')

@section('title', $report['title'])
@section('page-title', $report['title'])
@section('page-subtitle', 'Detail laporan dari katalog')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-sm text-slate-500">Kode laporan: {{ $slug }}</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $report['title'] }}</div>
            </div>
            <a href="{{ route('admin.reports.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Kembali ke Katalog
            </a>
        </div>

        <form method="GET" class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Outlet</label>
                <select name="outlet_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) $outletId === (string) $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex h-10 items-center rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white hover:bg-indigo-800">
                    Tampilkan
                </button>
                @if(!empty($report['existing_route']) && Route::has($report['existing_route']))
                    <a href="{{ route($report['existing_route']) }}"
                        class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        Versi Lama
                    </a>
                @endif
            </div>
        </form>
    </div>

    @php
        $auditBase = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
        if (!empty($outletId)) {
            $auditBase['outlet_id'] = $outletId;
        }
    @endphp

    @if($viewType === 'payment-method')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Transaksi</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Nilai</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Metode Pembayaran</th>
                            <th class="px-4 py-3 text-right">Total Transaksi</th>
                            <th class="px-4 py-3 text-right">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $row->payment_method_name }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_transactions) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada data untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($viewType === 'sales-summary')
        @php
            $fmt = fn($amount) => 'Rp. ' . number_format((float) $amount, 2, ',', '.');
        @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="border-b border-slate-200 pb-4">
                <h3 class="text-xl font-bold text-slate-900">Ringkasan Penjualan</h3>
                <div class="mt-1 text-sm text-slate-600">
                    {{ \Carbon\Carbon::parse($summary['date_from'] ?? $dateFrom)->format('d/m/Y') }}
                    -
                    {{ \Carbon\Carbon::parse($summary['date_to'] ?? $dateTo)->format('d/m/Y') }}
                </div>
                <div class="mt-1 text-sm font-semibold text-slate-700">
                    {{ $summary['selected_outlet_name'] ?? 'Semua Outlet' }}
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200">
                <div class="grid grid-cols-1 divide-y divide-slate-100 text-sm md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="space-y-2 p-4">
                        <div class="flex items-center justify-between"><span>Total Sales</span><a class="font-semibold text-slate-900 hover:text-indigo-700" href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">{{ $fmt($summary['total_sales'] ?? 0) }}</a></div>
                        <div class="flex items-center justify-between"><span>Total Discount</span><span class="font-semibold text-slate-900">{{ $fmt($summary['total_discount'] ?? 0) }}</span></div>
                        <div class="flex items-center justify-between"><span>Total Service Charge</span><span class="font-semibold text-slate-900">{{ $fmt($summary['total_service_charge'] ?? 0) }}</span></div>
                        <div class="flex items-center justify-between"><span>Total Tax</span><span class="font-semibold text-slate-900">{{ $fmt($summary['total_tax'] ?? 0) }}</span></div>
                        <div class="flex items-center justify-between"><span>Total Adjustment</span><span class="font-semibold text-slate-900">{{ $fmt($summary['total_adjustment'] ?? 0) }}</span></div>
                        <div class="border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between"><span class="font-bold">TOTAL</span><a class="font-bold text-slate-900 hover:text-indigo-700" href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">{{ $fmt($summary['total_amount'] ?? 0) }}</a></div>
                        </div>
                    </div>
                    <div class="space-y-4 p-4">
                        <div>
                            <div class="mb-2 text-sm font-semibold text-slate-800">Invoices</div>
                            <div class="flex items-center justify-between text-sm"><span>Number of Invoices</span><span class="font-semibold">{{ number_format($summary['total_transactions'] ?? 0) }}</span></div>
                            <div class="flex items-center justify-between text-sm"><span>Average Bill per Invoice</span><span class="font-semibold">{{ $fmt($summary['avg_transaction'] ?? 0) }}</span></div>
                        </div>
                        <div class="border-t border-slate-200 pt-3">
                            <div class="mb-2 text-sm font-semibold text-slate-800">Void Summary</div>
                            <div class="flex items-center justify-between text-sm"><span>Number of Invoices</span><span class="font-semibold">{{ number_format($summary['void_invoices'] ?? 0) }}</span></div>
                            <div class="flex items-center justify-between text-sm"><span>Number of Items</span><span class="font-semibold">{{ number_format($summary['void_items'] ?? 0, 0, ',', '.') }}</span></div>
                            <div class="flex items-center justify-between text-sm"><span>TOTAL</span><span class="font-semibold">{{ $fmt($summary['void_total'] ?? 0) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Summary By Sales Type</div>
                    <div class="space-y-1 text-sm">
                        @forelse($summary['sales_type_rows'] ?? [] as $typeRow)
                            <div class="flex items-center justify-between">
                                <span>{{ ucfirst($typeRow->sales_type) }}</span>
                                <span class="font-semibold">{{ $fmt($typeRow->total_amount) }}</span>
                            </div>
                        @empty
                            <div class="text-slate-500">Belum ada data.</div>
                        @endforelse
                        <div class="border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold">TOTAL</span>
                                <span class="font-bold">{{ $fmt($summary['total_amount'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Summary By Pax</div>
                    <div class="space-y-1 text-sm">
                        <div class="flex items-center justify-between"><span>Total Pax</span><span class="font-semibold">{{ number_format($summary['total_pax'] ?? 0) }}</span></div>
                        <div class="flex items-center justify-between"><span>Average Pax per Day</span><span class="font-semibold">{{ number_format($summary['avg_pax_per_day'] ?? 0, 2, ',', '.') }}</span></div>
                        <div class="flex items-center justify-between"><span>Average Bill per Pax</span><span class="font-semibold">{{ $fmt($summary['avg_bill_per_pax'] ?? 0) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Summary By Payment</div>
                    <div class="space-y-1 text-sm">
                        @forelse($summary['payment_rows'] ?? [] as $paymentRow)
                            <div class="flex items-center justify-between">
                                <span>{{ $paymentRow->payment_method_name }}</span>
                                <span class="font-semibold">{{ $fmt($paymentRow->total_amount) }}</span>
                            </div>
                        @empty
                            <div class="text-slate-500">Belum ada data pembayaran.</div>
                        @endforelse
                        <div class="border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold">TOTAL</span>
                                <span class="font-bold">{{ $fmt($summary['total_amount'] ?? 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 text-sm font-semibold text-slate-800">Summary By Product</div>
                    <div class="max-h-72 overflow-y-auto space-y-1 text-sm">
                        @forelse($summary['product_rows'] ?? [] as $productRow)
                            <div class="grid grid-cols-[1fr_auto_auto_auto] items-center gap-3 border-b border-slate-100 py-1">
                                <span>{{ $productRow->product_name }}</span>
                                <span class="text-xs text-slate-500">{{ ucfirst($productRow->sales_type ?? 'Normal') }}</span>
                                <span class="text-xs font-semibold">x{{ number_format($productRow->total_qty, 0, ',', '.') }}</span>
                                <a class="text-right font-semibold hover:text-indigo-700"
                                    href="{{ route('admin.reports.sales.products', array_filter($auditBase)) }}">
                                    {{ $fmt($productRow->total_amount) }}
                                </a>
                            </div>
                        @empty
                            <div class="text-slate-500">Belum ada data produk.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @elseif($viewType === 'balance-sheet-final')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-slate-900">Ringkasan Neraca</h3>
                @if($summary['totals']['is_balanced'] ?? false)
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Balance</span>
                @else
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Belum Balance</span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Aset</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['totals']['asset'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'asset']))) }}">
                        Audit Aset
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Kewajiban</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['totals']['liability'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'liability']))) }}">
                        Audit Kewajiban
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Ekuitas</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['totals']['equity'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'equity']))) }}">
                        Audit Ekuitas
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Kewajiban + Ekuitas</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['totals']['liability_equity'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Selisih Balance</div>
                    <div class="mt-1 text-xl font-bold {{ ($summary['totals']['is_balanced'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                        Rp {{ number_format($summary['totals']['difference'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach(['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Ekuitas'] as $type => $label)
                    <div class="rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                            {{ $label }}
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-white text-left text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2">Akun</th>
                                        <th class="px-4 py-2 text-right">Mutasi Periode</th>
                                        <th class="px-4 py-2 text-right">Saldo s/d {{ $dateTo }}</th>
                                        <th class="px-4 py-2 text-right">Audit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse(($summary['sections'][$type]['rows'] ?? []) as $row)
                                        <tr>
                                            <td class="px-4 py-2">
                                                <div class="font-medium text-slate-800">{{ $row['code'] }} - {{ $row['name'] }}</div>
                                                <div class="text-xs text-slate-500">{{ $row['group'] }}</div>
                                            </td>
                                            <td class="px-4 py-2 text-right {{ $row['movement_in_period'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                Rp {{ number_format($row['movement_in_period'], 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-slate-900">
                                                Rp {{ number_format($row['balance'], 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <a class="inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                                                    href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_account_id' => $row['id']]))) }}">
                                                    Lihat Transaksi
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-5 text-center text-slate-500">Belum ada akun aktif.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <td class="px-4 py-2 text-sm font-semibold text-slate-700" colspan="3">Total {{ $label }}</td>
                                        <td class="px-4 py-2 text-right text-sm font-bold text-slate-900">
                                            Rp {{ number_format($summary['sections'][$type]['total'] ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    Kontrol Rekonsiliasi
                </div>
                <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="text-xs font-semibold uppercase text-slate-500">Saldo Kas & Bank (Kontrol)</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">
                            Rp {{ number_format($summary['controls']['cash_bank_as_of'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                            Audit Kas & Bank Detil
                        </a>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                        <div class="text-xs font-semibold uppercase text-slate-500">Selisih Aset vs Kontrol Kas/Bank</div>
                        <div class="mt-1 text-lg font-bold {{ abs((float) ($summary['controls']['asset_vs_cash_bank_gap'] ?? 0)) < 0.01 ? 'text-emerald-700' : 'text-amber-700' }}">
                            Rp {{ number_format($summary['controls']['asset_vs_cash_bank_gap'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                    Catatan Validasi
                </div>
                <div class="p-4">
                    <ul class="space-y-2 text-sm text-slate-700">
                        @foreach(($meta['notes'] ?? []) as $note)
                            <li class="flex items-start gap-2">
                                <span class="mt-1 inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                                <span>{{ $note }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @elseif($viewType === 'profit-loss')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Pendapatan</div>
                    <a class="mt-2 inline-flex text-2xl font-bold text-slate-900 hover:text-indigo-700"
                        href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">
                        Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">HPP</div>
                    <a class="mt-2 inline-flex text-2xl font-bold text-rose-700 hover:text-indigo-700"
                        href="{{ route('admin.stocks.mutations', array_filter([
                            'start_date' => $dateFrom,
                            'end_date' => $dateTo,
                            'outlet_id' => $outletId,
                            'reference_scope' => 'sales_cogs',
                        ])) }}">
                        Rp {{ number_format($summary['total_cogs'] ?? 0, 0, ',', '.') }}
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Laba Kotor</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-700">Rp {{ number_format($summary['gross_profit'] ?? 0, 0, ',', '.') }}</div>
                    <div class="mt-1 text-xs text-slate-500">Margin {{ number_format($summary['gross_profit_margin'] ?? 0, 2) }}%</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Laba Bersih</div>
                    <div class="mt-2 text-2xl font-bold {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-indigo-700' : 'text-rose-700' }}">
                        Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500">Margin {{ number_format($summary['net_profit_margin'] ?? 0, 2) }}%</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                        Ringkasan Laba Rugi
                    </div>
                    <div class="space-y-2 p-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-700">Pendapatan</span>
                            <a class="font-semibold text-slate-900 hover:text-indigo-700"
                                href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">
                                Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                            </a>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-700">HPP</span>
                            <a class="font-semibold text-rose-700 hover:text-indigo-700"
                                href="{{ route('admin.stocks.mutations', array_filter([
                                    'start_date' => $dateFrom,
                                    'end_date' => $dateTo,
                                    'outlet_id' => $outletId,
                                    'reference_scope' => 'sales_cogs',
                                ])) }}">
                                Rp {{ number_format($summary['total_cogs'] ?? 0, 0, ',', '.') }}
                            </a>
                        </div>
                        <div class="border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-800">Laba Kotor</span>
                                <span class="font-bold text-emerald-700">Rp {{ number_format($summary['gross_profit'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-700">Biaya Operasional</span>
                            <a class="font-semibold text-rose-700 hover:text-indigo-700"
                                href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
                                    'type' => 'out',
                                    'coa_type' => 'expense',
                                    'exclude_coa_group' => 'HPP',
                                ]))) }}">
                                Rp {{ number_format($summary['total_expenses'] ?? 0, 0, ',', '.') }}
                            </a>
                        </div>
                        <div class="border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-800">Laba Bersih</span>
                                <span class="font-bold {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-indigo-700' : 'text-rose-700' }}">
                                    Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">
                        Biaya Operasional per Grup COA
                    </div>
                    <div class="p-4">
                        @if(!empty($summary['expenses_by_group']) && count($summary['expenses_by_group']) > 0)
                            <div class="space-y-4">
                                @foreach($summary['expenses_by_group'] as $group)
                                    <div class="rounded-lg border border-slate-200 p-3">
                                        <div class="mb-2 flex items-center justify-between">
                                            <div class="text-sm font-semibold text-slate-800">{{ $group['group_name'] }}</div>
                                            <a class="text-sm font-bold text-slate-900 hover:text-indigo-700"
                                                href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
                                                    'type' => 'out',
                                                    'coa_type' => 'expense',
                                                    'coa_group' => $group['group_name'],
                                                ]))) }}">
                                                Rp {{ number_format($group['total'], 0, ',', '.') }}
                                            </a>
                                        </div>
                                        <div class="space-y-1">
                                            @foreach($group['accounts'] as $account)
                                                <div class="flex items-center justify-between text-xs text-slate-600">
                                                    <span>{{ $account['code'] }} - {{ $account['name'] }}</span>
                                                    <a class="font-semibold text-slate-700 hover:text-indigo-700"
                                                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
                                                            'type' => 'out',
                                                            'coa_account_id' => $account['id'] ?? null,
                                                        ]))) }}">
                                                        Rp {{ number_format($account['amount'], 0, ',', '.') }}
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada biaya operasional untuk periode ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @elseif($viewType === 'cash-bank-summary')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Kas</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_cash'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                        Audit Akun Kas
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Bank</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_bank'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                        Audit Per Akun
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Kas + Bank</div>
                    <div class="mt-1 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_balance'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Outlet</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3 text-right">Saldo s/d {{ $summary['as_of'] ?? $dateTo }}</th>
                            <th class="px-4 py-3 text-right">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $row->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $row->code }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                        {{ strtoupper($row->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format($row->ending_balance, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a class="inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['cash_account_id' => $row->id]))) }}">
                                        Lihat Transaksi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada akun kas/bank aktif.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($viewType === 'cash-bank-detail')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Saldo Awal Periode</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['beginning_balance'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Kas Masuk</div>
                    <div class="mt-1 text-xl font-bold text-emerald-700">Rp {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Kas Keluar</div>
                    <div class="mt-1 text-xl font-bold text-rose-700">Rp {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Saldo Akhir</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">Rp {{ number_format($summary['ending_balance'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Akun</th>
                            <th class="px-4 py-3">Outlet</th>
                            <th class="px-4 py-3 text-right">Saldo Awal</th>
                            <th class="px-4 py-3 text-right">Masuk</th>
                            <th class="px-4 py-3 text-right">Keluar</th>
                            <th class="px-4 py-3 text-right">Saldo Akhir</th>
                            <th class="px-4 py-3 text-right">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $row->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $row->code }} - {{ strtoupper($row->type) }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">Rp {{ number_format($row->beginning_balance, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">Rp {{ number_format($row->total_in, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-rose-700">Rp {{ number_format($row->total_out, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format($row->ending_balance, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a class="inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['cash_account_id' => $row->id]))) }}">
                                        Lihat Transaksi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada data mutasi kas/bank.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($viewType === 'ledger-detail')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Kas Masuk</div>
                    <div class="mt-1 text-xl font-bold text-emerald-700">Rp {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Kas Keluar</div>
                    <div class="mt-1 text-xl font-bold text-rose-700">Rp {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Net Mutasi</div>
                    <div class="mt-1 text-xl font-bold {{ ($summary['net'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        Rp {{ number_format($summary['net'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Jumlah Baris</div>
                    <div class="mt-1 text-xl font-bold text-slate-900">{{ number_format($summary['row_count'] ?? 0) }}</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">No Transaksi</th>
                            <th class="px-4 py-3">COA</th>
                            <th class="px-4 py-3">Kas/Bank</th>
                            <th class="px-4 py-3">Outlet</th>
                            <th class="px-4 py-3 text-right">Masuk</th>
                            <th class="px-4 py-3 text-right">Keluar</th>
                            <th class="px-4 py-3">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-slate-700">{{ \Carbon\Carbon::parse($row->transaction_date)->format('d M Y') }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $row->transaction_number }}</td>
                                <td class="px-4 py-3">
                                    @if($row->coa_code)
                                        <div class="font-medium text-slate-800">{{ $row->coa_code }} - {{ $row->coa_name }}</div>
                                    @else
                                        <span class="text-slate-500">Tanpa COA</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->cash_account_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">{{ $row->type === 'in' ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-3 text-right text-rose-700">{{ $row->type === 'out' ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $row->description }}
                                    @if($row->reference_type)
                                        <div class="text-xs text-slate-500">Ref: {{ $row->reference_type }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi ledger pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($viewType === 'cash-flow')
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Arus Masuk</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-700">Rp {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['type' => 'in']))) }}">
                        Audit Arus Masuk
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Total Arus Keluar</div>
                    <div class="mt-1 text-2xl font-bold text-rose-700">Rp {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}</div>
                    <a class="mt-2 inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['type' => 'out']))) }}">
                        Audit Arus Keluar
                    </a>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Net Arus Kas</div>
                    <div class="mt-1 text-2xl font-bold {{ ($summary['net'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        Rp {{ number_format($summary['net'] ?? 0, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Kelompok Arus Kas</th>
                            <th class="px-4 py-3 text-right">Masuk</th>
                            <th class="px-4 py-3 text-right">Keluar</th>
                            <th class="px-4 py-3 text-right">Net</th>
                            <th class="px-4 py-3 text-right">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $row->flow_group }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">Rp {{ number_format($row->total_in, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-rose-700">Rp {{ number_format($row->total_out, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold {{ $row->net_cash_flow >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    Rp {{ number_format($row->net_cash_flow, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a class="inline-flex text-xs font-semibold text-indigo-700 hover:text-indigo-900"
                                        href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_group' => $row->flow_group]))) }}">
                                        Lihat Transaksi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data arus kas pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="text-sm font-semibold text-amber-900">Halaman laporan sudah dibuat.</div>
            <div class="mt-1 text-sm text-amber-800">
                Query data untuk laporan ini belum diimplementasikan. Halaman ini siap dipakai untuk tahap integrasi data berikutnya.
            </div>
        </div>
    @endif
</div>
@endsection
