@extends('layouts.admin')

@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')
@section('page-subtitle', 'Ringkasan laba rugi berdasarkan periode')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Laporan Laba Rugi</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Analisis pendapatan, beban, dan performa laba unit
                    usaha</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'ikhtisar')]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-xs"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Periode
                    </h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ request('tab', 'ikhtisar') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="flex flex-col gap-1.5 lg:col-span-1">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Dari
                                Tanggal</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        <div class="flex flex-col gap-1.5 lg:col-span-1">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai
                                Tanggal</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>
                        <div class="flex flex-col gap-1.5 lg:col-span-2">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet
                                (Opsional)</label>
                            <select name="outlet_id"
                                class="w-full px-3 py-1.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Outlet</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ $outletId == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end lg:col-span-2 gap-2">
                            <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Tampilkan</span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                        <a href="{{ route('admin.reports.profit-loss.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-normal text-emerald-700 border border-emerald-100 shadow-sm transition-all hover:bg-emerald-100 active:scale-95">
                            <i class="fas fa-file-excel text-[10px]"></i>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('admin.reports.profit-loss.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-4 py-2 text-xs font-normal text-rose-700 border border-rose-100 shadow-sm transition-all hover:bg-rose-100 active:scale-95">
                            <i class="fas fa-file-pdf text-[10px]"></i>
                            <span>PDF</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Pendapatan -->
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-indigo-500">Total Pendapatan</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fas fa-hand-holding-usd text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp
                        {{ number_format($report['total_revenue'], 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Penjualan + Pendapatan Lain</p>
                </div>
            </div>

            <!-- Laba Kotor -->
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-emerald-500">Laba Kotor</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fas fa-chart-line text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp
                        {{ number_format($report['gross_profit'], 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] font-normal text-emerald-500 mt-0.5">Margin:
                        {{ number_format($report['gross_profit_margin'], 2) }}%
                    </p>
                </div>
            </div>

            <!-- Total Biaya -->
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all hover:shadow-md border-l-4 border-l-rose-500">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-rose-500">Total Biaya</span>
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition-colors group-hover:bg-rose-600 group-hover:text-white">
                        <i class="fas fa-wallet text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-slate-800">Rp
                        {{ number_format($report['total_expenses'], 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] font-normal text-slate-400 mt-0.5">Pengeluaran Operasional</p>
                </div>
            </div>

            <!-- Laba Bersih -->
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-900 p-4 shadow-lg transition-all hover:shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-normal uppercase tracking-[0.2em] text-indigo-300">Laba Bersih</span>
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/10 text-white">
                        <i class="fas fa-trophy text-xs"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-xl font-normal text-white">Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                    </h3>
                    <p class="text-[10px] font-normal text-indigo-300 mt-0.5">Net Margin:
                        {{ number_format($report['net_profit_margin'], 2) }}%
                    </p>
                </div>
                <div
                    class="absolute bottom-0 left-0 h-1 {{ $report['net_profit'] >= 0 ? 'bg-emerald-500' : 'bg-rose-500' }} w-full opacity-50">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-list-alt text-indigo-500 text-xs"></i>
                    <h2 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Rincian
                        Laporan
                        Laba Rugi</h2>
                </div>
                <button onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95 no-print">
                    <i class="fas fa-print text-[10px]"></i>
                    <span>Cetak Report</span>
                </button>
            </div>

            <div class="p-6">
                <!-- A. PENDAPATAN -->
                <div class="mb-6">
                    <h3 class="text-sm font-normal text-gray-700 mb-3">A. PENDAPATAN</h3>
                    <div class="pl-4">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-sm text-gray-900">Penjualan</span>
                            <span class="text-sm font-normal text-gray-900">
                                Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex justify-between py-2 bg-gray-100 font-normal mt-2">
                        <span>Total Pendapatan</span>
                        <span>Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- B. HARGA POKOK PENJUALAN -->
                <div class="mb-6">
                    <h3 class="text-sm font-normal text-gray-700 mb-3">B. HARGA POKOK PENJUALAN (HPP)</h3>
                    <div class="pl-4">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <span class="text-sm text-gray-900">HPP Penjualan</span>
                            <span class="text-sm font-normal text-red-600">
                                Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- C. LABA KOTOR -->
                <div class="mb-6 border-t-2 border-gray-400 pt-4">
                    <div class="flex justify-between py-3 bg-green-50 rounded-lg px-4">
                        <div>
                            <span class="font-normal text-green-800">LABA KOTOR</span>
                            <p class="text-xs text-green-600 mt-1">
                                Margin: {{ number_format($report['gross_profit_margin'], 2) }}%
                            </p>
                        </div>
                        <span class="text-xl font-normal text-green-800">
                            Rp {{ number_format($report['gross_profit'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- D. BIAYA OPERASIONAL -->
                <div class="mb-6">
                    <h3 class="text-sm font-normal text-gray-700 mb-3">C. BIAYA OPERASIONAL</h3>

                    @if(count($report['expenses_by_group']) > 0)
                        @foreach($report['expenses_by_group'] as $group)
                            <div class="mb-4 pl-4">
                                <h4 class="text-sm font-normal text-gray-600 mb-2">{{ $group['group_name'] }}</h4>

                                @foreach($group['accounts'] as $account)
                                    <div class="flex justify-between py-1 pl-4 text-sm text-gray-700">
                                        <span>{{ $account['code'] }} - {{ $account['name'] }}</span>
                                        <span>Rp {{ number_format($account['amount'], 0, ',', '.') }}</span>
                                    </div>
                                @endforeach

                                <div class="flex justify-between py-2 border-b border-gray-200 font-normal text-sm mt-1">
                                    <span class="pl-4">Subtotal {{ $group['group_name'] }}</span>
                                    <span>Rp {{ number_format($group['total'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach

                        <div class="flex justify-between py-2 bg-red-50 font-normal mt-2 rounded-lg px-4">
                            <span>Total Biaya Operasional</span>
                            <span class="text-red-800">Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}</span>
                        </div>
                    @else
                        <div class="pl-4">
                            <p class="text-sm text-gray-500 italic">Belum ada biaya operasional tercatat</p>
                            <div class="flex justify-between py-2 bg-gray-50 font-normal mt-2">
                                <span>Total Biaya Operasional</span>
                                <span>Rp 0</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- E. LABA BERSIH -->
                <div class="border-t-2 border-gray-400 pt-4">
                    <div class="flex justify-between py-4 bg-indigo-50 rounded-lg px-4">
                        <div>
                            <span class="text-lg font-normal text-indigo-900">LABA BERSIH</span>
                            <p class="text-xs text-indigo-600 mt-1">
                                Margin Laba Bersih: {{ number_format($report['net_profit_margin'], 2) }}%
                            </p>
                        </div>
                        <span
                            class="text-2xl font-normal {{ $report['net_profit'] >= 0 ? 'text-indigo-900' : 'text-red-600' }}">
                            Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <!-- Summary Calculation -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
                    <p class="font-normal text-gray-700 mb-2">Ringkasan Perhitungan:</p>
                    <p>Pendapatan: Rp {{ number_format($report['total_revenue'], 0, ',', '.') }}</p>
                    <p>HPP: Rp {{ number_format($report['total_cogs'], 0, ',', '.') }}</p>
                    <p class="border-t border-gray-300 mt-1 pt-1">Laba Kotor: Rp
                        {{ number_format($report['gross_profit'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1">Biaya Operasional: Rp {{ number_format($report['total_expenses'], 0, ',', '.') }}</p>
                    <p class="border-t-2 border-gray-400 mt-1 pt-1 font-normal text-gray-900">
                        Laba Bersih: Rp {{ number_format($report['net_profit'], 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 12px;
            }

            .container {
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
@endsection
