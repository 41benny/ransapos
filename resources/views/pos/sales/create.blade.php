@extends('layouts.pos')

@section('title', 'Transaksi Penjualan')
@section('page-title', 'Transaksi Baru')

@section('content')
    <div class="h-screen flex flex-col md:flex-row md:overflow-hidden bg-gray-900" id="posApp">

        <!-- LEFT PANEL: Products (Flexible width) -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 bg-gray-900 md:border-r border-gray-800 relative">

            <!-- Header: Search & Categories -->
            <div class="flex-none p-4 md:p-6 space-y-4 bg-gray-900 z-10">
                <!-- Alert Session -->
                @if(!$activeSession)
                    <div
                        class="bg-red-500/10 border border-red-500/50 text-red-200 px-4 py-3 rounded-xl flex items-center shadow-lg backdrop-blur-sm">
                        <svg class="w-6 h-6 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <strong class="font-bold">Sesi Tertutup!</strong> Silakan buka shift terlebih dahulu untuk
                            bertransaksi.
                        </div>
                    </div>
                @endif

                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search -->
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="searchQuery" @input="filterProducts"
                            placeholder="Cari menu, kode, kategori..."
                            class="w-full pl-11 pr-4 py-3.5 bg-gray-800 text-white placeholder-gray-500 rounded-xl border border-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition shadow-sm">
                    </div>
                </div>

                <!-- Category Filter (Pills) -->
                <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide py-1">
                    <button @click="selectedCategory = null" :class="selectedCategory === null 
                                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 ring-2 ring-indigo-600 ring-offset-2 ring-offset-gray-900' 
                                : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-gray-200 border border-gray-700'"
                        class="px-5 py-2.5 rounded-full whitespace-nowrap transition font-medium text-sm flex-shrink-0">
                        Semua Menu
                    </button>
                    @foreach($categories as $category)
                        <button @click="selectedCategory = {{ $category->id }}" :class="selectedCategory === {{ $category->id }} 
                                    ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 ring-2 ring-indigo-600 ring-offset-2 ring-offset-gray-900' 
                                    : 'bg-gray-800 text-gray-400 hover:bg-gray-700 hover:text-gray-200 border border-gray-700'"
                            class="px-5 py-2.5 rounded-full whitespace-nowrap transition font-medium text-sm flex-shrink-0">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Grid Content -->
            <div class="flex-1 overflow-y-auto p-4 md:p-6 pt-0 custom-scrollbar max-h-[50vh] md:max-h-none">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <template v-for="product in filteredProducts" :key="product.id">
                        <button @click="addToCart(product)"
                            class="group relative flex flex-col bg-gray-800 hover:bg-gray-750 rounded-2xl p-3 text-left transition-all duration-200 border border-gray-700 hover:border-indigo-500/50 hover:shadow-xl hover:shadow-indigo-500/10 hover:-translate-y-1 overflow-hidden">

                            <!-- Image / Placeholder -->
                            <div
                                class="aspect-[4/3] bg-gray-700/50 rounded-xl mb-3 flex items-center justify-center relative overflow-hidden group-hover:bg-gray-700 transition">
                                <!-- Pattern bg -->
                                <div
                                    class="absolute inset-0 opacity-10 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white to-transparent">
                                </div>

                                <!-- Icon -->
                                <svg class="w-10 h-10 text-gray-600 group-hover:text-indigo-400 transition transform group-hover:scale-110 duration-300"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>

                                <!-- Price Tag Overlay -->
                                <div
                                    class="absolute top-2 right-2 bg-gray-900/90 backdrop-blur text-indigo-400 text-xs font-bold px-2 py-1 rounded-lg border border-gray-700 shadow-sm">
                                    Rp @{{ formatNumber(product.selling_price) }}
                                </div>
                            </div>

                            <!-- Info -->
                            <div class="flex-1 flex flex-col">
                                <h4 class="text-sm font-semibold text-gray-100 group-hover:text-indigo-300 transition line-clamp-2 leading-tight mb-1"
                                    v-text="product.name"></h4>
                                <p class="text-[10px] text-gray-500 font-mono" v-text="product.sku"></p>
                            </div>
                        </button>
                    </template>

                    <!-- Empty State -->
                    <div v-if="filteredProducts.length === 0" class="col-span-full py-20 text-center">
                        <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-300">Menu tidak ditemukan</h3>
                        <p class="text-gray-500 mt-2">Coba kata kunci lain atau kategori berbeda</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Cart & Checkout (Fixed width on desktop) -->
        <div
            class="w-full md:w-[400px] xl:w-[450px] bg-gray-900 flex flex-col md:border-l border-gray-800 shadow-2xl z-20 min-h-0">

            <!-- Cart Header -->
            <div class="flex-none p-5 border-b border-gray-800 bg-gray-900 shadow-sm z-20">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-indigo-600/20 p-2 rounded-lg text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-white">Keranjang</h2>
                    </div>
                    <div class="text-xs font-mono text-gray-500 bg-gray-800 px-2 py-1 rounded border border-gray-700">
                        @{{ cart.length }} Item
                    </div>
                </div>

                <!-- Customer Selector -->
                <div class="relative">
                    <select v-model="selectedCustomerId"
                        class="w-full pl-3 pr-8 py-2.5 bg-gray-800 text-sm text-white rounded-lg border border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 cursor-pointer appearance-none">
                        <option value="">👤 Tamu Umum (Walk-in)</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            ⭐ @{{ customer.name }} (@{{ customer.phone || 'No Phone' }})
                        </option>
                    </select>
                    <div class="absolute right-3 top-2.5 pointer-events-none text-gray-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div v-if="selectedCustomer" class="mt-2 flex items-center justify-between text-xs px-1">
                    <span class="text-indigo-400 font-medium">✨ @{{ selectedCustomer.member_tier || 'Member' }}</span>
                    <span class="text-gray-400">Poin: <span class="text-white">@{{
                            formatNumber(selectedCustomer.loyalty_points || 0) }}</span></span>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar bg-gray-900/50 max-h-[30vh] md:max-h-none">
                <template v-if="cart.length > 0">
                    <div v-for="(item, index) in cart" :key="index"
                        class="group bg-gray-800 rounded-xl p-3 border border-gray-700/50 hover:border-gray-600 transition flex gap-3 relative overflow-hidden">

                        <!-- Qty Controls (Vertical) -->
                        <div class="flex flex-col items-center justify-between bg-gray-900 rounded-lg p-1 w-8">
                            <button @click="increaseQty(index)"
                                class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 rounded transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <span class="text-sm font-bold text-white select-none">@{{ item.quantity }}</span>
                            <button @click="decreaseQty(index)"
                                class="w-6 h-6 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 rounded transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                        </div>

                        <!-- Item Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="text-sm font-medium text-white truncate pr-4" v-text="item.name"></h4>
                                <span class="text-sm font-bold text-white whitespace-nowrap">@{{ formatNumber(item.subtotal)
                                    }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mb-2">@ @{{ formatNumber(item.unit_price) }}</p>

                            <!-- Actions & Notes -->
                            <div class="flex items-center gap-3">
                                <button @click="editItemNotes(index)"
                                    class="text-[10px] flex items-center gap-1 px-2 py-1 rounded bg-gray-700 hover:bg-gray-600 text-gray-300 transition"
                                    :class="{'text-amber-400 bg-amber-400/10': item.notes}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    @{{ item.notes ? 'Edit Catatan' : 'Catatan' }}
                                </button>
                                <button @click="removeFromCart(index)"
                                    class="text-xs text-red-500 hover:text-red-400 flex items-center gap-1 px-2 py-1 rounded hover:bg-red-500/10 transition ml-auto">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                            <div v-if="item.notes"
                                class="mt-2 text-xs text-amber-500 italic bg-amber-500/10 p-1.5 rounded border border-amber-500/20">
                                "@{{ item.notes }}"
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty Cart State -->
                <div v-else class="h-full flex flex-col items-center justify-center text-center opacity-50 py-10">
                    <div class="w-20 h-20 bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <p class="text-gray-400 text-sm">Keranjang Kosong</p>
                    <p class="text-gray-600 text-xs mt-1">Belum ada item dipilih</p>
                </div>
            </div>

            <!-- Checkout Section -->
            <div
                class="flex-none bg-gray-900 border-t border-gray-800 p-5 shadow-[0_-4px_20px_rgba(0,0,0,0.4)] z-30 sticky bottom-0 md:relative">
                <!-- Summary -->
                <div class="space-y-2 mb-4 text-sm">
                    <div class="flex justify-between text-gray-400">
                        <span>Subtotal</span>
                        <span class="font-mono">Rp @{{ formatNumber(subtotal) }}</span>
                    </div>
                    <!-- Tax & Fees -->
                    <div v-if="serviceChargeRate > 0 || taxRate > 0" class="py-2 border-y border-gray-800 space-y-1">
                        <div v-if="serviceChargeRate > 0" class="flex justify-between text-gray-500 text-xs">
                            <span>Service Charge (@{{ serviceChargeRate }}%)</span>
                            <span class="font-mono">Rp @{{ formatNumber(serviceChargeAmount) }}</span>
                        </div>
                        <div v-if="taxRate > 0" class="flex justify-between text-gray-500 text-xs">
                            <span>Pajak (@{{ formatNumber(taxRate) }}%)</span>
                            <span class="font-mono">Rp @{{ formatNumber(taxAmount) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end pt-2">
                        <span class="text-gray-300 font-medium">Total Tagihan</span>
                        <span class="text-3xl font-bold text-white tracking-tight">Rp @{{ formatNumber(totalAmount)
                            }}</span>
                    </div>
                </div>

                <!-- Payment Method & Action -->
                <div class="grid grid-cols-[1fr_auto] gap-3">
                    <div class="relative">
                        <select v-model="selectedPaymentMethod"
                            class="w-full pl-3 pr-8 py-3.5 bg-gray-800 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 appearance-none font-medium">
                            <option value="">Pilih Pembayaran...</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">💳 {{ $method->name }}</option>
                            @endforeach
                        </select>
                        <div class="absolute right-3 top-4 pointer-events-none text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>

                    <button @click="processPayment" :disabled="cart.length === 0 || !selectedPaymentMethod || isProcessing"
                        :class="cart.length === 0 || !selectedPaymentMethod || isProcessing 
                                ? 'bg-gray-800 text-gray-600 cursor-not-allowed border border-gray-700' 
                                : 'bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/30'"
                        class="px-8 py-3.5 rounded-xl font-bold transition transform active:scale-95 flex items-center justify-center min-w-[120px]">
                        <span v-if="!isProcessing">BAYAR</span>
                        <svg v-else class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Success Modal -->
        <div v-if="showSuccessModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
            style="display: none;" :style="{ display: showSuccessModal ? 'flex' : 'none' }">
            <div
                class="bg-gray-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center border border-gray-700 animate-[bounceIn_0.3s_ease-out]">
                <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-white mb-2">Transaksi Berhasil!</h3>
                <p class="text-gray-400 mb-6 font-mono">@{{ lastSale ? lastSale.invoice_number : '' }}</p>

                <div class="bg-gray-900/50 rounded-xl p-4 mb-6 border border-gray-700/50">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-gray-400 text-sm">Total Tagihan</span>
                        <span class="text-xl font-bold text-white">Rp @{{ lastSale ? formatNumber(lastSale.total_amount) : 0
                            }}</span>
                    </div>
                </div>

                <div class="space-y-3">
                    <button @click="printReceipt"
                        class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold flex items-center justify-center gap-2 transition shadow-lg shadow-indigo-600/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        CETAK STRUK
                    </button>
                    <button @click="closeSuccessModal"
                        class="w-full py-3.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-semibold transition">
                        Transaksi Baru
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        // Logic Vue.js tetap sama, hanya structure HTML yang berubah total
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    products: @json($categories->flatMap->products),
                    customers: @json($customers),
                    cart: [],
                    searchQuery: '',
                    selectedCategory: null,
                    filteredProducts: [],
                    selectedPaymentMethod: '',
                    showSuccessModal: false,
                    lastSale: null,
                    discountType: 'none',
                    discountValue: 0,
                    isProcessing: false,
                    outletId: {{ $activeSession->outlet_id ?? 'null' }},
                    cashSessionId: {{ $activeSession->id ?? 'null' }},
                    selectedCustomerId: '',
                    customerName: '',
                    orderNotes: '',
                    taxRate: {{ $outlet->tax_rate ?? 10 }},
                    serviceChargeRate: {{ $outlet->service_charge_rate ?? 0 }},
                }
            },
            computed: {
                hasAnyNotes() {
                    if (this.orderNotes && this.orderNotes.trim().length > 0) {
                        return true;
                    }
                    return this.cart.some(item => item.notes && item.notes.trim().length > 0);
                },
                selectedCustomer() {
                    if (!this.selectedCustomerId) {
                        return null;
                    }
                    const id = Number(this.selectedCustomerId);
                    return this.customers.find(c => c.id === id) || null;
                },
                subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
                },
                discountAmount() {
                    if (this.discountType === 'percentage') {
                        return this.subtotal * (this.discountValue / 100);
                    } else if (this.discountType === 'fixed') {
                        return this.discountValue;
                    }
                    return 0;
                },
                taxBase() {
                    return this.subtotal - this.discountAmount;
                },
                serviceChargeAmount() {
                    return this.taxBase * (this.serviceChargeRate / 100);
                },
                taxableAmount() {
                    return this.taxBase + this.serviceChargeAmount;
                },
                taxAmount() {
                    return this.taxableAmount * (this.taxRate / 100);
                },
                totalAmount() {
                    return this.taxBase + this.serviceChargeAmount + this.taxAmount;
                }
            },
            mounted() {
                this.filteredProducts = this.products;
            },
            methods: {
                filterProducts() {
                    let filtered = this.products;

                    // Filter by category
                    if (this.selectedCategory !== null) {
                        filtered = filtered.filter(p => p.category_id === this.selectedCategory);
                    }

                    // Filter by search
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(p =>
                            p.name.toLowerCase().includes(query) ||
                            p.sku.toLowerCase().includes(query)
                        );
                    }

                    this.filteredProducts = filtered;
                },
                addToCart(product) {
                    const existingItem = this.cart.find(item => item.product_id === product.id);
                    const price = Number(product.selling_price);

                    if (existingItem) {
                        existingItem.quantity++;
                        existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            name: product.name,
                            sku: product.sku,
                            quantity: 1,
                            unit_price: price,
                            discount_amount: 0,
                            subtotal: price,
                            notes: ''
                        });
                    }
                },
                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },
                increaseQty(index) {
                    this.cart[index].quantity++;
                    this.updateItemTotal(index);
                },
                decreaseQty(index) {
                    if (this.cart[index].quantity > 1) {
                        this.cart[index].quantity--;
                        this.updateItemTotal(index);
                    }
                },
                updateItemTotal(index) {
                    const item = this.cart[index];
                    item.subtotal = (item.quantity * item.unit_price) - item.discount_amount;
                },
                editItemNotes(index) {
                    const current = this.cart[index].notes || '';
                    const updated = prompt('Catatan untuk item ini:', current);
                    if (updated !== null) {
                        this.cart[index].notes = updated.substring(0, 255);
                    }
                },
                formatNumber(number) {
                    if (isNaN(number)) return '0';
                    return new Intl.NumberFormat('id-ID').format(number);
                },
                async processPayment() {
                    if (!this.outletId || !this.cashSessionId) {
                        alert('Tidak ada sesi kasir yang aktif. Silakan buka shift terlebih dahulu.');
                        return;
                    }

                    if (!confirm('Proses pembayaran?')) {
                        return;
                    }

                    this.isProcessing = true;

                    const data = {
                        outlet_id: this.outletId,
                        cash_session_id: this.cashSessionId,
                        customer_id: this.selectedCustomerId ? Number(this.selectedCustomerId) : null,
                        customer_name: this.customerName || (this.selectedCustomer ? this.selectedCustomer.name : null),
                        notes: this.orderNotes || null,
                        discount_type: this.discountType,
                        discount_value: this.discountValue,
                        items: this.cart.map(item => ({
                            product_id: item.product_id,
                            quantity: item.quantity,
                            unit_price: item.unit_price,
                            discount_amount: item.discount_amount || 0
                        })),
                        payment_method_id: this.selectedPaymentMethod,
                        payment_amount: this.totalAmount,
                        payment_reference: null
                    };

                    try {
                        const response = await fetch('{{ route('pos.sales.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.lastSale = result.data;
                            this.showSuccessModal = true;
                            this.lastSale.change = this.totalAmount <= this.cart.reduce((sum, i) => sum + i.subtotal, 0) ? 0 : 0; // Simple placeholder, actual change calc if needed

                            this.cart = [];
                            this.selectedPaymentMethod = '';
                            this.searchQuery = '';
                            this.filteredProducts = this.products;
                        } else {
                            alert('Gagal: ' + result.message + '\n' + (result.error || ''));
                        }
                    } catch (error) {
                        alert('Terjadi kesalahan: ' + error.message);
                    } finally {
                        this.isProcessing = false;
                    }
                },
                printReceipt() {
                    if (this.lastSale && this.lastSale.sale_id) {
                        const width = 400;
                        const height = 600;
                        const left = (screen.width - width) / 2;
                        const top = (screen.height - height) / 2;
                        window.open(
                            `/pos/sales/${this.lastSale.sale_id}/print`,
                            '_blank',
                            `width=${width},height=${height},top=${top},left=${left}`
                        );
                    }
                },
                closeSuccessModal() {
                    this.showSuccessModal = false;
                    this.lastSale = null;
                }
            },
            watch: {
                selectedCategory() {
                    this.filterProducts();
                }
            }
        }).mount('#posApp');
    </script>

    <!-- Success Modal -->



    <style>
        /* Custom Scrollbar for better consistency */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
@endsection