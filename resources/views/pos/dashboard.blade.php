@extends('layouts.pos_theme')

@section('content')
<div id="dashboardApp" v-cloak>
    {{-- Kitchen Display Link (Preserved from original) --}}
    @if(auth()->user()->hasRole(['admin', 'manager', 'kitchen']))
        <div class="flex justify-end mb-4">
            <a href="{{ route('pos.kitchen.index') }}"
                class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-all shadow-md">
                <span class="material-icons-round text-base">restaurant</span>
                <span>Kitchen Display</span>
            </a>
        </div>
    @endif

    @if($activeSession)
        {{-- Active Session Card --}}
        <div class="bg-surface-light rounded-2xl shadow-soft overflow-hidden relative group">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
            <div class="p-4 md:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-[10px] uppercase tracking-wider font-bold text-emerald-600">Sesi Aktif</h2>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 font-mono tracking-tight mb-0.5">
                        {{ $activeSession->session_number }}
                    </h3>
                    <p class="text-text-muted-light flex items-center gap-1.5 text-[10px] md:text-xs">
                        <span class="material-icons-round text-xs">schedule</span>
                        Dibuka: {{ $activeSession->opened_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full md:w-auto">
                    <div class="text-left md:text-right">
                        <p class="text-[10px] text-text-muted-light font-medium mb-0.5">Saldo Awal</p>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">
                            Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}
                        </p>
                    </div>
                    <a href="{{ route('pos.sessions.close') }}"
                        class="w-full md:w-auto bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2 text-xs">
                        <span class="material-icons-round text-sm">lock_clock</span>
                        Tutup Shift
                    </a>
                </div>
            </div>
        </div>

        {{-- Quick Actions Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:[grid-template-columns:repeat(auto-fit,minmax(170px,1fr))] gap-3 my-4">
            <a href="{{ route('pos.sales.create') }}"
                class="bg-emerald-50 p-4 rounded-xl shadow-sm border border-emerald-100 hover:border-emerald-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-white text-emerald-600 flex items-center justify-center group-hover:scale-110 transition shadow-sm">
                    <span class="material-icons-round">point_of_sale</span>
                </div>
                <span class="text-xs font-bold text-emerald-800 group-hover:text-emerald-900">Kasir / POS</span>
            </a>

            <button type="button" @click="openRecapModal"
                class="w-full bg-white p-4 rounded-xl shadow-sm border border-rose-100 hover:border-rose-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">print</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-rose-700">Print Rekap</span>
            </button>

            <a href="{{ route('pos.petty-cash.index') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-amber-100 hover:border-amber-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">payments</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-amber-700">Petty Cash</span>
            </a>

            <a href="{{ route('pos.attendance.index') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-violet-100 hover:border-violet-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">badge</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-violet-700">Absensi</span>
            </a>

            <a href="{{ route('pos.sales.history') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-sky-100 hover:border-sky-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-sky-50 text-sky-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">assessment</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-sky-700">Laporan</span>
            </a>

            <form action="{{ route('logout') }}" method="POST" class="contents">
                @csrf
                <button type="submit"
                    class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:border-red-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24 w-full">
                    <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-500 group-hover:bg-red-50 group-hover:text-red-600 flex items-center justify-center group-hover:scale-110 transition">
                        <span class="material-icons-round">logout</span>
                    </div>
                    <span class="text-xs font-bold text-gray-700 group-hover:text-red-700">Logout</span>
                </button>
            </form>
        </div>

        {{-- Transactions Card --}}
        <div class="bg-surface-light rounded-2xl shadow-soft flex flex-col relative overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-round text-primary text-base">receipt_long</span>
                    Transaksi Hari Ini
                </h2>
                <div class="flex gap-2">
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-semibold uppercase tracking-wide">
                        {{ $todaySalesCount }} Transaksi
                    </span>
                </div>
            </div>

            @if($todaySales && $todaySales->count() > 0)
                {{-- Has Transactions State --}}
                <div class="p-4">
                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-[10px] mb-0.5">Total Penjualan</p>
                            <p class="text-lg font-bold text-gray-900">Rp
                                {{ number_format($todaySalesTotalAmount, 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-[10px] mb-0.5">Rata-rata</p>
                            <p class="text-lg font-bold text-gray-900">Rp
                                {{ number_format($todaySalesAverageAmount, 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 font-medium text-[10px] uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-2">Invoice</th>
                                    <th class="px-4 py-2">Items</th>
                                    <th class="px-4 py-2">Tipe</th>
                                    <th class="px-4 py-2">Pembayaran</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                @foreach($todaySales as $sale)
                                    <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" @click="openHistoryWithSale({{ $sale->id }})">
                                        <td class="px-4 py-2 font-mono text-gray-900 whitespace-nowrap">
                                            {{ $sale->invoice_number }}
                                            <div class="text-[10px] text-gray-400">{{ $sale->created_at->format('H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            <span class="line-clamp-1" title="{{ $sale->items->pluck('product_name')->join(', ') }}">
                                                {{ $sale->items->pluck('product_name')->join(', ') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold text-[10px]">
                                                {{ ucfirst($sale->sales_type) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            {{ $sale->payments->first()?->paymentMethod?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-2 font-bold text-gray-900 text-right">
                                            Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($todaySales->hasPages())
                        <div class="mt-4">
                            {{ $todaySales->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>

                {{-- Floating Action Button for easy access when list is long --}}
                <div class="fixed bottom-6 right-6 z-50">
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white w-12 h-12 rounded-full shadow-xl shadow-violet-500/30 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 group">
                        <span
                            class="material-icons-round text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                    </a>
                </div>

            @else
                {{-- Empty State (Compact) --}}
                <div class="flex-1 flex flex-col items-center justify-center p-6 md:py-8 text-center relative z-10">
                    <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center mb-4 relative">
                        <div
                            class="absolute inset-0 border border-dashed border-gray-200 rounded-full animate-[spin_10s_linear_infinite]">
                        </div>
                        <span class="material-icons-round text-5xl text-gray-300">assignment_late</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Belum ada transaksi</h3>
                    <p class="text-text-muted-light max-w-xs mb-6 text-xs leading-relaxed">
                        Data penjualan akan muncul di sini setelah Anda memulai transaksi pertama.
                    </p>
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2 group">
                        <span class="material-icons-round text-lg group-hover:rotate-12 transition-transform">add_shopping_cart</span>
                        Mulai Transaksi
                    </a>
                </div>
                <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
                    style="background-image: radial-gradient(#6B7280 1px, transparent 1px); background-size: 24px 24px;"></div>
            @endif
        </div>

    @else
        {{-- No Active Session State --}}
        <div class="flex items-center justify-center min-h-[80vh]">
            <div class="bg-surface-light p-8 md:p-12 rounded-2xl shadow-soft max-w-lg w-full text-center">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-icons-round text-5xl text-primary">point_of_sale</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Sesi Kasir Belum Dibuka</h2>
                <p class="text-text-muted-light mb-8">
                    Anda perlu membuka shift kasir terlebih dahulu sebelum dapat melakukan transaksi penjualan.
                </p>
                <a href="{{ route('pos.sessions.open') }}"
                    class="w-full inline-flex justify-center items-center gap-2 bg-primary hover:bg-red-700 text-white px-8 py-3.5 rounded-xl font-bold text-lg shadow-lg shadow-red-500/20 hover:shadow-red-500/40 transition-all duration-300">
                    <span class="material-icons-round">login</span>
                    Buka Shift Kasir
                </a>
            </div>
        </div>
    @endif

    <!-- History Modal -->
    <div v-if="showHistoryModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        style="display: none;" v-show="showHistoryModal">
        <div class="bg-surface-light rounded-2xl shadow-2xl w-full max-w-5xl h-[80vh] flex overflow-hidden">

            <!-- Left: Transaction List -->
            <div class="w-1/3 border-r border-gray-200 bg-gray-50 flex flex-col">
                <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h3 class="font-bold text-gray-800">Riwayat Transaksi</h3>
                    <button @click="fetchHistory" :disabled="isLoadingHistory" class="text-primary hover:text-red-700">
                        <svg :class="{'animate-spin': isLoadingHistory}" class="w-5 h-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div v-if="isLoadingHistory" class="p-8 text-center text-gray-400">Loading...</div>
                    <div v-else-if="historySales.length === 0" class="p-8 text-center text-gray-400">Belum ada transaksi
                        pada riwayat sesi Anda.</div>
                    <template v-else>
                        <div v-for="sale in historySales" :key="sale.id" @click="selectSale(sale)"
                            :class="{'bg-white border-l-4 border-primary shadow-sm': selectedSale && selectedSale.id === sale.id, 'hover:bg-gray-100 border-l-4 border-transparent': !selectedSale || selectedSale.id !== sale.id}"
                            class="p-4 cursor-pointer border-b border-gray-100 transition-all">
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-bold text-gray-800 text-sm">@{{ sale.invoice_number }}</span>
                                <span class="text-xs font-mono text-gray-400">@{{ formatTime(sale.created_at) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-primary font-bold">Rp @{{ formatNumber(sale.total_amount) }}</span>
                                <span v-if="sale.status === 'cancelled'"
                                    class="px-2 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-bold uppercase">VOID</span>
                                <span v-else
                                    class="px-2 py-0.5 rounded text-[10px] bg-green-100 text-green-700 font-bold uppercase">OK</span>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="p-4 border-t border-gray-200 bg-white">
                    <button @click="closeHistory"
                        class="w-full py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-gray-700 font-bold">Tutup</button>
                </div>
            </div>

            <!-- Right: Transaction Detail -->
            <div class="w-2/3 flex flex-col bg-white">
                <div v-if="selectedSale" class="flex-1 flex flex-col h-full">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-100 flex justify-between items-start bg-gray-50/50">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-1">@{{ selectedSale.invoice_number }}</h2>
                            <div class="flex gap-4 text-sm text-gray-500">
                                <span>@{{ formatDate(selectedSale.created_at) }}</span>
                                <span>•</span>
                                <span>@{{ selectedSale.customer ? selectedSale.customer.name : 'Guest' }}</span>
                                <span>•</span>
                                <span class="font-medium text-gray-700">@{{
                                    selectedSale.payments[0]?.payment_method?.name || 'Tunai' }}</span>
                            </div>
                        </div>
                        <div v-if="selectedSale.status === 'cancelled'" class="text-right">
                            <div
                                class="text-red-600 font-bold text-xl uppercase tracking-widest border-2 border-red-600 px-4 py-1 rounded mb-1 transform -rotate-6">
                                VOID</div>
                            <p class="text-xs text-red-500 max-w-[200px]">@{{ selectedSale.notes }}</p>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="flex-1 overflow-y-auto p-6">
                        <table class="w-full">
                            <thead>
                                <tr
                                    class="text-left text-xs font-bold text-gray-400 uppercase border-b border-gray-100">
                                    <th class="pb-3 pl-2">Item</th>
                                    <th class="pb-3 text-right">Qty</th>
                                    <th class="pb-3 text-right">Price</th>
                                    <th class="pb-3 text-right pr-2">Total</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <tr v-for="item in selectedSale.items" :key="item.id"
                                    class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50">
                                    <td class="py-3 pl-2">
                                        <div class="font-medium text-gray-800">@{{ item.product_name }}</div>
                                        <div v-if="item.notes" class="text-xs text-gray-400 italic">@{{ item.notes }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-right font-mono">@{{ item.quantity }}</td>
                                    <td class="py-3 text-right text-gray-500">@{{ formatNumber(item.unit_price) }}</td>
                                    <td class="py-3 text-right font-bold text-gray-700 pr-2">@{{
                                        formatNumber(item.subtotal) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-between items-center">
                        <div class="text-right flex-1 mr-8">
                            <p class="text-sm text-gray-500 mb-1">Total Amount</p>
                            <p class="text-3xl font-bold text-primary">Rp @{{ formatNumber(selectedSale.total_amount) }}
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="printReceiptFromHistory(selectedSale)"
                                class="px-6 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 shadow-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Struk
                            </button>
                            <button v-if="selectedSale.status !== 'cancelled'" @click="openVoidModal"
                                class="px-6 py-3 bg-red-100 text-red-700 border border-red-200 font-bold rounded-xl hover:bg-red-200 shadow-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Void / Batal
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-300">
                    <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="font-medium">Pilih transaksi untuk melihat detail</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recap Print Modal -->
    <div v-if="showRecapModal"
        class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        style="display: none;" v-show="showRecapModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-1">Print Rekap Transaksi</h3>
            <p class="text-sm text-gray-500 mb-5">Pilih periode transaksi sebelum cetak. Maksimal rentang 1 bulan (31 hari).</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="recap_date_from" class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Mulai</label>
                    <input id="recap_date_from" type="date" v-model="recapDateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="recap_date_to" class="block text-sm font-semibold text-gray-700 mb-1">Tanggal Akhir</label>
                    <input id="recap_date_to" type="date" v-model="recapDateTo"
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-3">
                Rentang dipilih: <span class="font-semibold text-gray-700">@{{ recapDateFrom || '-' }}</span>
                s.d <span class="font-semibold text-gray-700">@{{ recapDateTo || '-' }}</span>
            </p>

            <div class="flex gap-3 mt-6">
                <button @click="closeRecapModal"
                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold transition">
                    Batal
                </button>
                <button @click="printRecapWithRange"
                    class="flex-1 py-2.5 bg-primary hover:bg-red-700 text-white rounded-lg font-semibold transition">
                    Cetak Rekap
                </button>
            </div>
        </div>
    </div>

    <!-- Void Confirmation Modal -->
    <div v-if="showVoidModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        style="display: none;" v-show="showVoidModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-[bounceIn_0.3s_ease-out]">
            <h3 class="text-xl font-bold text-red-600 mb-1 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Konfirmasi Void
            </h3>
            <p class="text-sm text-gray-500 mb-6">Anda akan membatalkan transaksi <strong>@{{
                    selectedSale?.invoice_number }}</strong>. Stok akan dikembalikan otomatis.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Alasan Pembatalan</label>
                    <input type="text" v-model="voidReason" placeholder="Contoh: Salah input, pelanggan batal..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Token Void (Dari Admin)</label>
                        <input type="text" v-model="voidToken" placeholder="Masukkan 6 Digit Token..." maxlength="6"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:outline-none tracking-widest text-lg font-mono text-center">
                        <p class="text-xs text-gray-400 mt-1">*Minta token ke Admin/Manager untuk membatalkan</p>
                    </div>
                </div>

                <div class="flex gap-3 mt-8">
                    <button @click="closeVoidModal"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-bold transition">Batal</button>
                    <button @click="confirmVoid" :disabled="isVoiding || !voidReason || !voidToken"
                        :class="isVoiding || !voidReason || !voidToken ? 'bg-gray-300 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-500/30'"
                        class="flex-1 py-3 rounded-xl font-bold transition flex items-center justify-center gap-2">
                        <span v-if="isVoiding">Processing...</span>
                        <span v-else>Void Transaksi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
    const { createApp } = Vue;

    createApp({
        data() {
            return {
                showHistoryModal: false,
                showVoidModal: false,
                showRecapModal: false,
                historySales: [],
                selectedSale: null,
                isLoadingHistory: false,
                isVoiding: false,
                voidReason: '',
                voidToken: '',
                activeSessionId: @json($activeSession->id ?? null),
                recapDateFrom: @json(now()->toDateString()),
                recapDateTo: @json(now()->toDateString()),
                outletId: @json(auth()->user()->outlet_id ?? null),
                userId: @json(auth()->id() ?? null),
            }
        },
        methods: {
            openRecapModal() {
                if (!this.activeSessionId) {
                    alert('Sesi aktif tidak ditemukan. Buka shift dulu sebelum cetak rekap.');
                    return;
                }

                this.showRecapModal = true;
            },
            closeRecapModal() {
                this.showRecapModal = false;
            },
            parseDateOnly(value) {
                if (!value) {
                    return null;
                }

                const normalized = new Date(`${value}T00:00:00`);
                return Number.isNaN(normalized.getTime()) ? null : normalized;
            },
            calculateRangeDays(dateFrom, dateTo) {
                const start = this.parseDateOnly(dateFrom);
                const end = this.parseDateOnly(dateTo);

                if (!start || !end) {
                    return null;
                }

                const msPerDay = 24 * 60 * 60 * 1000;
                return Math.floor((end.getTime() - start.getTime()) / msPerDay) + 1;
            },
            printRecapWithRange() {
                if (!this.recapDateFrom || !this.recapDateTo) {
                    alert('Tanggal mulai dan tanggal akhir wajib diisi.');
                    return;
                }

                const start = this.parseDateOnly(this.recapDateFrom);
                const end = this.parseDateOnly(this.recapDateTo);

                if (!start || !end) {
                    alert('Format tanggal tidak valid.');
                    return;
                }

                if (start.getTime() > end.getTime()) {
                    alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir.');
                    return;
                }

                const rangeDays = this.calculateRangeDays(this.recapDateFrom, this.recapDateTo);
                if (!rangeDays || rangeDays > 31) {
                    alert('Rentang tanggal maksimal 31 hari (1 bulan).');
                    return;
                }

                const params = new URLSearchParams({
                    view: 'product',
                    date_from: this.recapDateFrom,
                    date_to: this.recapDateTo,
                    print: '1',
                });
                const baseUrl = `{{ route('pos.sales.history') }}?${params.toString()}`;
                const engine = this.getPrintEngine();

                // Mode thermal langsung (tanpa dialog) -> kirim ESC/POS ke printer Bluetooth.
                if (engine === 'webbt' || engine === 'rawbt') {
                    this.printRecapThermal(baseUrl, engine);
                    this.showRecapModal = false;
                    return;
                }

                // Mode lain (browser/bridge): tetap buka tab cetak biasa.
                window.open(baseUrl, '_blank', 'noopener,noreferrer');
                this.showRecapModal = false;
            },
            getPrintSettingsStorageKey() {
                const outletKey = this.outletId ? `outlet_${this.outletId}` : 'outlet_unknown';
                const userKey = this.userId ? `user_${this.userId}` : 'user_unknown';
                return `Ransa_pos_print_settings_${outletKey}_${userKey}`;
            },
            getPrintEngine() {
                const allowed = ['browser', 'bridge', 'rawbt', 'webbt'];
                const readEngine = (raw) => {
                    if (!raw) return null;
                    try {
                        const parsed = JSON.parse(raw);
                        return allowed.includes(parsed.printEngine) ? parsed.printEngine : null;
                    } catch (e) { return null; }
                };

                // 1) Key persis (outlet+user).
                let engine = readEngine(localStorage.getItem(this.getPrintSettingsStorageKey()));
                if (engine) return engine;

                // 2) Fallback: cari key setting print mana pun milik user ini (jaga-jaga
                //    kalau outletId berbeda sumber), lalu key setting print apa pun.
                const userSuffix = this.userId ? `_user_${this.userId}` : null;
                let anyEngine = null;
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (!key || key.indexOf('Ransa_pos_print_settings_') !== 0) continue;
                    const e = readEngine(localStorage.getItem(key));
                    if (!e) continue;
                    if (userSuffix && key.endsWith(userSuffix)) return e; // prioritas user ini
                    anyEngine = anyEngine || e;
                }
                return anyEngine || 'browser';
            },
            async fetchRecapBase64(baseUrl) {
                const url = baseUrl + '&format=escpos';
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const text = await response.text();
                if (!response.ok) {
                    throw new Error('server balas HTTP ' + response.status);
                }
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // Server mengembalikan HTML (mis. halaman rekap/ error), bukan JSON.
                    throw new Error('respon bukan JSON - kemungkinan kode rekap belum ter-deploy di server. Cuplikan: ' + text.slice(0, 50));
                }
                if (!data || !data.base64) {
                    throw new Error('data ESC/POS rekap kosong');
                }
                return data.base64;
            },
            async printRecapThermal(baseUrl, engine) {
                if (engine === 'rawbt') {
                    try {
                        const base64 = await this.fetchRecapBase64(baseUrl);
                        window.location.href = 'intent:base64,' + base64
                            + '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';
                    } catch (error) {
                        console.error('Gagal cetak rekap via RawBT:', error);
                        window.open(baseUrl, '_blank', 'noopener,noreferrer');
                    }
                    return;
                }

                // engine === 'webbt'
                // Hubungkan printer DULU (mumpung masih dalam "user gesture" dari klik),
                // baru ambil data ESC/POS, lalu tulis ke printer.
                let characteristic = null;
                try {
                    characteristic = await this.webbtGetCharacteristic();
                } catch (e) {
                    characteristic = null;
                }
                if (!characteristic) {
                    alert('Printer Bluetooth tidak tersedia. Membuka cetak biasa.');
                    window.open(baseUrl, '_blank', 'noopener,noreferrer');
                    return;
                }

                // Langkah 1: ambil data ESC/POS rekap dari server.
                let base64;
                try {
                    base64 = await this.fetchRecapBase64(baseUrl);
                } catch (error) {
                    console.error('Gagal AMBIL DATA rekap:', error);
                    alert('Gagal AMBIL DATA rekap dari server: ' + (error && error.message ? error.message : error) + '\n\nMembuka cetak biasa sebagai cadangan.');
                    window.open(baseUrl, '_blank', 'noopener,noreferrer');
                    return;
                }

                // Langkah 2: kirim ke printer thermal.
                try {
                    await this.webbtWriteBytes(characteristic, this.base64ToBytes(base64));
                } catch (error) {
                    console.error('Gagal KIRIM ke printer:', error);
                    alert('Gagal KIRIM rekap ke printer: ' + (error && error.message ? error.message : error) + '\n\nMembuka cetak biasa sebagai cadangan.');
                    window.open(baseUrl, '_blank', 'noopener,noreferrer');
                }
            },
            getWebBtServiceUuids() {
                return [
                    0xFFE0, 0xFF00, 0x18F0, 0xFEE7,
                    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
                    '0000ff00-0000-1000-8000-00805f9b34fb',
                ];
            },
            async webbtDiscoverCharacteristic(device) {
                const server = await device.gatt.connect();
                const services = await server.getPrimaryServices();
                for (const service of services) {
                    let characteristics = [];
                    try {
                        characteristics = await service.getCharacteristics();
                    } catch (e) {
                        continue;
                    }
                    const writable = characteristics.find(c => c.properties.writeWithoutResponse)
                        || characteristics.find(c => c.properties.write);
                    if (writable) return writable;
                }
                return null;
            },
            async webbtGetCharacteristic() {
                if (!navigator.bluetooth) {
                    alert('Browser ini tidak mendukung Web Bluetooth, atau halaman tidak diakses lewat koneksi aman (HTTPS/localhost).');
                    return null;
                }

                // 1) Coba pakai printer yang SUDAH diizinkan (reconnect tanpa dialog).
                if (typeof navigator.bluetooth.getDevices === 'function') {
                    try {
                        const devices = await navigator.bluetooth.getDevices();
                        for (const device of devices) {
                            try {
                                const ch = await this.webbtDiscoverCharacteristic(device);
                                if (ch) return ch;
                            } catch (e) { /* coba device berikutnya */ }
                        }
                    } catch (e) { /* lanjut ke pemilihan manual */ }
                }

                // 2) Belum ada izin -> tampilkan pemilih (klik tombol = user gesture, diizinkan).
                try {
                    const device = await navigator.bluetooth.requestDevice({
                        acceptAllDevices: true,
                        optionalServices: this.getWebBtServiceUuids(),
                    });
                    return await this.webbtDiscoverCharacteristic(device);
                } catch (e) {
                    return null;
                }
            },
            async webbtWriteBytes(characteristic, bytes) {
                // Utamakan tulis DENGAN respons (ACK) agar tidak ada paket dobel.
                const chunkSize = 128;
                const canWithResponse = !!characteristic.properties.write;
                for (let i = 0; i < bytes.length; i += chunkSize) {
                    const chunk = bytes.slice(i, i + chunkSize);
                    if (canWithResponse) {
                        await characteristic.writeValue(chunk);
                    } else {
                        await characteristic.writeValueWithoutResponse(chunk);
                        await new Promise(r => setTimeout(r, 40));
                    }
                }
            },
            base64ToBytes(b64) {
                const binary = atob(b64);
                const bytes = new Uint8Array(binary.length);
                for (let i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }
                return bytes;
            },
            async openReceiptPrintWindow(saleId) {
                const normalizedSaleId = Number(saleId);
                if (!Number.isFinite(normalizedSaleId) || normalizedSaleId <= 0) {
                    alert('ID transaksi tidak valid untuk dicetak.');
                    return;
                }

                const engine = this.getPrintEngine();

                if (engine === 'rawbt') {
                    try {
                        const b64 = await this.fetchSaleBase64(normalizedSaleId);
                        window.location.href = 'intent:base64,' + b64
                            + '#Intent;scheme=rawbt;package=ru.a402d.rawbtprinter;end;';
                        return;
                    } catch (e) {
                        console.error('Gagal cetak struk via RawBT:', e);
                    }
                }

                if (engine === 'webbt') {
                    // Hubungkan printer dulu (mumpung masih dalam user-gesture klik), lalu cetak.
                    let characteristic = null;
                    try {
                        characteristic = await this.webbtGetCharacteristic();
                    } catch (e) {
                        characteristic = null;
                    }
                    if (characteristic) {
                        try {
                            const b64 = await this.fetchSaleBase64(normalizedSaleId);
                            await this.webbtWriteBytes(characteristic, this.base64ToBytes(b64));
                            return;
                        } catch (e) {
                            console.error('Gagal cetak struk via Web Bluetooth:', e);
                        }
                    }
                }

                // Fallback: cetak biasa lewat browser.
                window.location.href = `/pos/sales/${normalizedSaleId}/print?autoprint=1`;
            },
            async fetchSaleBase64(saleId) {
                const response = await fetch(`/pos/sales/${saleId}/escpos`, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                const data = await response.json();
                if (!data || !data.base64) {
                    throw new Error('Data ESC/POS struk kosong.');
                }
                return data.base64;
            },
            openHistoryWithSale(saleId) {
                this.showHistoryModal = true;
                this.fetchHistory().then(() => {
                    const found = this.historySales.find(s => s.id == saleId);
                    if (found) {
                        this.selectedSale = found;
                    }
                });
            },
            closeHistory() {
                this.showHistoryModal = false;
                this.selectedSale = null;
            },
            async fetchHistory() {
                this.isLoadingHistory = true;
                try {
                    const response = await fetch('{{ route('pos.sales.history') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await this.parseApiResponse(response);

                    if (!response.ok) {
                        throw new Error(result.message || 'Gagal mengambil history transaksi');
                    }

                    if (result.success) {
                        this.historySales = result.data;
                        if (this.historySales.length > 0 && !this.selectedSale) {
                            this.selectedSale = this.historySales[0];
                        }
                    }
                } catch (e) {
                    console.error(e);
                    alert('Gagal mengambil history transaksi');
                } finally {
                    this.isLoadingHistory = false;
                }
            },
            selectSale(sale) {
                this.selectedSale = sale;
            },
            formatNumber(num, decimals = 0) {
                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                }).format(num ?? 0);
            },
            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            },
            formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            },
            printReceiptFromHistory(sale) {
                this.openReceiptPrintWindow(sale && sale.id ? sale.id : null);
            },
            openVoidModal() {
                this.voidReason = '';
                this.voidToken = '';
                this.showVoidModal = true;
            },
            closeVoidModal() {
                this.showVoidModal = false;
            },
            async confirmVoid() {
                if (!this.selectedSale) return;
                
                this.isVoiding = true;
                try {
                    const response = await fetch(`/pos/sales/${this.selectedSale.id}/void`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            reason: this.voidReason,
                            token: this.voidToken
                        })
                    });
                    const result = await this.parseApiResponse(response);

                    if (!response.ok) {
                        alert('GAGAL: ' + (result.message || 'Permintaan void gagal.'));
                        return;
                    }

                    if (result.success) {
                        alert('Transaksi BERHASIL dibatalkan.');
                        this.closeVoidModal();
                        // Update local data
                        this.selectedSale.status = 'cancelled';
                        this.selectedSale.notes = result.data.notes;
                        // Refresh history
                        this.fetchHistory();
                    } else {
                        alert('GAGAL: ' + result.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Terjadi kesalahan sistem.');
                } finally {
                    this.isVoiding = false;
                }
            },
            async parseApiResponse(response) {
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return await response.json();
                }
                throw new Error('Respon server tidak valid (bukan JSON).');
            }
        }
    }).mount('#dashboardApp');
</script>
<style>
    [v-cloak] { display: none; }
</style>
@endsection
