@extends('layouts.admin')

@section('title', 'Laporan Penjualan per Produk')
@section('page-title', 'Laporan Penjualan per Produk')
@section('page-subtitle', 'Analisis penjualan berdasarkan produk')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Penjualan per Produk</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Analisis performa penjualan setiap item menu</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.reports.index', ['tab' => request('tab', 'penjualan')]) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Katalog</span>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-6 no-print">
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 rounded-t-2xl">
                <div class="flex items-center gap-2">
                    <i class="fas fa-filter text-indigo-500 text-xs"></i>
                    <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Laporan
                    </h3>
                </div>
            </div>
            <div class="p-5">
                <form method="GET" action="{{ route('admin.reports.sales.products') }}" class="space-y-4">
                    <input type="hidden" name="tab" value="{{ request('tab', 'penjualan') }}">
                    @php
                        $selectedOutletIds = collect($filters['outlet_ids'] ?? [])->map(fn($id) => (int) $id)->all();
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Dari
                                Tanggal</label>
                            <input type="date" name="date_from" value="{{ $dateFrom }}" required
                                class="w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Sampai
                                Tanggal</label>
                            <input type="date" name="date_to" value="{{ $dateTo }}" required
                                class="w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet</label>
                            <div class="relative" id="salesProductOutletFilterWrap">
                                <button type="button" id="salesProductOutletDropdownBtn"
                                    class="w-full px-3 py-1.5 text-left text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all flex items-center justify-between">
                                    <span id="salesProductOutletDropdownLabel">Semua Outlet</span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                                </button>
                                <div id="salesProductOutletDropdownMenu"
                                    class="hidden absolute top-full left-0 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-lg p-2 z-50">
                                    <label class="flex items-center gap-2 text-sm text-slate-700 pb-1 mb-1 border-b border-slate-100">
                                        <input type="checkbox" id="salesProductOutletAllCheckbox"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            {{ count($selectedOutletIds) === 0 ? 'checked' : '' }}>
                                        <span>Semua Outlet</span>
                                    </label>
                                    <div style="max-height: 9rem; overflow-y: auto;" class="space-y-1 pr-1">
                                        @foreach($outlets as $outlet)
                                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                                <input type="checkbox"
                                                    name="outlet_ids[]"
                                                    value="{{ $outlet->id }}"
                                                    {{ in_array((int) $outlet->id, $selectedOutletIds, true) ? 'checked' : '' }}
                                                    class="sales-product-outlet-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="truncate">{{ $outlet->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="text-[9px] text-slate-400 ml-1">Kosong = semua outlet</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-normal text-slate-500 uppercase tracking-wider ml-1">Kasir</label>
                            <select name="user_id"
                                class="w-full px-3 py-1.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">Semua Kasir</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                <i class="fas fa-search text-[10px]"></i>
                                <span>Terapkan Filter</span>
                            </button>
                            <a href="{{ route('admin.reports.sales.products', ['tab' => request('tab', 'penjualan')]) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-600 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                <i class="fas fa-undo text-[10px]"></i>
                                <span>Reset</span>
                            </a>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.reports.sales.products.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-xs font-normal text-emerald-700 border border-emerald-100 shadow-sm transition-all hover:bg-emerald-100 active:scale-95">
                                <i class="fas fa-file-excel text-[10px]"></i>
                                <span>Excel</span>
                            </a>
                            <a href="{{ route('admin.reports.sales.products.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                                class="inline-flex items-center gap-2 rounded-lg bg-rose-50 px-4 py-2 text-xs font-normal text-rose-700 border border-rose-100 shadow-sm transition-all hover:bg-rose-100 active:scale-95">
                                <i class="fas fa-file-pdf text-[10px]"></i>
                                <span>PDF</span>
                            </a>
                            <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-slate-800 active:scale-95">
                                <i class="fas fa-print text-[10px]"></i>
                                <span>Cetak</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-normal uppercase tracking-[0.2em] text-indigo-500">Total Item Terjual</span>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <i class="fas fa-boxes text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-2xl font-normal text-slate-800 tracking-tight">
                        {{ number_format($grandTotal['total_qty'], 0, ',', '.') }}</h3>
                    <p class="text-xs font-normal text-slate-400 mt-0.5">Produk Berhasil Terjual</p>
                </div>
            </div>

            <div
                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md border-l-4 border-l-emerald-500">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-normal uppercase tracking-[0.2em] text-emerald-500">Total Omzet
                        (Gross)</span>
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <i class="fas fa-money-bill-wave text-sm"></i>
                    </div>
                </div>
                <div class="flex flex-col">
                    <h3 class="text-2xl font-normal text-slate-800 tracking-tight">Rp
                        {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}</h3>
                    <p class="text-xs font-normal text-slate-400 mt-0.5">Nilai Sebelum Diskon & Pajak Transaksi</p>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 sticky top-0 backdrop-blur-sm z-10">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-500 w-16 resizable group" style="min-width: 60px; position:relative;">
                                No
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 150px; position:relative;">
                                Produk
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-500 border-r border-slate-200 resizable group" style="min-width: 100px; position:relative;">
                                SKU
                                <div class="resize-handle"></div>
                            </th>
                            @foreach($outletsForColumns as $outletCol)
                                @php
                                    $clean = trim(str_ireplace('moresto', '', $outletCol->name));
                                    if (strtolower($clean) === 'ciplaz') {
                                        $initials = 'Cplz';
                                    } elseif (strlen($clean) > 8 && str_contains($clean, ' ')) {
                                        $initials = collect(explode(' ', $clean))->map(fn($w) => strtoupper(substr($w, 0, 1)))->join('');
                                    } else {
                                        $initials = $clean;
                                    }
                                @endphp
                                <th class="px-4 py-3 text-right text-[10px] font-bold uppercase tracking-widest text-slate-500 bg-slate-100/50 border-r border-slate-200 resizable group" title="{{ $outletCol->name }}" style="min-width: 70px; position:relative;">
                                    {{ $initials }}
                                    <div class="resize-handle"></div>
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest text-slate-700 bg-indigo-50/50 resizable group" style="min-width: 100px; position:relative;">
                                Total Qty
                                <div class="resize-handle bg-indigo-200"></div>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest text-slate-700 bg-emerald-50/50 resizable group" style="min-width: 120px; position:relative;">
                                Total Omzet
                                <div class="resize-handle bg-emerald-200"></div>
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-500 resizable group" style="min-width: 100px; position:relative;">
                                Avg Price
                                <div class="resize-handle"></div>
                            </th>
                        </tr>
                        {{-- Compact Filter Row --}}
                        <tr class="bg-white border-b border-slate-100 no-print">
                            <td class="px-2 py-2">
                                <button type="button" id="clearFilters" title="Reset filter tabel (klik untuk membersihkan semua kolom filter)"
                                    class="h-8 w-full inline-flex items-center justify-center rounded-lg bg-slate-50 text-slate-600 hover:text-rose-500 hover:bg-rose-50 transition-all">
                                    <i class="fas fa-times-circle text-[12px]"></i>
                                </button>
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" data-name="filter_product" placeholder="Cari..."
                                    class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300">
                            </td>
                            <td class="px-2 py-2 border-r border-slate-200">
                                <input type="text" data-name="filter_sku" placeholder="Cari..."
                                    class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300">
                            </td>
                            @foreach($outletsForColumns as $outletCol)
                                <td class="px-2 py-2 bg-slate-100/30 border-r border-slate-200">
                                    <input type="text" data-name="filter_outlet_{{ $outletCol->id }}" placeholder=""
                                        class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-white border border-slate-200 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all text-right placeholder:text-slate-300">
                                </td>
                            @endforeach
                            <td class="px-2 py-2 bg-indigo-50/30">
                                <input type="text" data-name="filter_qty" placeholder=""
                                    class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-white border border-indigo-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all text-right placeholder:text-indigo-300">
                            </td>
                            <td class="px-2 py-2 bg-emerald-50/30 border-l border-emerald-100/50">
                                <input type="text" data-name="filter_amount" placeholder=""
                                    class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-white border border-emerald-100 rounded-lg focus:ring-1 focus:ring-emerald-500 transition-all text-right placeholder:text-emerald-300">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" data-name="filter_avg" placeholder=""
                                    class="filter-input w-full px-2 py-1.5 text-[11px] font-normal bg-slate-50 border border-slate-100 rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all text-right placeholder:text-slate-300">
                            </td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($products as $index => $product)
                            <tr class="group hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-normal text-slate-400">#{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5 text-sm font-normal text-slate-800">{{ $product->product_name }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-sm font-mono text-slate-500 border-r border-slate-100">{{ $product->sku }}</td>
                                @foreach($outletsForColumns as $outletCol)
                                    @php
                                        $oQty = $product->{"outlet_{$outletCol->id}_qty"} ?? 0;
                                    @endphp
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right text-sm font-normal border-r border-slate-100 {{ $oQty > 0 ? 'text-indigo-600' : 'text-slate-300' }}">
                                        @if($oQty > 0)
                                            <a href="{{ route('admin.reports.sales.index', ['tab' => 'penjualan', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_ids' => [$outletCol->id], 'product_id' => $product->id, 'view_mode' => 'detail']) }}"
                                               target="_blank"
                                               class="hover:text-indigo-900 hover:underline transition-all"
                                               title="Lihat Detail Transaksi">
                                                {{ number_format($oQty, 0, ',', '.') }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-sm font-bold text-slate-800 bg-indigo-50/30">
                                    <a href="{{ route('admin.reports.sales.index', ['tab' => 'penjualan', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'outlet_ids' => $filters['outlet_ids'] ?? [], 'product_id' => $product->id, 'view_mode' => 'detail']) }}"
                                       target="_blank"
                                       class="hover:text-indigo-600 hover:underline transition-all text-indigo-700"
                                       title="Lihat Detail Transaksi Semua Outlet yang Difilter">
                                        {{ number_format($product->total_qty, 0, ',', '.') }}
                                    </a>
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-sm font-medium text-emerald-600 bg-emerald-50/30 border-l border-emerald-100/50">
                                    Rp {{ number_format($product->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2.5 whitespace-nowrap text-right text-sm text-slate-500 italic">
                                    Rp {{ number_format($product->total_amount / max(1, $product->total_qty), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + count($outletsForColumns) }}" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-40">
                                        <i class="fas fa-box-open text-4xl mb-4"></i>
                                        <p class="text-sm font-normal text-slate-500 italic">Tidak ada data penjualan produk
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($products->count() > 0)
                        <tfoot class="bg-indigo-50/30">
                            <tr>
                                <td colspan="3"
                                    class="px-4 py-3 text-right text-xs font-bold text-slate-600 uppercase tracking-wider border-r border-slate-200">
                                    GRAND TOTAL:
                                </td>
                                @foreach($outletsForColumns as $outletCol)
                                    @php
                                        $oGrandQty = $products->sum("outlet_{$outletCol->id}_qty");
                                    @endphp
                                    <td class="px-4 py-3 text-right text-sm font-bold tracking-tight text-indigo-600 border-r border-slate-200 bg-indigo-50/40">
                                        {{ $oGrandQty > 0 ? number_format($oGrandQty, 0, ',', '.') : '-' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-right text-base font-black text-slate-900 border-l border-indigo-100/50 bg-indigo-100/50">
                                    {{ number_format($grandTotal['total_qty'], 0, ',', '.') }}
                                </td>
                                <td colspan="2" class="px-4 py-3 text-right text-base font-black text-emerald-700 bg-emerald-100/40 border-l border-emerald-200/50">
                                    Rp {{ number_format($grandTotal['total_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Print CSS -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            aside,
            header {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }
        }
        
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            cursor: col-resize;
            z-index: 10;
        }

        .resize-handle:hover {
            background: rgba(99, 102, 241, 0.5);
        }

        .resizing {
            cursor: col-resize;
            user-select: none;
        }
    </style>
    @push('scripts')
        <script>
            // Column resizing logic
            const table = document.querySelector('table');
            if (table) {
                const headers = table.querySelectorAll('th.resizable');
                headers.forEach(th => {
                    const handle = th.querySelector('.resize-handle');
                    if (handle) {
                        let startX, startWidth;
                        handle.addEventListener('mousedown', (e) => {
                            startX = e.pageX; startWidth = th.offsetWidth;
                            document.body.classList.add('resizing');
                            const move = (e) => th.style.width = Math.max(50, startWidth + (e.pageX - startX)) + 'px';
                            const up = () => {
                                document.body.classList.remove('resizing');
                                document.removeEventListener('mousemove', move);
                                document.removeEventListener('mouseup', up);
                            };
                            document.addEventListener('mousemove', move);
                            document.addEventListener('mouseup', up);
                        });
                    }
                });
            }

            // Filtering logic via Input
            const filterInputs = document.querySelectorAll('.filter-input');
            const clearBtn = document.getElementById('clearFilters');

            function updateFilter(name, value) {
                const url = new URL(window.location.href);
                if (value.trim()) url.searchParams.set(name, value.trim());
                else url.searchParams.delete(name);
                window.location.href = url.toString();
            }

            let timer;
            filterInputs.forEach(input => {
                const name = input.dataset.name;
                input.addEventListener('input', (e) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => updateFilter(name, e.target.value), 600);
                });
            });

            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    const keysToDelete = [];
                    url.searchParams.forEach((val, key) => {
                        if(key.startsWith('filter_')) keysToDelete.push(key);
                    });
                    keysToDelete.forEach(key => url.searchParams.delete(key));
                    window.location.href = url.toString();
                });
            }

            // Preserve URL params in inputs on load
            const params = new URLSearchParams(window.location.search);
            filterInputs.forEach(input => {
                if (params.has(input.dataset.name)) input.value = params.get(input.dataset.name);
            });

            (() => {
                const wrap = document.getElementById('salesProductOutletFilterWrap');
                if (!wrap) return;

                const dropdownBtn = document.getElementById('salesProductOutletDropdownBtn');
                const dropdownLabel = document.getElementById('salesProductOutletDropdownLabel');
                const dropdownMenu = document.getElementById('salesProductOutletDropdownMenu');
                const allCheckbox = document.getElementById('salesProductOutletAllCheckbox');
                const itemCheckboxes = Array.from(document.querySelectorAll('.sales-product-outlet-checkbox'));

                const updateLabel = () => {
                    const checkedItems = itemCheckboxes.filter((checkbox) => checkbox.checked);
                    
                    // Jika semua checkbox terpilih, checkbox 'Semua Outlet' otomatis dicentang
                    if (checkedItems.length === itemCheckboxes.length) {
                         allCheckbox.checked = true;
                         dropdownLabel.textContent = 'Semua Outlet';
                         return;
                    }
                    
                    allCheckbox.checked = false;

                    if (checkedItems.length === 0) {
                        dropdownLabel.textContent = 'Semua Outlet';
                        return;
                    }

                    if (checkedItems.length === 1) {
                        dropdownLabel.textContent = checkedItems[0].parentElement?.textContent?.trim() || '1 Outlet Dipilih';
                        return;
                    }

                    dropdownLabel.textContent = `${checkedItems.length} Outlet Dipilih`;
                };

                dropdownBtn.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });

                document.addEventListener('click', (event) => {
                    if (wrap.contains(event.target)) return;
                    dropdownMenu.classList.add('hidden');
                });

                allCheckbox.addEventListener('change', () => {
                    // Jika diklik, ubah status semua checkbox anak mengikuti status checkbox ini
                    const isChecked = allCheckbox.checked;
                    itemCheckboxes.forEach((checkbox) => { 
                        checkbox.checked = isChecked; 
                    });
                    updateLabel();
                });

                itemCheckboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => {
                        updateLabel();
                    });
                });

                // Inisialisasi awal
                const checkedItems = itemCheckboxes.filter((checkbox) => checkbox.checked);
                if (checkedItems.length === 0 || checkedItems.length === itemCheckboxes.length) {
                    allCheckbox.checked = true;
                    itemCheckboxes.forEach((checkbox) => { checkbox.checked = true; });
                }
                updateLabel();
            })();
        </script>
    @endpush
@endsection
