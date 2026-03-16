@extends('layouts.admin')

@section('title', $report['title'])
@section('page-title', $report['title'])
@section('page-subtitle', 'Detail laporan dari katalog')

@section('content')
    <div class="w-full space-y-5 animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-normal uppercase tracking-widest text-slate-400">Kode Laporan:
                        {{ strtoupper($slug) }}
                    </div>
                    <div class="mt-1 text-2xl font-normal text-slate-800">{{ $report['title'] }}</div>
                </div>
                <a href="{{ route('admin.reports.index', ['tab' => request('tab')]) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-normal text-slate-700 hover:bg-slate-50 transition-colors shadow-sm">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Kembali ke Katalog
                </a>
            </div>

            @php
                $isStockMovement = $viewType === 'stock-movement';
                $isStockAdjustment = $viewType === 'stock-adjustment';
                $usesProductFilter = $isStockMovement || $isStockAdjustment;
                $outletColClass = $isStockAdjustment ? 'md:col-span-2' : ($usesProductFilter ? 'md:col-span-3' : 'md:col-span-3');
                $productColClass = $isStockAdjustment ? 'md:col-span-2' : 'md:col-span-3';
                $actionColClass = $isStockAdjustment ? 'md:col-span-2' : ($usesProductFilter ? 'md:col-span-2' : 'md:col-span-5');
                $selectedProductOption = collect($products ?? collect())->firstWhere('id', $selectedProductId ?? null);
                $selectedProductLabel = $selectedProductOption
                    ? $selectedProductOption->name . (!empty($selectedProductOption->sku) ? ' - ' . $selectedProductOption->sku : '')
                    : '';
            @endphp
            <form method="GET" class="mt-8 grid grid-cols-1 items-end gap-4 md:grid-cols-12">
                <input type="hidden" name="tab" value="{{ request('tab') }}">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-xs font-normal uppercase tracking-widest text-slate-400">Tanggal
                        Dari</label>
                    <div class="relative">
                        <i class="far fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 py-2.5 text-xs font-normal text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-xs font-normal uppercase tracking-widest text-slate-400">Tanggal
                        Sampai</label>
                    <div class="relative">
                        <i class="far fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 py-2.5 text-xs font-normal text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                    </div>
                </div>
                <div class="{{ $outletColClass }}">
                    <label
                        class="mb-1.5 block text-xs font-normal uppercase tracking-widest text-slate-400">Outlet</label>
                    <div class="relative">
                        <select name="outlet_id"
                            class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-normal text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                            <option value="">Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected((string) $outletId === (string) $outlet->id)>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                        <i
                            class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                    </div>
                </div>
                @if($usesProductFilter)
                    <div class="{{ $productColClass }}">
                        <label
                            class="mb-1.5 block text-xs font-normal uppercase tracking-widest text-slate-400">Produk</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" list="report-product-options" value="{{ $selectedProductLabel }}"
                                placeholder="Ketik nama atau SKU produk..." autocomplete="off"
                                data-report-product-input
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 py-2.5 text-xs font-normal text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                            <input type="hidden" name="product_id" value="{{ $selectedProductId ?? '' }}"
                                data-report-product-id>
                            <datalist id="report-product-options">
                                @foreach(($products ?? collect()) as $product)
                                    <option value="{{ $product->name }}{{ !empty($product->sku) ? ' - ' . $product->sku : '' }}"
                                        data-id="{{ $product->id }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                @endif
                @if($isStockAdjustment)
                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-xs font-normal uppercase tracking-widest text-slate-400">User Input</label>
                        <div class="relative">
                            <select name="user_id"
                                class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-xs font-normal text-slate-700 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                                <option value="">Semua User</option>
                                @foreach(($users ?? collect()) as $user)
                                    <option value="{{ $user->id }}" @selected((string) ($selectedUserId ?? '') === (string) $user->id)>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <i
                                class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        </div>
                    </div>
                @endif
                <div class="{{ $actionColClass }} flex items-center gap-2">
                    <button type="submit"
                        class="ui-btn ui-btn-primary flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-normal text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200">
                        <i class="fas fa-sync-alt text-xs"></i>
                        TAMPILKAN
                    </button>
                    <a href="{{ route('admin.reports.catalog.show', array_merge(['slug' => $slug], array_filter(request()->except('format')), ['format' => 'xlsx'])) }}"
                        class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-all shadow-sm"
                        title="Export Excel">
                        <i class="fas fa-file-excel"></i>
                    </a>
                    <a href="{{ route('admin.reports.catalog.show', array_merge(['slug' => $slug], array_filter(request()->except('format')), ['format' => 'pdf'])) }}"
                        class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-500 hover:bg-rose-100 transition-all shadow-sm"
                        title="Export PDF">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                    @if(!empty($report['existing_route']) && Route::has($report['existing_route']))
                        <a href="{{ route($report['existing_route'], array_filter(['tab' => request('tab')])) }}"
                            class="inline-flex h-[38px] items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-[10px] font-normal text-slate-400 hover:text-slate-600 transition-all hover:bg-slate-50"
                            title="Versi Lama">
                            OLD
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
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- KPI Cards --}}
                    <div
                        class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-lg">
                        <span class="block text-xs font-normal uppercase tracking-[0.2em] text-slate-400 mb-2">Total
                            Transactions</span>
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                                <i class="fas fa-receipt text-lg"></i>
                            </div>
                            <h3 class="text-3xl font-normal text-slate-800 tracking-tight">
                                {{ number_format($summary['total_transactions'] ?? 0) }}</h3>
                        </div>
                    </div>

                    <div
                        class="group relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:shadow-lg">
                        <span class="block text-xs font-normal uppercase tracking-[0.2em] text-slate-400 mb-2">Total
                            Amount</span>
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                                <i class="fas fa-coins text-lg"></i>
                            </div>
                            <h3 class="text-3xl font-normal text-slate-800 tracking-tight">Rp
                                {{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</h3>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-normal uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Metode Pembayaran</th>
                                <th class="px-4 py-3 text-right">Total Transaksi</th>
                                <th class="px-4 py-3 text-right">Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-normal text-slate-800">{{ $row->payment_method_name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_transactions) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-normal text-slate-900">Rp
                                        {{ number_format($row->total_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada data untuk filter yang
                                        dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($viewType === 'sales-summary')
            @php
                $fmt = fn($amount) => 'Rp. ' . number_format((float) $amount, 0, ',', '.');
                $fmtWithDec = fn($amount) => 'Rp. ' . number_format((float) $amount, 2, ',', '.');

                // Prepare Sales Type Data for Chart
                $totalSalesType = collect($summary['sales_type_rows'] ?? [])->sum('total_amount');
                $salesTypeLabels = collect($summary['sales_type_rows'] ?? [])->map(fn($r) => ucfirst(str_replace('_', ' ', $r->sales_type)))->toArray();
                $salesTypeData = collect($summary['sales_type_rows'] ?? [])->map(fn($r) => (float) $r->total_amount)->toArray();
                $dominantType = collect($summary['sales_type_rows'] ?? [])->sortByDesc('total_amount')->first();
                $dominantPercent = ($totalSalesType > 0 && $dominantType) ? round(($dominantType->total_amount / $totalSalesType) * 100, 1) : 0;

                // Pax Data
                $totalPax = $summary['total_pax'] ?? 0;
                $paxPercent = 70; // Placeholder for Capacity Utilization if not available
            @endphp

            <div class="space-y-6">
                {{-- Row 1: Sales Overview & Summary By Pax --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- Sales Overview --}}
                    <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-normal text-slate-800">Sales Overview</h3>
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-normal text-indigo-600">
                                {{ \Carbon\Carbon::parse($summary['date_from'] ?? $dateFrom)->format('d/m/Y') }} -
                                {{ \Carbon\Carbon::parse($summary['date_to'] ?? $dateTo)->format('d/m/Y') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-8 md:grid-cols-5">
                            <div class="md:col-span-3 space-y-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Gross Sales</span>
                                    <span
                                        class="font-normal text-slate-900 text-lg">{{ $fmt($summary['total_sales'] ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Total Discount</span>
                                    <div class="text-right">
                                        <span class="font-normal text-rose-500">{{ $fmt($summary['total_discount'] ?? 0) }}</span>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            Item {{ $fmt($summary['item_discount_total'] ?? 0) }} | Header
                                            {{ $fmt($summary['header_discount_total'] ?? 0) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Total Service Charge</span>
                                    <span
                                        class="font-normal text-slate-900">{{ $fmt($summary['total_service_charge'] ?? 0) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Total Tax</span>
                                    <span class="font-normal text-slate-900">{{ $fmt($summary['total_tax'] ?? 0) }}</span>
                                </div>
                                <div class="border-t border-slate-100 pt-4 mt-4 flex items-center justify-between">
                                    <span class="text-sm font-normal uppercase tracking-wider text-slate-800">Total Net
                                        Sales</span>
                                    <span
                                        class="text-2xl font-normal text-indigo-600">{{ $fmt($summary['total_amount'] ?? 0) }}</span>
                                </div>
                            </div>

                            <div class="md:col-span-2 space-y-4">
                                {{-- Invoices Sub-card --}}
                                <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">
                                            <i class="fas fa-file-invoice text-sm"></i>
                                        </div>
                                        <span class="text-sm font-normal text-slate-800">Invoices</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500">Count</span>
                                            <span
                                                class="font-normal text-slate-900 text-sm">{{ number_format($summary['total_transactions'] ?? 0) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500">Avg/Invoice</span>
                                            <span
                                                class="font-normal text-slate-900">{{ $fmt($summary['avg_transaction'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Void Summary Sub-card --}}
                                <div class="rounded-xl bg-slate-50 p-4 border border-slate-100">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                            <i class="fas fa-ban text-sm"></i>
                                        </div>
                                        <span class="text-sm font-normal text-slate-800">Void Summary</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500">Items</span>
                                            <span
                                                class="font-normal text-slate-900 text-sm">{{ number_format($summary['void_items'] ?? 0) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-slate-500">Total Void</span>
                                            <span
                                                class="font-normal text-rose-500">{{ $fmt($summary['void_total'] ?? 0) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary By Pax --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-lg font-normal text-slate-800">Summary By Pax</h3>
                            <i class="fas fa-users text-slate-300"></i>
                        </div>

                        <div class="flex flex-col items-center">
                            <div class="relative h-48 w-48 mb-8">
                                <canvas id="paxChart"></canvas>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-normal text-slate-800">{{ number_format($totalPax) }}</span>
                                    <span class="text-xs font-normal uppercase tracking-widest text-slate-400">Total
                                        Pax</span>
                                </div>
                            </div>

                            <div class="w-full space-y-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Avg Pax per Day</span>
                                    <span
                                        class="font-normal text-slate-900">{{ number_format($summary['avg_pax_per_day'] ?? 0, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-500">Avg Bill per Pax</span>
                                    <span
                                        class="font-normal text-slate-900 text-lg">{{ $fmtWithDec($summary['avg_bill_per_pax'] ?? 0) }}</span>
                                </div>

                                <div class="pt-4">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-xs font-normal uppercase tracking-widest text-slate-400">Capacity
                                            Utilization: {{ $paxPercent }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $paxPercent }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(($summary['discount_anomaly_transactions'] ?? 0) > 0)
                        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                            Audit diskon: {{ number_format($summary['discount_anomaly_transactions']) }} transaksi spesial
                            tidak memiliki promo/diskon aktual. Transaksi ini perlu direview terpisah dari diskon valid.
                        </div>
                    @endif
                </div>

                {{-- Row 2: Sales Type, Payment, Product --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- Summary By Sales Type --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-normal text-slate-800 mb-6">Summary By Sales Type</h3>

                        <div class="flex items-center gap-6 mb-8">
                            <div class="h-32 w-32 shrink-0">
                                <canvas id="salesTypeChart"></canvas>
                            </div>
                            <div>
                                @if($dominantType)
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="h-2 w-2 rounded-full bg-indigo-600"></div>
                                        <span class="text-xs font-normal text-slate-500">{{ ucfirst(str_replace('_', ' ', $dominantType->sales_type)) }}
                                            Sales Dominant</span>
                                    </div>
                                    <div class="text-3xl font-normal text-slate-800">{{ $dominantPercent }}%</div>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-3">
                            @foreach($summary['sales_type_rows'] ?? [] as $typeRow)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $typeRow->sales_type)) }}</span>
                                    <span class="font-normal text-slate-800">{{ $fmt($typeRow->total_amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Summary By Payment --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-normal text-slate-800 mb-6">Summary By Payment</h3>

                        <div class="flex flex-wrap gap-2 mb-6">
                            @php
                                $colors = ['bg-blue-100 text-blue-600', 'bg-emerald-100 text-emerald-600', 'bg-indigo-100 text-indigo-600', 'bg-amber-100 text-amber-600'];
                            @endphp
                            @foreach($summary['payment_rows'] ?? [] as $index => $paymentRow)
                                <span
                                    class="px-2 py-1 rounded-md text-[10px] font-normal uppercase {{ $colors[$index % count($colors)] }}">
                                    {{ $paymentRow->payment_method_name }}
                                </span>
                            @endforeach
                        </div>

                        <div class="space-y-4">
                            @forelse($summary['payment_rows'] ?? [] as $paymentRow)
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-1.5 rounded-full bg-slate-300"></div>
                                        <span class="text-slate-600">{{ $paymentRow->payment_method_name }}</span>
                                    </div>
                                    <span class="font-normal text-slate-800">{{ $fmt($paymentRow->total_amount) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-4 text-slate-400 italic text-sm">No payment data</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Summary By Product --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-normal text-slate-800">Summary By Product</h3>
                            <a href="{{ route('admin.reports.sales.products', array_filter($auditBase)) }}"
                                class="text-xs font-normal text-indigo-600 hover:text-indigo-800">View All</a>
                        </div>

                        <div class="space-y-4 max-h-[300px] overflow-y-auto pr-2 scrollbar-hide">
                            @forelse(collect($summary['product_rows'] ?? [])->take(5) as $productRow)
                                <div class="flex items-center justify-between group">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-sm font-normal text-slate-800 truncate max-w-[150px]">{{ $productRow->product_name }}</span>
                                            <span
                                                class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-normal uppercase">{{ $productRow->sales_type ?? 'REGULAR' }}</span>
                                        </div>
                                        <div class="text-sm text-slate-400 mt-0.5">Qty:
                                            {{ number_format($productRow->total_qty) }}
                                        </div>
                                    </div>
                                    <span
                                        class="text-sm font-normal text-slate-800 group-hover:text-indigo-600 transition-colors">{{ $fmt($productRow->total_amount) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-4 text-slate-400 italic text-sm">No product data</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Sales Type Chart
                        const salesTypeCtx = document.getElementById('salesTypeChart').getContext('2d');
                        new Chart(salesTypeCtx, {
                            type: 'doughnut',
                            data: {
                                labels: @json($salesTypeLabels),
                                datasets: [{
                                    data: @json($salesTypeData),
                                    backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                                    borderWidth: 0,
                                    cutout: '75%'
                                }]
                            },
                            options: {
                                plugins: { legend: { display: false } },
                                maintainAspectRatio: false
                            }
                        });

                        // Pax Chart
                        const paxCtx = document.getElementById('paxChart').getContext('2d');
                        new Chart(paxCtx, {
                            type: 'doughnut',
                            data: {
                                datasets: [{
                                    data: [{{ $totalPax }}, {{ max(0, 100 - $totalPax) }}], // Simplified visualization
                                    backgroundColor: ['#4F46E5', '#F1F5F9'],
                                    borderWidth: 0,
                                    cutout: '85%',
                                    circumference: 360,
                                    rotation: 0
                                }]
                            },
                            options: {
                                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                                maintainAspectRatio: false
                            }
                        });
                    });
                </script>
            @endpush

        @elseif($viewType === 'sales-discount')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-normal text-slate-800">Laporan Diskon Penjualan</h3>
                    <div class="flex rounded-lg bg-slate-100 p-1">
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'summary']) }}"
                            class="rounded-md px-3 py-1.5 text-xs font-medium transition-all {{ ($summary['view_mode'] ?? 'summary') === 'summary' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Ringkas
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'detail']) }}"
                            class="rounded-md px-3 py-1.5 text-xs font-medium transition-all {{ ($summary['view_mode'] ?? 'summary') === 'detail' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Detil
                        </a>
                    </div>
                </div>

                {{-- Cards --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5 mb-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-widest text-slate-400">Total Transaksi</div>
                        <div class="mt-1 text-2xl font-normal text-slate-900">
                            {{ number_format($summary['total_transactions'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-widest text-slate-400">Gross Value (Normal)</div>
                        <div class="mt-1 text-2xl font-normal text-slate-900">Rp
                            {{ number_format($summary['total_gross_value'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-widest text-slate-400">Total Diskon</div>
                        <div class="mt-1 text-2xl font-normal text-rose-600">Rp
                            {{ number_format($summary['total_discount'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-widest text-slate-400">Net Sales (Paid)</div>
                        <div class="mt-1 text-2xl font-normal text-indigo-600">Rp
                            {{ number_format($summary['total_net_sales'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-widest text-amber-600">Transaksi Anomali</div>
                        <div class="mt-1 text-2xl font-normal text-amber-700">
                            {{ number_format($summary['discount_anomaly_transactions'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                    Diskon efektif = diskon item + diskon header. Status <strong>Anomali</strong> berarti transaksi
                    spesial lama tampil di laporan, tetapi tidak memiliki promo atau diskon aktual.
                </div>

                @if(($summary['view_mode'] ?? 'summary') === 'summary')
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-normal uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Tipe Diskon</th>
                                    <th class="px-4 py-3">Status Data</th>
                                    <th class="px-4 py-3">Metode Penjualan</th>
                                    <th class="px-4 py-3 text-right">Jumlah Transaksi</th>
                                    <th class="px-4 py-3 text-right">Nilai Jual (Gross)</th>
                                    <th class="px-4 py-3 text-right">Total Diskon</th>
                                    <th class="px-4 py-3 text-right">Net Sales</th>
                                </tr>
                                <tr class="bg-white border-b border-slate-100 no-print">
                                    <td class="px-1 py-1 relative">
                                        <button type="button" data-report-table-clear-filters title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10">
                                            <i class="fas fa-times text-[10px]"></i>
                                        </button>
                                        <input type="text" data-name="filter_tipe_diskon" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_status_data" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_metode_penjualan" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_jumlah_transaksi" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_gross" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_diskon" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_net_sales" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-slate-800">{{ $row['discount_source_label'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">
                                            <span class="rounded px-2 py-0.5 text-xs font-medium {{ !empty($row['is_discount_anomaly']) ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                {{ $row['data_status_label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-700">{{ $row['sales_type_label'] }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row['transaction_count']) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700">Rp
                                            {{ number_format($row['gross_value'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-rose-600">Rp
                                            {{ number_format($row['effective_discount'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-indigo-600">Rp
                                            {{ number_format($row['net_sales'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="ui-table min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-normal uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Tipe Diskon</th>
                                    <th class="px-4 py-3">Status Data</th>
                                    <th class="px-4 py-3">No Transaksi</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Outlet</th>
                                    <th class="px-4 py-3">Metode Penjualan</th>
                                    <th class="px-4 py-3">Pelanggan</th>
                                    <th class="px-4 py-3 text-right">Nilai Jual (Gross)</th>
                                    <th class="px-4 py-3 text-right">Diskon</th>
                                    <th class="px-4 py-3 text-right">Bayar (Net)</th>
                                </tr>
                                <tr class="bg-white border-b border-slate-100 no-print">
                                    <td class="px-1 py-1 relative">
                                        <button type="button" data-report-table-clear-filters title="Reset filter tabel" class="absolute left-1 top-1 h-6 w-6 inline-flex items-center justify-center rounded bg-slate-50 text-slate-400 hover:text-rose-500 transition-all z-10">
                                            <i class="fas fa-times text-[10px]"></i>
                                        </button>
                                        <input type="text" data-name="filter_tipe_diskon" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full pl-7 pr-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_status_data" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_transaksi" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_tanggal" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_outlet" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_metode_penjualan" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_pelanggan" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_gross" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_diskon" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" data-name="filter_net_sales" data-report-table-filter-input placeholder="Cari..." class="filter-input w-full px-1 py-1.5 text-[10px] text-right bg-slate-50 border border-slate-100 rounded focus:ring-1 focus:ring-indigo-500">
                                    </td>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span
                                                class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $row['discount_source_label'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span
                                                class="rounded px-2 py-0.5 text-xs font-medium {{ !empty($row['is_discount_anomaly']) ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $row['data_status_label'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-indigo-600">
                                            <a href="#" class="hover:text-indigo-800 hover:underline transition-colors cursor-pointer" data-sale-detail-id="{{ $row['id'] }}">{{ $row['invoice_number'] }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row['sale_date'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row['outlet_name'] }}</td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span
                                                class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $row['sales_type_label'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row['customer_name'] }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">Rp
                                            {{ number_format($row['gross_value'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-rose-600">Rp
                                            {{ number_format($row['effective_discount'], 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-indigo-600">Rp
                                            {{ number_format($row['net_sales'], 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-8 text-center text-slate-500">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- Modal Detail Penjualan --}}
            <div id="saleDetailModal" class="fixed inset-0 z-[9999] hidden">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" id="saleDetailOverlay"></div>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-300" id="saleDetailContent">
                        {{-- Loading state --}}
                        <div id="saleDetailLoading" class="flex flex-col items-center justify-center py-20">
                            <div class="h-10 w-10 animate-spin rounded-full border-4 border-slate-200 border-t-indigo-600"></div>
                            <span class="mt-4 text-sm text-slate-500">Memuat detail transaksi...</span>
                        </div>

                        {{-- Content (populated by JS) --}}
                        <div id="saleDetailBody" class="hidden">
                            {{-- Header --}}
                            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 rounded-t-2xl">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-800" id="sdm-invoice">-</h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-slate-400" id="sdm-date">-</span>
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600" id="sdm-salestype">-</span>
                                        <span class="rounded px-2 py-0.5 text-[10px] font-medium" id="sdm-status">-</span>
                                    </div>
                                </div>
                                <button type="button" id="saleDetailClose" class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-colors">
                                    <i class="fas fa-times text-sm"></i>
                                </button>
                            </div>

                            {{-- Info Cards --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 px-6 py-4">
                                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                    <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Outlet</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-outlet">-</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                    <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Kasir</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-cashier">-</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                    <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Pelanggan</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-customer">-</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                                    <div class="text-[10px] font-medium uppercase tracking-widest text-slate-400">Promo / Voucher</div>
                                    <div class="mt-1 text-sm font-medium text-slate-800" id="sdm-promo">-</div>
                                </div>
                            </div>

                            {{-- Items Table --}}
                            <div class="px-6 pb-3">
                                <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Detail Item</h4>
                                <div class="overflow-x-auto rounded-xl border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 text-left text-[11px] font-medium uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th class="px-4 py-2.5">Produk</th>
                                                <th class="px-4 py-2.5 text-right">Qty</th>
                                                <th class="px-4 py-2.5 text-right">Harga</th>
                                                <th class="px-4 py-2.5 text-right">Diskon</th>
                                                <th class="px-4 py-2.5 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100" id="sdm-items">
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Summary + Payments --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-6 pb-4">
                                {{-- Payments --}}
                                <div>
                                    <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Pembayaran</h4>
                                    <div class="rounded-xl border border-slate-200 divide-y divide-slate-100" id="sdm-payments">
                                    </div>
                                </div>

                                {{-- Totals --}}
                                <div>
                                    <h4 class="text-xs font-medium uppercase tracking-widest text-slate-400 mb-3">Ringkasan</h4>
                                    <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Nilai Jual (Gross)</span>
                                            <span class="font-medium text-slate-800" id="sdm-gross">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Diskon Item</span>
                                            <span class="font-medium text-rose-500" id="sdm-item-disc">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Diskon Header</span>
                                            <span class="font-medium text-rose-500" id="sdm-header-disc">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                                            <span class="text-slate-500">Subtotal</span>
                                            <span class="font-medium text-slate-800" id="sdm-subtotal">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Pajak</span>
                                            <span class="font-medium text-slate-800" id="sdm-tax">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Service Charge</span>
                                            <span class="font-medium text-slate-800" id="sdm-sc">-</span>
                                        </div>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-slate-500">Pembulatan</span>
                                            <span class="font-medium text-slate-800" id="sdm-rounding">-</span>
                                        </div>
                                        <div class="flex justify-between border-t border-slate-200 pt-2">
                                            <span class="text-sm font-semibold uppercase tracking-wider text-slate-800">Total</span>
                                            <span class="text-lg font-bold text-indigo-600" id="sdm-total">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div id="sdm-notes-section" class="hidden px-6 pb-6">
                                <div class="rounded-xl bg-amber-50 border border-amber-200 p-3">
                                    <div class="text-[10px] font-medium uppercase tracking-widest text-amber-600 mb-1">Catatan</div>
                                    <div class="text-sm text-amber-800" id="sdm-notes">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @push('scripts')
            <script>
            (function() {
                const modal = document.getElementById('saleDetailModal');
                const overlay = document.getElementById('saleDetailOverlay');
                const closeBtn = document.getElementById('saleDetailClose');
                const loadingEl = document.getElementById('saleDetailLoading');
                const bodyEl = document.getElementById('saleDetailBody');

                function fmt(n) {
                    return 'Rp ' + Number(n || 0).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }

                function openModal(saleId) {
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    loadingEl.classList.remove('hidden');
                    bodyEl.classList.add('hidden');

                    const url = '{{ url("/admin/reports/catalog/sale") }}/' + saleId;
                    fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
                        .then(data => {
                            // Header
                            document.getElementById('sdm-invoice').textContent = data.invoice_number;
                            document.getElementById('sdm-date').textContent = data.sale_date;
                            document.getElementById('sdm-salestype').textContent = data.sales_type_label;

                            const statusEl = document.getElementById('sdm-status');
                            statusEl.textContent = data.status === 'completed' ? 'Selesai' : data.status;
                            statusEl.className = 'rounded px-2 py-0.5 text-[10px] font-medium ' +
                                (data.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700');

                            // Info
                            document.getElementById('sdm-outlet').textContent = data.outlet_name;
                            document.getElementById('sdm-cashier').textContent = data.cashier_name;
                            document.getElementById('sdm-customer').textContent = data.customer_name;

                            let promoText = '-';
                            if (data.promotion_name) promoText = data.promotion_name;
                            else if (data.voucher_name) promoText = data.voucher_name;
                            else if (data.voucher_code) promoText = data.voucher_code;
                            document.getElementById('sdm-promo').textContent = promoText;

                            // Items
                            const itemsBody = document.getElementById('sdm-items');
                            itemsBody.innerHTML = '';
                            (data.items || []).forEach(function(item) {
                                const tr = document.createElement('tr');
                                tr.innerHTML =
                                    '<td class="px-4 py-2.5">' +
                                        '<div class="font-medium text-slate-800">' + (item.product_name || '-') + '</div>' +
                                        (item.product_sku ? '<div class="text-[10px] text-slate-400">' + item.product_sku + '</div>' : '') +
                                        (item.notes ? '<div class="text-[10px] text-amber-600 mt-0.5"><i class="fas fa-sticky-note mr-1"></i>' + item.notes + '</div>' : '') +
                                    '</td>' +
                                    '<td class="px-4 py-2.5 text-right text-slate-700">' + Number(item.quantity).toLocaleString('id-ID') + '</td>' +
                                    '<td class="px-4 py-2.5 text-right text-slate-700">' + fmt(item.original_price) + '</td>' +
                                    '<td class="px-4 py-2.5 text-right text-rose-500">' + (item.discount_amount > 0 ? fmt(item.discount_amount) : '-') + '</td>' +
                                    '<td class="px-4 py-2.5 text-right font-medium text-slate-800">' + fmt(item.subtotal) + '</td>';
                                itemsBody.appendChild(tr);
                            });

                            // Payments
                            const paymentsEl = document.getElementById('sdm-payments');
                            paymentsEl.innerHTML = '';
                            (data.payments || []).forEach(function(p) {
                                const div = document.createElement('div');
                                div.className = 'flex items-center justify-between px-4 py-3';
                                div.innerHTML =
                                    '<div>' +
                                        '<div class="text-sm font-medium text-slate-800">' + p.method + '</div>' +
                                        (p.reference_number ? '<div class="text-[10px] text-slate-400">' + p.reference_number + '</div>' : '') +
                                    '</div>' +
                                    '<span class="text-sm font-medium text-slate-800">' + fmt(p.amount) + '</span>';
                                paymentsEl.appendChild(div);
                            });

                            if (!data.payments || data.payments.length === 0) {
                                paymentsEl.innerHTML = '<div class="px-4 py-3 text-center text-sm text-slate-400 italic">Tidak ada data pembayaran</div>';
                            }

                            // Totals
                            document.getElementById('sdm-gross').textContent = fmt(data.gross_value);
                            document.getElementById('sdm-item-disc').textContent = fmt(data.item_level_discount);
                            document.getElementById('sdm-header-disc').textContent = fmt(data.header_discount);
                            document.getElementById('sdm-subtotal').textContent = fmt(data.subtotal);
                            document.getElementById('sdm-tax').textContent = fmt(data.tax_amount);
                            document.getElementById('sdm-sc').textContent = fmt(data.service_charge_amount);
                            document.getElementById('sdm-rounding').textContent = fmt(data.rounding_amount);
                            document.getElementById('sdm-total').textContent = fmt(data.total_amount);

                            // Notes
                            const notesSection = document.getElementById('sdm-notes-section');
                            if (data.notes && data.notes.trim() !== '') {
                                document.getElementById('sdm-notes').textContent = data.notes;
                                notesSection.classList.remove('hidden');
                            } else {
                                notesSection.classList.add('hidden');
                            }

                            loadingEl.classList.add('hidden');
                            bodyEl.classList.remove('hidden');
                        })
                        .catch(function(err) {
                            loadingEl.innerHTML = '<div class="text-center py-20"><i class="fas fa-exclamation-circle text-3xl text-rose-400 mb-3"></i><div class="text-sm text-slate-500">Gagal memuat data transaksi</div></div>';
                        });
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                }

                // Event: click invoice number
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('[data-sale-detail-id]');
                    if (link) {
                        e.preventDefault();
                        openModal(link.dataset.saleDetailId);
                    }
                });

                // Close modal
                if (overlay) overlay.addEventListener('click', closeModal);
                if (closeBtn) closeBtn.addEventListener('click', closeModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });
            })();
            </script>
            @endpush

        @elseif($viewType === 'balance-sheet-final')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-base font-normal text-slate-900">Ringkasan Neraca</h3>
                    @if($summary['totals']['is_balanced'] ?? false)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-normal text-emerald-700">Balance</span>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-normal text-amber-700">Belum Balance</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Aset</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['totals']['asset'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'asset']))) }}">
                            Audit Aset
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Kewajiban</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['totals']['liability'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'liability']))) }}">
                            Audit Kewajiban
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Ekuitas</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['totals']['equity'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_type' => 'equity']))) }}">
                            Audit Ekuitas
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Kewajiban + Ekuitas</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['totals']['liability_equity'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Selisih Balance</div>
                        <div
                            class="mt-1 text-xl font-normal {{ ($summary['totals']['is_balanced'] ?? false) ? 'text-emerald-700' : 'text-amber-700' }}">
                            Rp {{ number_format($summary['totals']['difference'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @foreach(['asset' => 'Aset', 'liability' => 'Kewajiban', 'equity' => 'Ekuitas'] as $type => $label)
                        <div class="rounded-xl border border-slate-200">
                            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-normal text-slate-700">
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
                                                    <div class="font-normal text-slate-800">{{ $row['code'] }} - {{ $row['name'] }}
                                                    </div>
                                                    <div class="text-xs text-slate-500">{{ $row['group'] }}</div>
                                                </td>
                                                <td
                                                    class="px-4 py-2 text-right {{ $row['movement_in_period'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                    Rp {{ number_format($row['movement_in_period'], 0, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-2 text-right font-normal text-slate-900">
                                                    Rp {{ number_format($row['balance'], 0, ',', '.') }}
                                                </td>
                                                <td class="px-4 py-2 text-right">
                                                    <a class="inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
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
                                            <td class="px-4 py-2 text-sm font-normal text-slate-700" colspan="3">Total
                                                {{ $label }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm font-normal text-slate-900">
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
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-normal text-slate-700">
                        Kontrol Rekonsiliasi
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="text-xs font-normal uppercase text-slate-500">Saldo Kas & Bank (Kontrol)</div>
                            <div class="mt-1 text-lg font-normal text-slate-900">
                                Rp {{ number_format($summary['controls']['cash_bank_as_of'] ?? 0, 0, ',', '.') }}
                            </div>
                            <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                                href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                                Audit Kas & Bank Detil
                            </a>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="text-xs font-normal uppercase text-slate-500">Selisih Aset vs Kontrol Kas/Bank</div>
                            <div
                                class="mt-1 text-lg font-normal {{ abs((float) ($summary['controls']['asset_vs_cash_bank_gap'] ?? 0)) < 0.01 ? 'text-emerald-700' : 'text-amber-700' }}">
                                Rp {{ number_format($summary['controls']['asset_vs_cash_bank_gap'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-normal text-slate-700">
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
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Pendapatan</div>
                        <a class="mt-2 inline-flex text-2xl font-normal text-slate-900 hover:text-indigo-700"
                            href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">
                            Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">HPP</div>
                        <a class="mt-2 inline-flex text-2xl font-normal text-rose-700 hover:text-indigo-700" href="{{ route('admin.stocks.mutations', array_filter([
                'start_date' => $dateFrom,
                'end_date' => $dateTo,
                'outlet_id' => $outletId,
                'reference_scope' => 'sales_cogs',
            ])) }}">
                            Rp {{ number_format($summary['total_cogs'] ?? 0, 0, ',', '.') }}
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Laba Kotor</div>
                        <div class="mt-2 text-2xl font-normal text-emerald-700">Rp
                            {{ number_format($summary['gross_profit'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Margin
                            {{ number_format($summary['gross_profit_margin'] ?? 0, 2) }}%
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Laba Bersih</div>
                        <div
                            class="mt-2 text-2xl font-normal {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-indigo-700' : 'text-rose-700' }}">
                            Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Margin
                            {{ number_format($summary['net_profit_margin'] ?? 0, 2) }}%
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-normal text-slate-700">
                            Ringkasan Laba Rugi
                        </div>
                        <div class="space-y-2 p-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-700">Pendapatan</span>
                                <a class="font-normal text-slate-900 hover:text-indigo-700"
                                    href="{{ route('admin.reports.sales.index', array_filter($auditBase)) }}">
                                    Rp {{ number_format($summary['total_revenue'] ?? 0, 0, ',', '.') }}
                                </a>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-700">HPP</span>
                                <a class="font-normal text-rose-700 hover:text-indigo-700" href="{{ route('admin.stocks.mutations', array_filter([
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
                                    <span class="font-normal text-slate-800">Laba Kotor</span>
                                    <span class="font-normal text-emerald-700">Rp
                                        {{ number_format($summary['gross_profit'] ?? 0, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-700">Biaya Operasional</span>
                                <a class="font-normal text-rose-700 hover:text-indigo-700" href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
                'type' => 'out',
                'coa_type' => 'expense',
                'exclude_coa_group' => 'HPP',
            ]))) }}">
                                    Rp {{ number_format($summary['total_expenses'] ?? 0, 0, ',', '.') }}
                                </a>
                            </div>
                            <div class="border-t border-slate-200 pt-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-normal text-slate-800">Laba Bersih</span>
                                    <span
                                        class="font-normal {{ ($summary['net_profit'] ?? 0) >= 0 ? 'text-indigo-700' : 'text-rose-700' }}">
                                        Rp {{ number_format($summary['net_profit'] ?? 0, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-normal text-slate-700">
                            Biaya Operasional per Grup COA
                        </div>
                        <div class="p-4">
                            @if(!empty($summary['expenses_by_group']) && count($summary['expenses_by_group']) > 0)
                                <div class="space-y-4">
                                    @foreach($summary['expenses_by_group'] as $group)
                                                    <div class="rounded-lg border border-slate-200 p-3">
                                                        <div class="mb-2 flex items-center justify-between">
                                                            <div class="text-sm font-normal text-slate-800">{{ $group['group_name'] }}</div>
                                                            <a class="text-sm font-normal text-slate-900 hover:text-indigo-700" href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
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
                                                                                    <a class="font-normal text-slate-700 hover:text-indigo-700" href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, [
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
                                <div
                                    class="rounded-lg border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">
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
                        <div class="text-xs font-normal uppercase text-slate-500">Total Kas</div>
                        <div class="mt-1 text-2xl font-normal text-slate-900">Rp
                            {{ number_format($summary['total_cash'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                            Audit Akun Kas
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Bank</div>
                        <div class="mt-1 text-2xl font-normal text-slate-900">Rp
                            {{ number_format($summary['total_bank'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.reports.catalog.show', array_merge(['slug' => 'cash-bank-detail'], $auditBase)) }}">
                            Audit Per Akun
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Kas + Bank</div>
                        <div class="mt-1 text-2xl font-normal text-slate-900">Rp
                            {{ number_format($summary['total_balance'] ?? 0, 0, ',', '.') }}
                        </div>
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
                                        <div class="font-normal text-slate-800">{{ $row->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->code }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-700">
                                            {{ strtoupper($row->type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-normal text-slate-900">Rp
                                        {{ number_format($row->ending_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
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
                        <div class="text-xs font-normal uppercase text-slate-500">Saldo Awal Periode</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['beginning_balance'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Kas Masuk</div>
                        <div class="mt-1 text-xl font-normal text-emerald-700">Rp
                            {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Kas Keluar</div>
                        <div class="mt-1 text-xl font-normal text-rose-700">Rp
                            {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Saldo Akhir</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">Rp
                            {{ number_format($summary['ending_balance'] ?? 0, 0, ',', '.') }}
                        </div>
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
                                        <div class="font-normal text-slate-800">{{ $row->name }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->code }} - {{ strtoupper($row->type) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">Rp
                                        {{ number_format($row->beginning_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-emerald-700">Rp
                                        {{ number_format($row->total_in, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-rose-700">Rp
                                        {{ number_format($row->total_out, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-normal text-slate-900">Rp
                                        {{ number_format($row->ending_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['cash_account_id' => $row->id]))) }}">
                                            Lihat Transaksi
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada data mutasi kas/bank.
                                    </td>
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
                        <div class="text-xs font-normal uppercase text-slate-500">Total Kas Masuk</div>
                        <div class="mt-1 text-xl font-normal text-emerald-700">Rp
                            {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Kas Keluar</div>
                        <div class="mt-1 text-xl font-normal text-rose-700">Rp
                            {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Net Mutasi</div>
                        <div
                            class="mt-1 text-xl font-normal {{ ($summary['net'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            Rp {{ number_format($summary['net'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Jumlah Baris</div>
                        <div class="mt-1 text-xl font-normal text-slate-900">{{ number_format($summary['row_count'] ?? 0) }}
                        </div>
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
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ \Carbon\Carbon::parse($row->transaction_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $row->transaction_number }}</td>
                                    <td class="px-4 py-3">
                                        @if($row->coa_code)
                                            <div class="font-normal text-slate-800">{{ $row->coa_code }} - {{ $row->coa_name }}</div>
                                        @else
                                            <span class="text-slate-500">Tanpa COA</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->cash_account_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">
                                        {{ $row->type === 'in' ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-rose-700">
                                        {{ $row->type === 'out' ? 'Rp ' . number_format($row->amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $row->description }}
                                        @if($row->reference_type)
                                            <div class="text-xs text-slate-500">Ref: {{ $row->reference_type }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi ledger pada
                                        periode ini.</td>
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
                        <div class="text-xs font-normal uppercase text-slate-500">Total Arus Masuk</div>
                        <div class="mt-1 text-2xl font-normal text-emerald-700">Rp
                            {{ number_format($summary['total_in'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['type' => 'in']))) }}">
                            Audit Arus Masuk
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Total Arus Keluar</div>
                        <div class="mt-1 text-2xl font-normal text-rose-700">Rp
                            {{ number_format($summary['total_out'] ?? 0, 0, ',', '.') }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['type' => 'out']))) }}">
                            Audit Arus Keluar
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase text-slate-500">Net Arus Kas</div>
                        <div
                            class="mt-1 text-2xl font-normal {{ ($summary['net'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
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
                                    <td class="px-4 py-3 font-normal text-slate-800">{{ $row->flow_group }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">Rp
                                        {{ number_format($row->total_in, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-rose-700">Rp
                                        {{ number_format($row->total_out, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-normal {{ $row->net_cash_flow >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        Rp {{ number_format($row->net_cash_flow, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_group' => $row->flow_group]))) }}">
                                            Lihat Transaksi
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data arus kas pada
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($viewType === 'stock-movement')
            @php
                $qty = fn($value) => number_format((float) $value, 2, ',', '.');
                $nominal = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
                $stockAuditBase = array_filter([
                    'start_date' => $dateFrom,
                    'end_date' => $dateTo,
                    'outlet_id' => $outletId,
                    'product_id' => $selectedProductId ?? null,
                ]);
            @endphp
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Stok Awal (Nominal)</div>
                        <div class="mt-2 text-xl font-normal text-slate-900">
                            {{ $nominal($summary['opening_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['opening_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Masuk</div>
                        <div class="mt-2 text-xl font-normal text-emerald-700">
                            {{ $nominal($summary['total_in_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['total_in_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Keluar</div>
                        <div class="mt-2 text-xl font-normal text-rose-700">
                            {{ $nominal($summary['total_out_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['total_out_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Penjualan Keluar (HPP)</div>
                        <div class="mt-2 text-xl font-normal text-rose-700">
                            {{ $nominal($summary['hpp_penjualan_kotor'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['sale_out_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Reversal Void</div>
                        <div class="mt-2 text-xl font-normal text-amber-700">
                            {{ $nominal($summary['hpp_reversal_void'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Ref: sale_cancellation</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">HPP Penjualan Bersih</div>
                        <div class="mt-2 text-xl font-normal text-rose-700">
                            {{ $nominal($summary['hpp_penjualan_bersih'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Kotor - Reversal</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Stok Akhir (Nominal)</div>
                        <div class="mt-2 text-xl font-normal text-indigo-700">
                            {{ $nominal($summary['closing_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['closing_qty'] ?? 0) }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a class="ui-btn ui-btn-ghost inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 hover:border-indigo-200 hover:text-indigo-700"
                        href="{{ route('admin.stocks.mutations', array_filter(array_merge($stockAuditBase, ['reference_scope' => 'sales_cogs']))) }}">
                        <i class="fas fa-percent text-xs"></i>
                        Audit HPP Penjualan (Kotor+Reversal)
                    </a>
                    <a class="ui-btn ui-btn-ghost inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 hover:border-indigo-200 hover:text-indigo-700"
                        href="{{ route('admin.stocks.mutations', $stockAuditBase) }}">
                        <i class="fas fa-list-ul text-xs"></i>
                        Audit Mutasi Stok
                    </a>
                    <a class="ui-btn ui-btn-ghost inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 hover:border-indigo-200 hover:text-indigo-700"
                        href="{{ route('admin.reports.catalog.show', array_filter(array_merge(['slug' => 'sales-vs-hpp'], $auditBase))) }}">
                        <i class="fas fa-balance-scale text-xs"></i>
                        Audit Sales vs HPP
                    </a>
                    @if(!empty($summary['selected_product_name']))
                        <span class="rounded-lg bg-indigo-50 px-3 py-1.5 text-indigo-700">
                            Produk: {{ $summary['selected_product_name'] }}
                        </span>
                    @endif
                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-slate-600">
                        Baris: {{ number_format($summary['row_count'] ?? 0) }}
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-[3200px] text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3">Outlet</th>
                                <th class="px-4 py-3 text-right">Stok Awal Qty</th>
                                <th class="px-4 py-3 text-right">Stok Awal Nominal</th>
                                <th class="px-4 py-3 text-right">Pembelian In Qty</th>
                                <th class="px-4 py-3 text-right">Pembelian In Nominal</th>
                                <th class="px-4 py-3 text-right">Retur Jual In Qty</th>
                                <th class="px-4 py-3 text-right">Retur Jual In Nominal</th>
                                <th class="px-4 py-3 text-right">Mutasi In Qty</th>
                                <th class="px-4 py-3 text-right">Mutasi In Nominal</th>
                                <th class="px-4 py-3 text-right">Adjustment + Qty</th>
                                <th class="px-4 py-3 text-right">Adjustment + Nominal</th>
                                <th class="px-4 py-3 text-right">Penjualan Out Qty</th>
                                <th class="px-4 py-3 text-right">Penjualan Out Nominal</th>
                                <th class="px-4 py-3 text-right">Mutasi Out Qty</th>
                                <th class="px-4 py-3 text-right">Mutasi Out Nominal</th>
                                <th class="px-4 py-3 text-right">Adjustment - Qty</th>
                                <th class="px-4 py-3 text-right">Adjustment - Nominal</th>
                                <th class="px-4 py-3 text-right">Lainnya In Qty</th>
                                <th class="px-4 py-3 text-right">Lainnya In Nominal</th>
                                <th class="px-4 py-3 text-right">Lainnya Out Qty</th>
                                <th class="px-4 py-3 text-right">Lainnya Out Nominal</th>
                                <th class="px-4 py-3 text-right">Total In Qty</th>
                                <th class="px-4 py-3 text-right">Total In Nominal</th>
                                <th class="px-4 py-3 text-right">Total Out Qty</th>
                                <th class="px-4 py-3 text-right">Total Out Nominal</th>
                                <th class="px-4 py-3 text-right">Stok Akhir Qty</th>
                                <th class="px-4 py-3 text-right">Stok Akhir Nominal</th>
                                <th class="px-4 py-3 text-right">Avg Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-normal text-slate-800">{{ $row->product_name }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $row->product_sku ?: '-' }} | {{ strtoupper($row->product_unit ?? 'pcs') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $qty($row->opening_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-900">{{ $nominal($row->opening_value) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $qty($row->purchase_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $nominal($row->purchase_in_value) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $qty($row->sale_return_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $nominal($row->sale_return_in_value) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $qty($row->transfer_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $nominal($row->transfer_in_value) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $qty($row->adjustment_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $nominal($row->adjustment_in_value) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $qty($row->sale_out_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $nominal($row->sale_out_value) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $qty($row->transfer_out_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $nominal($row->transfer_out_value) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $qty($row->adjustment_out_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $nominal($row->adjustment_out_value) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $qty($row->other_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700">{{ $nominal($row->other_in_value) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $qty($row->other_out_qty) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700">{{ $nominal($row->other_out_value) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-emerald-700">{{ $qty($row->total_in_qty) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-emerald-700">{{ $nominal($row->total_in_value) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-rose-700">{{ $qty($row->total_out_qty) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-rose-700">{{ $nominal($row->total_out_value) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-indigo-700">{{ $qty($row->closing_qty) }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-indigo-700">{{ $nominal($row->closing_value) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->current_avg_cost, 4, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="30" class="px-4 py-8 text-center text-slate-500">
                                        Belum ada data pergerakan stok untuk filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(($summary['row_count'] ?? 0) > 0)
                            <tfoot class="bg-slate-50 text-xs font-normal uppercase tracking-wide text-slate-600">
                                <tr>
                                    <td colspan="3" class="px-4 py-3">Total</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['opening_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['opening_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['purchase_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['purchase_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['sale_return_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['sale_return_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['transfer_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['transfer_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['adjustment_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['adjustment_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['sale_out_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['sale_out_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['transfer_out_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['transfer_out_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['adjustment_out_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['adjustment_out_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['other_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['other_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['other_out_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['other_out_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['total_in_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['total_in_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['total_out_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['total_out_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $qty($summary['closing_qty'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $nominal($summary['closing_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">-</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if(!empty($meta['notes']))
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Catatan Valuasi</div>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            @foreach($meta['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @elseif($viewType === 'stock-adjustment')
            @php
                $qty = fn($value) => number_format((float) $value, 2, ',', '.');
                $nominal = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
                $adjustmentAuditBase = array_filter([
                    'tab' => 'all',
                    'start_date' => $dateFrom,
                    'end_date' => $dateTo,
                    'outlet_id' => $outletId,
                    'product_id' => $selectedProductId ?? null,
                    'mutation_type' => 'adjustment',
                    'reference_type' => 'stock_opname',
                ]);
                $netQty = (float) ($summary['net_qty'] ?? 0);
                $netValue = (float) ($summary['net_value'] ?? 0);
            @endphp
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Adjustment</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">
                            {{ number_format($summary['row_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Baris mutasi adjustment</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Produk Terdampak</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">
                            {{ number_format($summary['product_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Produk unik pada periode ini</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">User Input</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">
                            {{ number_format($summary['user_count'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">User unik yang melakukan input</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Adjustment Plus</div>
                        <div class="mt-2 text-xl font-normal text-emerald-700">
                            {{ $nominal($summary['plus_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['plus_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Adjustment Minus</div>
                        <div class="mt-2 text-xl font-normal text-rose-700">
                            {{ $nominal($summary['minus_value'] ?? 0) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $qty($summary['minus_qty'] ?? 0) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Net Adjustment</div>
                        <div class="mt-2 text-xl font-normal {{ $netValue >= 0 ? 'text-indigo-700' : 'text-rose-700' }}">
                            {{ $nominal($netValue) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">Qty: {{ $netQty >= 0 ? '+' : '' }}{{ $qty($netQty) }}</div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a class="ui-btn ui-btn-ghost inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-slate-700 hover:border-indigo-200 hover:text-indigo-700"
                        href="{{ route('admin.stocks.mutations', $adjustmentAuditBase) }}">
                        <i class="fas fa-list-ul text-xs"></i>
                        Audit Mutasi Adjustment
                    </a>
                    @if(!empty($summary['selected_product_name']))
                        <span class="rounded-lg bg-indigo-50 px-3 py-1.5 text-indigo-700">
                            Produk: {{ $summary['selected_product_name'] }}
                        </span>
                    @endif
                    @if(!empty($summary['selected_user_name']))
                        <span class="rounded-lg bg-emerald-50 px-3 py-1.5 text-emerald-700">
                            User: {{ $summary['selected_user_name'] }}
                        </span>
                    @endif
                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-slate-600">
                        Periode: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                    </span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-[1700px] text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">No</th>
                                <th class="px-4 py-3">Tanggal & Jam</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3">Outlet</th>
                                <th class="px-4 py-3 text-right">Stok Sebelum</th>
                                <th class="px-4 py-3 text-right">Stok Sesudah</th>
                                <th class="px-4 py-3 text-right">Selisih Qty</th>
                                <th class="px-4 py-3 text-center">Arah</th>
                                <th class="px-4 py-3 text-right">Unit Cost</th>
                                <th class="px-4 py-3 text-right">Nominal</th>
                                <th class="px-4 py-3">Diinput Oleh</th>
                                <th class="px-4 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <div>{{ $row->mutation_date_display }}</div>
                                        <div class="text-xs text-slate-500">{{ $row->mutation_time_display }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-normal text-slate-800">{{ $row->product_name }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $row->product_sku ?: '-' }} | {{ strtoupper($row->product_unit ?? 'pcs') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $qty($row->stock_before) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-900">{{ $qty($row->stock_after) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->quantity > 0 ? 'text-emerald-700' : ($row->quantity < 0 ? 'text-rose-700' : 'text-slate-700') }}">
                                        {{ $row->quantity > 0 ? '+' : '' }}{{ $qty($row->quantity) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-medium uppercase tracking-wide {{ $row->direction_label === 'PLUS' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : ($row->direction_label === 'MINUS' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-slate-100 text-slate-700 ring-1 ring-slate-200') }}">
                                            {{ $row->direction_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ $nominal($row->unit_cost) }}</td>
                                    <td class="px-4 py-3 text-right {{ $row->quantity > 0 ? 'text-emerald-700' : ($row->quantity < 0 ? 'text-rose-700' : 'text-slate-700') }}">
                                        {{ $nominal($row->total_cost) }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->creator_name }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->notes }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-8 text-center text-slate-500">
                                        Belum ada data adjustment stok untuk filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!empty($meta['notes']))
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Catatan Laporan</div>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            @foreach($meta['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @elseif($viewType === 'inventory-reconciliation')
            @php
                $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
                $reconGap = (float) ($summary['gap_value'] ?? 0);
                $reconIsBalanced = (bool) ($summary['is_balanced'] ?? false);
            @endphp
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Nilai Mutasi Persediaan</div>
                        <div class="mt-2 text-2xl font-normal text-indigo-700">
                            {{ $money($summary['inventory_mutation_value'] ?? 0) }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.reports.catalog.show', array_filter(array_merge(['slug' => 'stock-movement'], $auditBase, ['tab' => request('tab')]))) }}">
                            Audit Laporan Pergerakan Stok
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Nilai Persediaan Neraca</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">
                            {{ $money($summary['inventory_neraca_value'] ?? 0) }}
                        </div>
                        <a class="mt-2 inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                            href="{{ route('admin.reports.catalog.show', array_filter(array_merge(['slug' => 'balance-sheet'], $auditBase, ['tab' => 'ikhtisar']))) }}">
                            Audit Neraca
                        </a>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Selisih Rekonsiliasi</div>
                        <div class="mt-2 text-2xl font-normal {{ abs($reconGap) < 0.01 ? 'text-emerald-700' : 'text-rose-700' }}">
                            {{ $money($reconGap) }}
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            Target: 0
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Status Rekonsiliasi</div>
                        <div class="mt-2">
                            @if($reconIsBalanced)
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-normal text-emerald-700">
                                    BALANCED
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-normal text-rose-700">
                                    SELISIH
                                </span>
                            @endif
                        </div>
                        <div class="mt-2 text-xs text-slate-500">
                            Akun persediaan terdeteksi: {{ number_format($summary['inventory_account_count'] ?? 0) }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Outlet</th>
                                <th class="px-4 py-3 text-right">Mutasi Persediaan</th>
                                <th class="px-4 py-3 text-right">Neraca Persediaan</th>
                                <th class="px-4 py-3 text-right">Selisih</th>
                                <th class="px-4 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-700">
                                        {{ $money($row->mutation_inventory_value) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-900">
                                        {{ $money($row->neraca_inventory_value) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-normal {{ abs((float) $row->gap_value) < 0.01 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ $money($row->gap_value) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs {{ !empty($row->is_balanced) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                            {{ !empty($row->is_balanced) ? 'Balance' : 'Selisih' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                        Belum ada data rekonsiliasi untuk filter yang dipilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(($summary['outlet_row_count'] ?? 0) > 0)
                            <tfoot class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                                <tr>
                                    <td class="px-4 py-3">Total</td>
                                    <td class="px-4 py-3 text-right">{{ $money($summary['inventory_mutation_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $money($summary['inventory_neraca_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $money($summary['gap_value'] ?? 0) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        {{ !empty($summary['is_balanced']) ? 'Balance' : 'Selisih' }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Akun Persediaan (COA)</th>
                                <th class="px-4 py-3">Grup</th>
                                <th class="px-4 py-3 text-right">Saldo s/d {{ $dateTo }}</th>
                                <th class="px-4 py-3 text-right">Audit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse(($summary['inventory_accounts_rows'] ?? []) as $accountRow)
                                <tr>
                                    <td class="px-4 py-3 text-slate-800">
                                        {{ $accountRow['coa_code'] }} - {{ $accountRow['coa_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $accountRow['coa_group'] }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-slate-900">
                                        {{ $money($accountRow['balance']) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a class="inline-flex text-xs font-normal text-indigo-700 hover:text-indigo-900"
                                            href="{{ route('admin.cash-transactions.index', array_filter(array_merge($auditBase, ['coa_account_id' => $accountRow['coa_account_id']]))) }}">
                                            Lihat Transaksi
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                        Belum ada akun COA persediaan yang terdeteksi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(!empty($meta['notes']))
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Catatan Rekonsiliasi</div>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            @foreach($meta['notes'] as $note)
                                <li>{{ $note }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @elseif(in_array($viewType, ['purchase-summary', 'purchase-by-supplier', 'purchase-by-product', 'purchase-by-category', 'purchase-unpaid'], true))
            @php
                $money = fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
            @endphp
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Jumlah Data</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">
                            @if($viewType === 'purchase-by-supplier')
                                {{ number_format($summary['supplier_count'] ?? 0) }}
                            @elseif($viewType === 'purchase-by-product')
                                {{ number_format($summary['product_count'] ?? 0) }}
                            @elseif($viewType === 'purchase-by-category')
                                {{ number_format($summary['category_count'] ?? 0) }}
                            @else
                                {{ number_format($summary['total_purchase_count'] ?? 0) }}
                            @endif
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Pembelian</div>
                        <div class="mt-2 text-2xl font-normal text-indigo-700">
                            {{ $money($summary['total_amount'] ?? 0) }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Dibayar</div>
                        <div class="mt-2 text-2xl font-normal text-emerald-700">
                            {{ $money($summary['total_paid'] ?? 0) }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Sisa Hutang</div>
                        <div class="mt-2 text-2xl font-normal text-rose-700">
                            {{ $money($summary['total_outstanding'] ?? 0) }}
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    @if($viewType === 'purchase-summary')
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-right">Jumlah PO</th>
                                    <th class="px-4 py-3 text-right">Total Pembelian</th>
                                    <th class="px-4 py-3 text-right">Total Dibayar</th>
                                    <th class="px-4 py-3 text-right">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ \Carbon\Carbon::parse($row->purchase_date)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_purchase_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->total_amount) }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-700">{{ $money($row->total_paid) }}</td>
                                        <td class="px-4 py-3 text-right text-rose-700">{{ $money($row->outstanding_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data pembelian untuk filter yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif($viewType === 'purchase-by-supplier')
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Supplier</th>
                                    <th class="px-4 py-3 text-right">Jumlah PO</th>
                                    <th class="px-4 py-3 text-right">Total Pembelian</th>
                                    <th class="px-4 py-3 text-right">Total Dibayar</th>
                                    <th class="px-4 py-3 text-right">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">
                                            {{ $row->supplier_name }}
                                            @if(!empty($row->supplier_code))
                                                <span class="ml-1 text-xs text-slate-500">({{ $row->supplier_code }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_purchase_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->total_amount) }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-700">{{ $money($row->total_paid) }}</td>
                                        <td class="px-4 py-3 text-right text-rose-700">{{ $money($row->outstanding_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data pembelian untuk filter yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif($viewType === 'purchase-by-product')
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Produk</th>
                                    <th class="px-4 py-3 text-right">Jumlah PO</th>
                                    <th class="px-4 py-3 text-right">Total Qty</th>
                                    <th class="px-4 py-3 text-right">Rata-rata Harga</th>
                                    <th class="px-4 py-3 text-right">Total Pembelian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">
                                            {{ $row->product_name }}
                                            @if(!empty($row->product_sku))
                                                <span class="ml-1 text-xs text-slate-500">({{ $row->product_sku }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_purchase_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_qty, 2, ',', '.') }} {{ $row->product_unit ?: 'pcs' }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->avg_unit_price) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data pembelian untuk filter yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @elseif($viewType === 'purchase-by-category')
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3 text-right">Jumlah PO</th>
                                    <th class="px-4 py-3 text-right">Jumlah Produk</th>
                                    <th class="px-4 py-3 text-right">Total Qty</th>
                                    <th class="px-4 py-3 text-right">Total Pembelian</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $row->category_name }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_purchase_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_product_count) }}</td>
                                        <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_qty, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->total_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data pembelian untuk filter yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @else
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">No PO</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3">Outlet</th>
                                    <th class="px-4 py-3">Supplier</th>
                                    <th class="px-4 py-3 text-right">Total Pembelian</th>
                                    <th class="px-4 py-3 text-right">Total Dibayar</th>
                                    <th class="px-4 py-3 text-right">Sisa Hutang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $row->purchase_number }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ \Carbon\Carbon::parse($row->purchase_date)->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name }}</td>
                                        <td class="px-4 py-3 text-slate-800">{{ $row->supplier_name }}</td>
                                        <td class="px-4 py-3 text-right text-slate-900">{{ $money($row->total_amount) }}</td>
                                        <td class="px-4 py-3 text-right text-emerald-700">{{ $money($row->total_paid) }}</td>
                                        <td class="px-4 py-3 text-right text-rose-700">{{ $money($row->outstanding_amount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada data pembelian belum lunas untuk filter yang dipilih.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @elseif($viewType === 'sales-vs-hpp')
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Jumlah Item</div>
                        <div class="mt-2 text-2xl font-normal text-slate-900">{{ number_format($summary['total_items'] ?? 0) }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total Penjualan</div>
                        <div class="mt-2 text-2xl font-normal text-emerald-700">Rp
                            {{ number_format($summary['total_sales'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Total HPP</div>
                        <div class="mt-2 text-2xl font-normal text-rose-700">Rp
                            {{ number_format($summary['total_hpp'] ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-normal uppercase tracking-wide text-slate-500">Laba Kotor</div>
                        <div
                            class="mt-2 text-2xl font-normal {{ ($summary['total_gross_profit'] ?? 0) >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                            Rp {{ number_format($summary['total_gross_profit'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="mt-1 text-xs font-normal text-slate-500">Margin:
                            {{ number_format($summary['gross_margin_percent'] ?? 0, 2) }}%
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">No_Transaksi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Outlet</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Hpp</th>
                                <th class="px-4 py-3 text-right">Laba Kotor</th>
                                <th class="px-4 py-3 text-right">Margin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs">
                                        <a href="{{ route('admin.stocks.mutations', array_filter([
                                            'tab' => 'all',
                                            'start_date' => $dateFrom,
                                            'end_date' => $dateTo,
                                            'outlet_id' => $row->outlet_id ?? null,
                                            'reference_scope' => 'sales_cogs',
                                            'reference_id' => $row->sale_id ?? null,
                                        ])) }}"
                                            class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $row->transaction_number }}
                                        </a>
                                        <div class="mt-1 text-[10px] text-slate-400">Ref ID: {{ $row->sale_id ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ \Carbon\Carbon::parse($row->sale_date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $row->outlet_name }}</td>
                                    <td class="px-4 py-3 font-normal text-slate-800">{{ $row->product_name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->qty, 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-normal text-slate-900">Rp
                                        {{ number_format($row->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-normal text-rose-700">Rp
                                        {{ number_format($row->hpp_amount, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-normal {{ $row->gross_profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        Rp {{ number_format($row->gross_profit, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right font-normal {{ $row->margin_percent >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        {{ number_format($row->margin_percent, 2) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada data penjualan untuk
                                        filter yang dipilih.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <div class="text-sm font-normal text-amber-900">Halaman laporan sudah dibuat.</div>
                <div class="mt-1 text-sm text-amber-800">
                    Query data untuk laporan ini belum diimplementasikan. Halaman ini siap dipakai untuk tahap integrasi data
                    berikutnya.
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productInput = document.querySelector('[data-report-product-input]');
            const productIdInput = document.querySelector('[data-report-product-id]');
            const optionNodes = document.querySelectorAll('#report-product-options option');

            if (productInput && productIdInput && optionNodes.length > 0) {
                const productOptions = Array.from(optionNodes).map(function (option) {
                    return {
                        id: option.dataset.id || '',
                        label: option.value || '',
                    };
                });

                function syncSelectedProduct() {
                    const matched = productOptions.find(function (option) {
                        return option.label === productInput.value;
                    });

                    productIdInput.value = matched ? matched.id : '';

                    if (productInput.value && !matched) {
                        productInput.setCustomValidity('Pilih produk dari daftar autocomplete yang tersedia.');
                    } else {
                        productInput.setCustomValidity('');
                    }
                }

                productInput.addEventListener('input', syncSelectedProduct);
                productInput.addEventListener('change', syncSelectedProduct);
                productInput.form?.addEventListener('submit', function (event) {
                    syncSelectedProduct();
                    if (productInput.value && !productIdInput.value) {
                        event.preventDefault();
                        productInput.reportValidity();
                        productInput.focus();
                    }
                });
            }

            const tableFilterInputs = document.querySelectorAll('[data-report-table-filter-input]');
            const clearTableFiltersButton = document.querySelector('[data-report-table-clear-filters]');

            if (tableFilterInputs.length > 0) {
                const tableFilterParamNames = [
                    'filter_tipe_diskon',
                    'filter_metode_penjualan',
                    'filter_jumlah_transaksi',
                    'filter_gross',
                    'filter_diskon',
                    'filter_net_sales',
                    'filter_transaksi',
                    'filter_tanggal',
                    'filter_outlet',
                    'filter_pelanggan',
                ];
                const params = new URLSearchParams(window.location.search);

                tableFilterInputs.forEach(function (input) {
                    if (params.has(input.dataset.name)) {
                        input.value = params.get(input.dataset.name);
                    }
                });

                let tableFilterTimer = null;

                function updateTableFilter(name, value) {
                    const url = new URL(window.location.href);

                    if (value.trim()) {
                        url.searchParams.set(name, value.trim());
                    } else {
                        url.searchParams.delete(name);
                    }

                    window.location.href = url.toString();
                }

                tableFilterInputs.forEach(function (input) {
                    input.addEventListener('input', function (event) {
                        clearTimeout(tableFilterTimer);
                        tableFilterTimer = setTimeout(function () {
                            updateTableFilter(input.dataset.name, event.target.value);
                        }, 500);
                    });
                });

                if (clearTableFiltersButton) {
                    clearTableFiltersButton.addEventListener('click', function () {
                        const url = new URL(window.location.href);

                        tableFilterParamNames.forEach(function (paramName) {
                            url.searchParams.delete(paramName);
                        });

                        window.location.href = url.toString();
                    });
                }
            }
        });
    </script>
@endpush
