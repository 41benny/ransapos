@extends('layouts.pos_v2')

@section('title', 'Transaksi Penjualan')
@section('page-title', 'MorestoPOS')

@section('content')
    <div class="h-full flex flex-col md:flex-row overflow-hidden bg-gray-50 font-sans text-gray-800" id="posApp">

        <!-- LEFT PANEL: Products & Header -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">

            <!-- Top Bar -->
            <div class="flex-none px-6 py-4 bg-white border-b border-gray-200 flex items-center justify-between z-10">
                <!-- Brand / Logo Area -->
                <div class="flex items-center gap-3">
                    <div class="bg-red-600 text-white p-2 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Moresto<span class="text-red-600">POS</span>
                    </h1>
                </div>

                <!-- Search Bar (Centered) -->
                <div class="flex-1 max-w-xl mx-8">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-red-500 transition" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="searchQuery" @input="filterProducts" placeholder="Search menu items..."
                            class="w-full pl-11 pr-4 py-3 bg-gray-100 border-none text-gray-700 placeholder-gray-400 rounded-xl focus:ring-2 focus:ring-red-500/20 focus:bg-white transition shadow-sm">
                    </div>
                </div>

                <!-- User Profile / Server Info -->
                <div class="flex items-center gap-3">
                    <div class="text-right hidden sm:block">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Server</p>
                        <p class="text-sm font-semibold text-gray-700">{{ auth()->user()->name }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 p-0.5 border-2 border-white shadow-sm overflow-hidden">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=EF4444&color=fff"
                            alt="User" class="w-full h-full rounded-full object-cover">
                    </div>
                </div>
            </div>

            <!-- Categories & Filters -->
            <div
                class="flex-none px-6 py-4 overflow-x-auto scrollbar-hide flex gap-3 bg-gray-50/50 backdrop-blur-sm sticky top-0 z-10">
                <button @click="selectedCategory = null" :class="selectedCategory === null 
                                        ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' 
                                        : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 shadow-sm'"
                    class="px-6 py-2.5 rounded-full whitespace-nowrap transition font-semibold text-sm flex-shrink-0">
                    All Items
                </button>
                @foreach($categories as $category)
                    <button @click="selectedCategory = {{ $category->id }}"
                        :class="Number(selectedCategory) === {{ $category->id }} 
                                                            ? 'bg-red-600 text-white shadow-lg shadow-red-600/30' 
                                                            : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200 shadow-sm'"
                        class="px-6 py-2.5 rounded-full whitespace-nowrap transition font-semibold text-sm flex-shrink-0">
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto px-6 pb-20 custom-scrollbar">

                <!-- Section Title -->
                <div class="flex justify-between items-end mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        @{{ selectedCategory ? (categories.find(c => c.id == selectedCategory)?.name || 'Menu') : 'All
                        Items' }}
                    </h2>
                    <span class="text-sm text-gray-500 font-medium">showing @{{ filteredProducts.length }} items</span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    <template v-for="product in filteredProducts" :key="product.id">
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-transparent hover:border-red-500/30 hover:shadow-xl hover:shadow-red-500/5 transition-all duration-300 group flex flex-col h-full cursor-pointer"
                            @click="addToCart(product)">

                            <!-- Image -->
                            <div class="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative bg-gray-50">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                                <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>

                                <!-- Tags (Optional) -->
                                <!-- <span class="absolute top-2 left-2 bg-white/90 backdrop-blur px-2 py-1 rounded-lg text-[10px] font-bold text-gray-800 shadow-sm uppercase tracking-wide">
                                                    Best Seller
                                                </span> -->
                            </div>

                            <!-- Content -->
                            <div class="flex-1 flex flex-col">
                                <h3
                                    class="text-base font-bold text-gray-800 group-hover:text-red-600 transition mb-1 leading-snug">
                                    @{{ product.name }}</h3>
                                <!-- SKU/Desc -->
                                <p class="text-xs text-gray-400 mb-3 line-clamp-2">@{{ product.description || product.sku }}
                                </p>

                                <div class="mt-auto flex items-center justify-between">
                                    <span class="text-lg font-bold text-red-600">
                                        <span class="text-xs text-red-400 align-top mr-0.5">Rp</span>@{{
                                        formatNumber(getProductPrice(product)) }}
                                    </span>
                                    <button
                                        class="w-8 h-8 rounded-full bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-600 hover:text-white transition shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <!-- Empty State -->
                    <div v-if="filteredProducts.length === 0" class="col-span-full py-20 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-600">No items found</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Current Order -->
        <div
            class="w-full md:w-[400px] xl:w-[420px] bg-white border-l border-gray-200 flex flex-col h-full shadow-2xl z-20">
            <!-- Order Header -->
            <div class="flex-none p-6 border-b border-gray-100">
                <div class="flex justify-between items-start mb-1">
                    <h2 class="text-2xl font-bold text-gray-900">Current Order</h2>
                    <button @click="cart = []" v-if="cart.length > 0"
                        class="text-red-500 p-2 hover:bg-red-50 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center text-sm text-gray-400 gap-2">
                    <span
                        class="bg-gray-100 px-2 py-0.5 rounded text-gray-500 font-mono text-xs">#{{ $activeSession ? $activeSession->id : '---' }}</span>
                    <span>•</span>
                    <span>{{ date('d M Y, H:i') }}</span>
                </div>

                <!-- Customer & Type Selector -->
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <select v-model="selectedCustomerId"
                        class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5">
                        <option value="">Guest (Walk-in)</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            @{{ customer.name }}
                        </option>
                    </select>
                    <select v-model="salesType"
                        class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5">
                        <option v-for="(label, key) in priceLevels" :key="key" :value="key">
                            @{{ label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto px-6 py-4 custom-scrollbar space-y-4">
                <template v-if="cart.length > 0">
                    <!-- Item Row -->
                    <div v-for="(item, index) in cart" :key="index" class="flex gap-4 group">
                        <!-- Thumb -->
                        <div class="w-16 h-16 rounded-xl bg-gray-100 flex-none overflow-hidden relative">
                            <!-- We need product image here, so we find it from products list -->
                            <img :src="getProductImage(item.product_id)" class="w-full h-full object-cover">
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                            <div class="flex justify-between items-start">
                                <h4 class="text-sm font-bold text-gray-800 line-clamp-2 leading-tight">@{{ item.name }}</h4>
                                <span class="text-sm font-bold text-gray-900 ml-2">@{{ formatNumber(item.subtotal) }}</span>
                            </div>

                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-400">@ @{{ formatNumber(item.unit_price) }}</p>

                                <!-- Qty Control -->
                                <div class="flex items-center gap-3 bg-gray-100 rounded-lg px-2 py-1">
                                    <button @click="decreaseQty(index)"
                                        class="w-5 h-5 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-red-600 transition text-xs font-bold disabled:opacity-50">-</button>
                                    <span class="text-xs font-bold w-4 text-center">@{{ item.quantity }}</span>
                                    <button @click="increaseQty(index)"
                                        class="w-5 h-5 flex items-center justify-center bg-red-600 rounded shadow-sm text-white hover:bg-red-700 transition text-xs font-bold">+</button>
                                </div>
                            </div>

                            <!-- Notes Button (Small) -->
                            <div v-if="item.notes"
                                class="mt-1 text-[10px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100 inline-flex self-start">
                                📝 @{{ item.notes }}
                            </div>
                            <button v-else @click="editItemNotes(index)"
                                class="mt-1 text-[10px] text-gray-400 hover:text-gray-600 self-start flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Add Note
                            </button>
                        </div>
                    </div>
                </template>
                <div v-else class="h-full flex flex-col items-center justify-center text-gray-300">
                    <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">Keranjang masih kosong</p>
                </div>
            </div>

            <!-- Checkout Section -->
            <div class="flex-none p-6 border-t border-gray-100 bg-gray-50/50">
                <!-- Order Note Input -->
                <div class="mb-4">
                    <input type="text" v-model="orderNotes" placeholder="Add order note..."
                        class="w-full bg-transparent border-b border-gray-300 focus:border-red-500 py-2 text-sm focus:outline-none placeholder-gray-400 transition">
                </div>

                <!-- Totals -->
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(subtotal) }}</span>
                    </div>
                    <div v-if="taxRate > 0" class="flex justify-between text-sm text-gray-500">
                        <span>Tax (@{{ taxRate }}%)</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(taxAmount) }}</span>
                    </div>

                    <div class="flex justify-between items-end pt-3 border-t border-dashed border-gray-200">
                        <span class="text-base font-bold text-gray-800">Total</span>
                        <span class="text-2xl font-black text-red-600 tracking-tight">Rp @{{ formatNumber(totalAmount)
                            }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Payment Method -->
                    <button class="col-span-2 relative hidden">
                        <!-- Hidden select wrapper if needed for functionality -->
                        <select v-model="selectedPaymentMethod" class="absolute inset-0 opacity-0 cursor-pointer z-10">
                            <option value="">Select Payment</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        <div
                            class="w-full py-3 border border-red-200 text-red-600 rounded-xl font-bold hover:bg-red-50 transition flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                                </path>
                            </svg>
                            <span
                                v-text="selectedPaymentMethod ? (paymentMethods.find(m => m.id == selectedPaymentMethod)?.name || 'Pilih Pembayaran') : 'Pilih Pembayaran'"></span>
                        </div>
                    </button>

                    <div class="col-span-2 grid grid-cols-[1fr_2fr] gap-3">
                        <div class="relative">
                            <select v-model="selectedPaymentMethod"
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <option value="">Method...</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                                @endforeach
                            </select>
                            <button
                                class="w-full h-full border border-red-200 text-red-600 rounded-xl font-bold text-sm hover:bg-red-50 transition flex flex-col items-center justify-center p-1">
                                <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="text-[10px] leading-none uppercase tracking-wide truncate max-w-full px-1"
                                    v-text="selectedPaymentMethod ? (paymentMethods.find(m => m.id == selectedPaymentMethod)?.name || 'Bayar') : 'Metode'"></span>
                            </button>
                        </div>

                        <button @click="processPayment"
                            :disabled="cart.length === 0 || !selectedPaymentMethod || isProcessing" :class="cart.length === 0 || !selectedPaymentMethod || isProcessing 
                                                ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                : 'bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/30'"
                            class="py-4 rounded-xl font-bold text-lg transition flex items-center justify-center gap-2">
                            <span v-if="!isProcessing">Pay Now</span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing
                            </span>
                            <svg v-if="!isProcessing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal (Re-styled) -->
        <div v-if="showSuccessModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            style="display: none;" :style="{ display: showSuccessModal ? 'flex' : 'none' }">
            <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-8 text-center animate-[bounceIn_0.3s_ease-out]">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 mb-2">Payment Successful!</h3>
                <p class="text-gray-500 mb-8 font-mono tracking-widest text-sm uppercase">@{{ lastSale ?
                    lastSale.invoice_number : '' }}</p>

                <div class="space-y-3">
                    <button @click="printReceipt"
                        class="w-full py-4 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold shadow-lg flex items-center justify-center gap-2 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2-2v4h10z">
                            </path>
                        </svg>
                        Print Receipt
                    </button>
                    <button @click="closeSuccessModal"
                        class="w-full py-4 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-xl font-bold transition">
                        New Order
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- VUE JS 3 -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    categories: @json($categories),
                    // Adding dummy products for categories if needed, relying on server data
                    products: @json($categories->flatMap->products),
                    priceLevels: @json($priceLevels),
                    customers: @json($customers),
                    paymentMethods: @json($paymentMethods),

                    cart: [],
                    searchQuery: '',
                    selectedCategory: null,
                    filteredProducts: [],

                    salesType: 'regular',
                    selectedPaymentMethod: '',
                    selectedCustomerId: '',

                    orderNotes: '',

                    showSuccessModal: false,
                    lastSale: null,

                    discountType: 'none',
                    discountValue: 0,
                    isProcessing: false,

                    outletId: {{ $activeSession->outlet_id ?? 'null' }},
                    cashSessionId: {{ $activeSession->id ?? 'null' }},

                    taxRate: {{ $outlet->tax_rate ?? 10 }},
                    serviceChargeRate: {{ $outlet->service_charge_rate ?? 0 }},
                }
            },
            computed: {
                selectedCustomer() {
                    if (!this.selectedCustomerId) return null;
                    return this.customers.find(c => c.id === Number(this.selectedCustomerId));
                },
                subtotal() {
                    return this.cart.reduce((sum, item) => sum + item.subtotal, 0);
                },
                taxBase() {
                    // Logic: Subtotal - Discount
                    // Simple implementation for now
                    return this.subtotal;
                },
                taxAmount() {
                    return this.taxBase * (this.taxRate / 100);
                },
                totalAmount() {
                    return this.taxBase + this.taxAmount;
                }
            },
            mounted() {
                this.filteredProducts = this.products;
                // Pre-select first category if available? No, user might want 'All'
            },
            methods: {
                filterProducts() {
                    let filtered = this.products;
                    if (this.selectedCategory !== null) {
                        filtered = filtered.filter(p => Number(p.category_id) === Number(this.selectedCategory));
                    }
                    if (this.searchQuery) {
                        const query = this.searchQuery.toLowerCase();
                        filtered = filtered.filter(p => p.name.toLowerCase().includes(query) || p.sku.toLowerCase().includes(query));
                    }
                    this.filteredProducts = filtered;
                },
                getProductPrice(product, forcedSalesType = null) {
                    if (!product) return 0;

                    const level = forcedSalesType || this.salesType || 'regular';
                    // Ensure price_levels handles the object/array structure from PHP
                    const priceMap = product.price_levels || {};

                    // Priority: Specific Level -> Regular -> Default Selling Price
                    if (priceMap[level] !== undefined && priceMap[level] !== null) {
                        return Number(priceMap[level]);
                    }
                    if (priceMap['regular'] !== undefined && priceMap['regular'] !== null) {
                        return Number(priceMap['regular']);
                    }
                    return Number(product.selling_price || 0);
                },
                updateCartPricesBySalesType() {
                    this.cart = this.cart.map(item => {
                        const product = this.products.find(p => p.id === item.product_id);
                        if (!product) return item;

                        const newPrice = this.getProductPrice(product);
                        return {
                            ...item,
                            unit_price: newPrice,
                            subtotal: item.quantity * newPrice
                        };
                    });
                },
                addToCart(product) {
                    const existing = this.cart.find(i => i.product_id === product.id);
                    const price = this.getProductPrice(product);

                    if (existing) {
                        existing.quantity++;
                        existing.subtotal = existing.quantity * price;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            name: product.name,
                            unit_price: price,
                            quantity: 1,
                            subtotal: price,
                            notes: ''
                        });
                    }
                },
                getProductImage(productId) {
                    const product = this.products.find(p => p.id === productId);
                    return product ? (product.image_url || 'https://via.placeholder.com/150') : 'https://via.placeholder.com/150';
                },
                increaseQty(index) {
                    const item = this.cart[index];
                    item.quantity++;
                    item.subtotal = item.quantity * item.unit_price;
                },
                decreaseQty(index) {
                    const item = this.cart[index];
                    if (item.quantity > 1) {
                        item.quantity--;
                        item.subtotal = item.quantity * item.unit_price;
                    } else {
                        this.cart.splice(index, 1);
                    }
                },
                editItemNotes(index) {
                    const current = this.cart[index].notes || '';
                    const updated = prompt('Add note:', current);
                    if (updated !== null) {
                        this.cart[index].notes = updated;
                    }
                },
                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                },
                async processPayment() {
                    if (!this.outletId || !this.cashSessionId) {
                        alert('No active cash session.');
                        return;
                    }

                    this.isProcessing = true;

                    // Simple Payload
                    const data = {
                        outlet_id: this.outletId,
                        cash_session_id: this.cashSessionId,
                        customer_id: this.selectedCustomerId,
                        notes: this.orderNotes,
                        sales_type: this.salesType,
                        items: this.cart.map(i => ({
                            product_id: i.product_id,
                            quantity: i.quantity,
                            unit_price: i.unit_price
                        })),
                        payment_method_id: this.selectedPaymentMethod,
                        payment_amount: this.totalAmount
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
                            this.cart = [];
                            this.selectedPaymentMethod = '';
                        } else {
                            alert('Error: ' + result.message);
                        }
                    } catch (e) {
                        alert('System Error: ' + e.message);
                    } finally {
                        this.isProcessing = false;
                    }
                },
                printReceipt() {
                    if (this.lastSale) {
                        window.open(`/pos/sales/${this.lastSale.sale_id}/print`, '_blank', 'width=400,height=600');
                    }
                },
                closeSuccessModal() {
                    this.showSuccessModal = false;
                }
            },
            watch: {
                selectedCategory() { this.filterProducts(); },
                salesType() { this.updateCartPricesBySalesType(); }
            }
        }).mount('#posApp');
    </script>
@endsection