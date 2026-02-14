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
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <a href="{{ route('pos.sessions.print', $activeSession->id) }}" target="_blank"
                class="bg-white p-4 rounded-xl shadow-sm border border-rose-100 hover:border-rose-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">print</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-rose-700">Rekap Shift</span>
            </a>

            <!-- History Button with Vue Click Handler -->
            <button @click="openHistory" type="button"
                class="bg-white p-4 rounded-xl shadow-sm border border-blue-100 hover:border-blue-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24 w-full">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">history</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-blue-700">Riwayat</span>
            </button>

            <a href="{{ route('pos.attendance.index') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-violet-100 hover:border-violet-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">badge</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-violet-700">Absensi</span>
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
                        {{ $todaySales ? $todaySales->count() : 0 }} Transaksi
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
                                {{ number_format($todaySales->sum('total_amount'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-[10px] mb-0.5">Rata-rata</p>
                            <p class="text-lg font-bold text-gray-900">Rp
                                {{ number_format($todaySales->avg('total_amount'), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 font-medium text-[10px] uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-2">Invoice</th>
                                    <th class="px-4 py-2">Pelanggan</th>
                                    <th class="px-4 py-2">Total</th>
                                    <th class="px-4 py-2">Waktu</th>
                                    <th class="px-4 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                @foreach($todaySales->take(5) as $sale)
                                    <tr class="hover:bg-gray-50/50 transition-colors cursor-pointer" @click="openHistoryWithSale({{ $sale->id }})">
                                        <td class="px-4 py-2 font-mono text-gray-900">{{ $sale->invoice_number }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900">Rp
                                            {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $sale->created_at->format('H:i') }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 text-[10px] font-semibold bg-green-100 text-green-700 rounded-full">
                                                {{ ucfirst($sale->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
                        di sesi ini.</div>
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
                historySales: [],
                selectedSale: null,
                isLoadingHistory: false,
                isVoiding: false,
                voidReason: '',
                voidToken: '',
            }
        },
        methods: {
            openHistory() {
                this.showHistoryModal = true;
                this.fetchHistory();
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
                window.open(`/pos/sales/${sale.id}/print`, '_blank', 'width=400,height=600');
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