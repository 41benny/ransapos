@extends('layouts.pos_v2')

@section('title', 'Transaksi Penjualan')
@section('page-title', 'MorestoPOS')

@section('content')
    <div class="h-full flex flex-col md:flex-row overflow-hidden bg-background-light font-display text-slate-900"
        id="posApp">

        <!-- LEFT PANEL: Products & Header -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative">

            <!-- Top Bar -->
            <div
                class="flex-none px-6 py-4 bg-surface-light border-b border-gray-200 dark:border-red-900/30 flex items-center justify-between z-10">
                <!-- Dashboard Button -->
                <div class="flex items-center gap-3">
                    <a href="{{ route('pos.dashboard') }}" title="Dashboard"
                        class="bg-primary text-white w-10 h-10 rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-500/30 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </a>

                    <button id="posFullscreenBtn" type="button" title="Fullscreen"
                        class="bg-surface-light text-gray-700 w-10 h-10 rounded-xl hover:bg-gray-100 transition shadow-sm border border-gray-200 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3M8 21H5a2 2 0 01-2-2v-3m18 0v3a2 2 0 01-2 2h-3" />
                        </svg>
                    </button>
                </div>

                <!-- Search Bar (Centered) -->
                <div class="flex-1 max-w-xl mx-8">
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-primary transition" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="searchQuery" @input="filterProducts" placeholder="Search menu items..."
                            class="w-full pl-11 pr-4 py-3 bg-gray-100 dark:bg-red-950/20 border-none text-gray-700 placeholder-gray-400 rounded-xl focus:ring-2 focus:ring-primary/50 focus:bg-white transition shadow-sm">
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
                class="flex-none px-6 py-4 overflow-x-auto scrollbar-hide flex gap-3 bg-background-light/50 backdrop-blur-sm sticky top-0 z-10">
                <button @click="selectedCategory = null"
                    :class="selectedCategory === null 
                                                                                                                                                    ? 'bg-primary text-white shadow-lg shadow-red-600/30' 
                                                                                                                                                    : 'bg-surface-light text-gray-600 hover:bg-gray-100 border border-gray-200 shadow-sm'"
                    class="px-6 py-2.5 rounded-full whitespace-nowrap transition font-semibold text-sm flex-shrink-0">
                    All Items
                </button>
                @foreach($categories as $category)
                    <button @click="selectedCategory = {{ $category['id'] }}"
                        :class="Number(selectedCategory) === {{ $category['id'] }} 
                                                                                                                                                                                                                                                                                     ? 'bg-primary text-white shadow-lg shadow-red-600/30' 
                                                                                                                                                                                                                                                                                     : 'bg-surface-light text-gray-600 hover:bg-gray-100 border border-gray-200 shadow-sm'"
                        class="px-6 py-2.5 rounded-full whitespace-nowrap transition font-semibold text-sm flex-shrink-0">
                        {{ $category['name'] }}
                    </button>
                @endforeach
            </div>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto px-6 pb-20 custom-scrollbar">

                <!-- Section Title -->
                <div class="flex justify-between items-end mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">
                        @{{ currentCategoryName }}
                    </h2>

                    <div class="flex items-center gap-4">
                        <!-- View Toggles -->
                        <div class="bg-gray-100 p-1 rounded-lg flex gap-1">
                            <button @click="gridSize = 'small'"
                                :class="gridSize === 'small' ? 'bg-white text-primary shadow-sm ring-1 ring-black/5' : 'text-gray-400 hover:text-gray-600'"
                                class="p-1.5 rounded-md transition" title="Small Grid">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                            <button @click="gridSize = 'medium'"
                                :class="gridSize === 'medium' ? 'bg-white text-primary shadow-sm ring-1 ring-black/5' : 'text-gray-400 hover:text-gray-600'"
                                class="p-1.5 rounded-md transition" title="Medium Grid">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
                                </svg>
                            </button>
                            <button @click="gridSize = 'large'"
                                :class="gridSize === 'large' ? 'bg-white text-primary shadow-sm ring-1 ring-black/5' : 'text-gray-400 hover:text-gray-600'"
                                class="p-1.5 rounded-md transition" title="Large Grid">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <rect x="4" y="4" width="7" height="7" rx="1" stroke-width="2"></rect>
                                    <rect x="13" y="4" width="7" height="7" rx="1" stroke-width="2"></rect>
                                    <rect x="4" y="13" width="7" height="7" rx="1" stroke-width="2"></rect>
                                    <rect x="13" y="13" width="7" height="7" rx="1" stroke-width="2"></rect>
                                </svg>
                            </button>
                        </div>
                        <span class="text-sm text-gray-500 font-medium">@{{ filteredProducts.length }} items</span>
                    </div>
                </div>

                <div :class="gridClasses">
                    <template v-for="product in filteredProducts" :key="product.id">
                        <div :class="['bg-surface-light rounded-2xl shadow-sm border border-transparent hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 group flex flex-col h-full cursor-pointer', gridSize === 'small' ? 'p-3' : 'p-4']"
                            @click="addToCart(product)">

                            <!-- Image -->
                            <div
                                :class="['rounded-xl overflow-hidden relative bg-gray-50', gridSize === 'small' ? 'aspect-square mb-2' : 'aspect-[4/3] mb-4']">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name" loading="lazy"
                                    decoding="async" fetchpriority="low"
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
                                    :class="['font-bold text-gray-800 group-hover:text-red-600 transition mb-1 leading-snug', gridSize === 'small' ? 'text-sm' : 'text-base']">
                                    @{{ product.name }}</h3>
                                <!-- SKU/Desc -->
                                <p v-if="gridSize !== 'small'" class="text-xs text-gray-400 mb-3 line-clamp-2">@{{
                                    product.description || product.sku }}
                                </p>

                                <div class="mt-auto flex items-center justify-between">
                                    <span :class="['font-bold text-primary', gridSize === 'small' ? 'text-sm' : 'text-lg']">
                                        <span class="text-xs text-primary/70 align-top mr-0.5">Rp</span>@{{
                                        formatNumber(getProductPrice(product)) }}
                                    </span>
                                    <button
                                        :class="['rounded-full bg-red-50 text-primary flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm', gridSize === 'small' ? 'w-7 h-7' : 'w-8 h-8']">
                                        <svg :class="gridSize === 'small' ? 'w-4 h-4' : 'w-5 h-5'" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
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
            class="w-full md:w-[400px] xl:w-[420px] bg-surface-light border-l border-gray-200 dark:border-red-900/30 flex flex-col h-full min-h-0 shadow-2xl z-20">
            <!-- Order Header -->
            <div class="flex-none p-6 border-b border-gray-100">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-2">
                        <button @click="isCartOpen = !isCartOpen"
                            class="p-1 rounded-lg transition hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary/20"
                            title="Toggle Cart View">
                            <svg :class="isCartOpen ? 'rotate-0' : '-rotate-90'"
                                class="w-6 h-6 text-gray-500 transition-transform duration-200" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </button>
                        <h2 class="text-lg font-bold text-slate-900 cursor-pointer flex items-center gap-2"
                            @click="isCartOpen = !isCartOpen">
                            Current Order
                        </h2>
                    </div>
                    <button @click="clearCart" v-if="cart.length > 0"
                        class="text-primary p-2 hover:bg-red-50 rounded-lg transition" title="Clear All">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </button>
                </div>
                <div class="flex items-center text-sm text-gray-400 gap-2 pl-9">
                    <template v-if="isCartOpen">
                        <span
                            class="bg-gray-100 px-2 py-0.5 rounded text-gray-500 font-mono text-xs">#{{ $activeSession ? $activeSession->id : '---' }}</span>
                        <span>•</span>
                        <span>{{ date('d M Y, H:i') }}</span>
                    </template>
                    <template v-else>
                        <span class="font-medium text-gray-600">@{{ salesType.charAt(0).toUpperCase() + salesType.slice(1)
                            }}</span>
                        <span>•</span>
                        <span class="font-medium text-gray-600 truncate max-w-[180px] inline-block align-bottom"
                            :title="cart.map(i => i.name).join(', ')">@{{ cart.map(i => i.name).join(', ') }}</span>
                        <span>•</span>
                        <span class="font-medium text-gray-600">@{{ cart.reduce((n, {quantity}) => n + quantity, 0) }}
                            Qty</span>
                    </template>
                </div>

                <!-- Customer & Type Selector -->
                <div v-show="isCartOpen" class="grid grid-cols-2 gap-3 mt-4 transition-all">
                    <select v-model="selectedCustomerId"
                        class="bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary block w-full p-2.5">
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
            <div v-show="isCartOpen"
                class="flex-1 min-h-0 overflow-y-auto px-6 py-4 custom-scrollbar space-y-4 transition-all">
                <template v-if="cart.length > 0">
                    <!-- Item Row -->
                    <div v-for="(item, index) in cart" :key="index" class="flex gap-4 group">
                        <!-- Thumb -->
                        <div class="w-16 h-16 rounded-xl bg-gray-100 flex-none overflow-hidden relative">
                            <!-- We need product image here, so we find it from products list -->
                            <img :src="getProductImage(item.product_id)" loading="lazy" decoding="async"
                                class="w-full h-full object-cover">
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                            <div class="flex justify-between items-start">
                                <h4 class="text-sm font-bold text-gray-800 line-clamp-2 leading-tight">@{{ item.name }}</h4>
                                <span class="text-sm font-bold text-gray-900 ml-2">@{{ formatNumber(item.subtotal) }}</span>
                            </div>

                            <div class="flex items-center justify-between mt-1">
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-400">@ @{{ formatNumber(item.unit_price) }}</p>
                                    <p v-if="item.discount_amount > 0" class="text-[10px] text-red-500">
                                        Promo @{{ formatNumber(item.promo_discount_percent, 2) }}% (-Rp @{{
                                        formatNumber(item.discount_amount, 2) }})
                                    </p>
                                </div>

                                <!-- Qty Control -->
                                <div class="flex items-center gap-3 bg-gray-100 rounded-lg px-2 py-1">
                                    <button @click="decreaseQty(index)"
                                        class="w-5 h-5 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-red-600 transition text-xs font-bold disabled:opacity-50">-</button>
                                    <input type="number" min="1" step="1" inputmode="numeric" :value="item.quantity"
                                        @change="setQtyFromInput(index, $event.target.value)"
                                        @blur="setQtyFromInput(index, $event.target.value)"
                                        class="w-12 h-7 text-xs font-bold text-center rounded border border-gray-200 bg-white text-gray-700 focus:ring-1 focus:ring-red-500 focus:border-red-500 outline-none">
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
                <div v-else class="py-8 flex flex-col items-center justify-center text-gray-300">
                    <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">Keranjang masih kosong</p>
                </div>
            </div>

            <!-- Checkout Section -->
            <div :class="isCartOpen ? 'flex-none max-h-[42vh]' : 'flex-1'"
                class="p-6 border-t border-gray-100 bg-background-light/50 overflow-y-auto custom-scrollbar transition-all">
                <!-- Order Note Input -->
                <div class="mb-4">
                    <input type="text" v-model="orderNotes" placeholder="Add order note..."
                        class="w-full bg-transparent border-b border-gray-300 focus:border-primary py-2 text-sm focus:outline-none placeholder-gray-400 transition">
                </div>

                <div v-if="hasPromotionSelector || hasVoucherInput" class="mb-4 space-y-3">
                    <div v-if="hasPromotionSelector">
                        <label class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">Promo
                            Kategori</label>
                        <select v-model="selectedPromotionId"
                            class="w-full bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary p-2.5">
                            <option value="">Tanpa Promo</option>
                            <option v-for="promo in activePromotions" :key="'promo-' + promo.id" :value="String(promo.id)">
                                @{{ promo.name }}
                            </option>
                        </select>
                        <p v-if="selectedPromotion" class="text-xs text-primary mt-1">
                            Promo aktif: <strong>@{{ selectedPromotion.name }}</strong>
                        </p>
                    </div>

                    <div v-if="hasVoucherInput">
                        <label
                            class="block text-[11px] font-bold uppercase tracking-wide text-gray-500 mb-1">Voucher</label>
                        <div class="flex items-center gap-2">
                            <input type="text" v-model="voucherCodeInput" placeholder="Contoh: MEMBER10"
                                class="flex-1 bg-white border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-primary focus:border-primary p-2.5 uppercase">
                            <button type="button" @click="applyVoucherCode"
                                class="px-3 py-2 text-xs font-bold bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition">
                                Apply
                            </button>
                            <button v-if="appliedVoucher" type="button" @click="clearVoucher"
                                class="px-3 py-2 text-xs font-bold bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                Hapus
                            </button>
                        </div>
                        <p v-if="voucherErrorMessage" class="text-xs text-red-600 mt-1">@{{ voucherErrorMessage }}</p>
                        <p v-else-if="appliedVoucher" class="text-xs text-emerald-600 mt-1">
                            Voucher <strong>@{{ appliedVoucher.code }}</strong> aktif
                        </p>
                    </div>
                </div>

                <!-- Totals -->
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Subtotal Bruto</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(cartGrossSubtotal) }}</span>
                    </div>
                    <div v-if="itemDiscountTotal > 0" class="flex justify-between text-sm text-gray-500">
                        <span>Diskon Promo Kategori</span>
                        <span class="font-medium text-red-600">- Rp @{{ formatNumber(itemDiscountTotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(subtotal, 2) }}</span>
                    </div>
                    <div v-if="voucherDiscountAmount > 0" class="flex justify-between text-sm text-gray-500">
                        <span>Diskon Voucher</span>
                        <span class="font-medium text-red-600">- Rp @{{ formatNumber(voucherDiscountAmount, 2) }}</span>
                    </div>
                    <div v-if="serviceChargeRate > 0" class="flex justify-between text-sm text-gray-500">
                        <span>Service (@{{ serviceChargeRate }}%)</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(serviceChargeAmount, 2) }}</span>
                    </div>
                    <div v-if="taxRate > 0" class="flex justify-between text-sm text-gray-500">
                        <span>Tax (@{{ taxRate }}%)</span>
                        <span class="font-medium text-gray-700">Rp @{{ formatNumber(taxAmount, 2) }}</span>
                    </div>
                    <div v-if="hasRounding" class="flex justify-between text-sm text-gray-500">
                        <span>Pembulatan</span>
                        <span class="font-medium text-gray-700">
                            @{{ roundingAmount > 0 ? '+' : '-' }} Rp @{{ formatNumber(Math.abs(roundingAmount), 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-end pt-3 border-t border-dashed border-gray-200">
                        <span class="text-base font-bold text-gray-800">Total</span>
                        <span class="text-2xl font-black text-primary tracking-tight">Rp @{{ formatNumber(totalAmount)
                            }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                        <button type="button" @click="showPaymentModal = true"
                            class="w-full h-14 rounded-xl border border-primary/20 bg-white px-3 py-2 flex items-center justify-between gap-2 hover:bg-red-50 transition shadow-sm">
                            <div class="min-w-0 text-left">
                                <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold leading-tight">
                                    Metode
                                </p>
                                <p class="text-sm font-bold text-primary truncate mt-0.5 leading-tight">@{{
                                    selectedPaymentMethodName ||
                                    'Pilih...' }}</p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </button>
                    </div>

                    <button @click="processPayment" :disabled="cart.length === 0 || !selectedPaymentMethod || isProcessing"
                        :class="cart.length === 0 || !selectedPaymentMethod || isProcessing 
                                                                                            ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                                                                                            : 'bg-primary hover:bg-primary-hover text-white shadow-lg shadow-red-500/30'"
                        class="w-full h-14 rounded-xl font-bold text-lg transition flex items-center justify-center gap-2 px-4">
                        <span v-if="!isProcessing">@{{ selectedPaymentMethodName ? 'Bayar' : 'Proses'
                            }}</span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                </circle>
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

        <!-- Payment Method Modal -->
        <div v-show="showPaymentModal"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            style="display: none;" :style="{ display: showPaymentModal ? 'flex' : 'none' }">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 animate-[bounceIn_0.1s_ease-out]">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-gray-800">Pilih Metode Pembayaran</h3>
                    <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-[60vh] overflow-y-auto p-1 custom-scrollbar">
                    <button type="button" v-for="method in paymentMethods" :key="'modal-pm-' + method.id"
                        @click="selectPaymentMethod(method.id)"
                        :class="Number(selectedPaymentMethod) === Number(method.id)
                                                                            ? 'bg-primary text-white border-primary shadow-lg shadow-red-500/30'
                                                                            : 'bg-white text-gray-700 border-gray-200 hover:border-primary/40 hover:bg-red-50'"
                        class="min-h-[60px] px-4 py-3 border rounded-xl text-sm md:text-base font-bold transition flex flex-col items-center justify-center gap-1 text-center group">
                        <span>@{{ method.name }}</span>
                        <span v-if="Number(selectedPaymentMethod) === Number(method.id)"
                            class="text-[10px] font-normal opacity-80">Terpilih</span>
                    </button>
                </div>
            </div>
        </div>


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

        <!-- History Modal -->
        <div v-if="showHistoryModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            style="display: none;" :style="{ display: showHistoryModal ? 'flex' : 'none' }">
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
            style="display: none;" :style="{ display: showVoidModal ? 'flex' : 'none' }">
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

        <!-- VUE JS 3 -->
        <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
        <script>
            const { createApp } = Vue;

            createApp({
                data() {
                    return {
                        categories: @json($categories),
                        products: @json($products),
                        priceLevels: @json($priceLevels),
                        customers: @json($customers),
                        paymentMethods: @json($paymentMethods),
                        activePromotions: @json($activePromotions ?? []),
                        activeVouchers: @json($activeVouchers ?? []),
                        productById: {},
                        productImageById: {},

                        gridSize: 'large',

                        cart: [],
                        isCartOpen: true, // Toggle Cart State
                        searchQuery: '',
                        selectedCategory: null,
                        filteredProducts: [],

                        salesType: 'regular',
                        selectedPaymentMethod: '',
                        selectedCustomerId: '',
                        showPaymentMethodPicker: false,
                        showPaymentModal: false,

                        orderNotes: '',

                        showSuccessModal: false,
                        lastSale: null,

                        selectedPromotionId: '',
                        voucherCodeInput: '',
                        appliedVoucher: null,
                        voucherErrorMessage: '',
                        isProcessing: false,

                        outletId: {{ $activeSession->outlet_id ?? 'null' }},
                        cashSessionId: {{ $activeSession->id ?? 'null' }},

                        taxRate: {{ $outlet->tax_rate ?? 10 }},
                        serviceChargeRate: {{ $outlet->service_charge_rate ?? 0 }},

                        // History & Void
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
                computed: {
                    gridClasses() {
                        switch (this.gridSize) {
                            case 'small':
                                return 'grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8 gap-3';
                            case 'medium':
                                return 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-4';
                            case 'large':
                            default:
                                return 'grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6';
                        }
                    },
                    currentCategoryName() {
                        if (!this.selectedCategory) return 'All Items';
                        const category = this.categories.find(c => c.id == this.selectedCategory);
                        return category ? category.name : 'Menu';
                    },
                    selectedCustomer() {
                        if (!this.selectedCustomerId) return null;
                        return this.customers.find(c => c.id === Number(this.selectedCustomerId));
                    },
                    selectedPaymentMethodName() {
                        if (!this.selectedPaymentMethod) return '';
                        const method = this.paymentMethods.find(m => Number(m.id) === Number(this.selectedPaymentMethod));
                        return method ? method.name : '';
                    },
                    hasPromotionSelector() {
                        return Array.isArray(this.activePromotions) && this.activePromotions.length > 0;
                    },
                    hasVoucherInput() {
                        return Array.isArray(this.activeVouchers) && this.activeVouchers.length > 0;
                    },
                    selectedPromotion() {
                        if (!this.selectedPromotionId) return null;
                        return this.activePromotions.find(p => Number(p.id) === Number(this.selectedPromotionId)) || null;
                    },
                    cartGrossSubtotal() {
                        return this.cart.reduce((sum, item) => sum + (Number(item.quantity) * Number(item.unit_price)), 0);
                    },
                    itemDiscountTotal() {
                        return this.cart.reduce((sum, item) => sum + Number(item.discount_amount || 0), 0);
                    },
                    subtotal() {
                        return this.cart.reduce((sum, item) => sum + Number(item.subtotal || 0), 0);
                    },
                    voucherDiscountAmount() {
                        if (!this.appliedVoucher) return 0;
                        return this.calculateVoucherDiscount(this.appliedVoucher, this.subtotal);
                    },
                    taxBase() {
                        return Math.max(0, this.subtotal - this.voucherDiscountAmount);
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
                    rawTotalAmount() {
                        return this.taxableAmount + this.taxAmount;
                    },
                    roundedTotalAmount() {
                        return Math.round(this.rawTotalAmount);
                    },
                    roundingAmount() {
                        return Number((this.roundedTotalAmount - this.rawTotalAmount).toFixed(2));
                    },
                    hasRounding() {
                        return Math.abs(this.roundingAmount) >= 0.01;
                    },
                    totalAmount() {
                        return this.roundedTotalAmount;
                    }
                },
                mounted() {
                    this.productById = Object.fromEntries(this.products.map(product => [Number(product.id), product]));
                    this.productImageById = Object.fromEntries(this.products.map(product => [
                        Number(product.id),
                        product.image_url || 'https://via.placeholder.com/150'
                    ]));
                    this.filteredProducts = this.products;

                    if (!this.hasPromotionSelector) {
                        this.selectedPromotionId = '';
                    }

                    if (!this.hasVoucherInput) {
                        this.clearVoucher();
                    }
                },
                methods: {
                    // ... Existing Methods ...
                    async parseApiResponse(response) {
                        const contentType = response.headers.get('content-type') || '';

                        if (contentType.includes('application/json')) {
                            return await response.json();
                        }

                        const bodyText = await response.text();
                        const redirectedToLogin = response.redirected && response.url.includes('/login');
                        const looksLikeHtml = bodyText.trim().startsWith('<!DOCTYPE') || bodyText.trim().startsWith('<html');

                        if (response.status === 401 || response.status === 419 || redirectedToLogin) {
                            throw new Error('Sesi login habis. Silakan login ulang.');
                        }

                        if (looksLikeHtml) {
                            throw new Error('Respon server tidak valid (bukan JSON).');
                        }

                        throw new Error('Terjadi kesalahan sistem.');
                    },
                    getProductById(productId) {
                        return this.productById[Number(productId)] || null;
                    },
                    normalizeQuantity(value, fallback = 1) {
                        const parsed = Number.parseInt(String(value ?? '').replace(',', '.'), 10);
                        if (Number.isFinite(parsed) && parsed > 0) {
                            return parsed;
                        }

                        const safeFallback = Number.parseInt(String(fallback ?? '1'), 10);
                        return Number.isFinite(safeFallback) && safeFallback > 0 ? safeFallback : 1;
                    },
                    setQtyFromInput(index, rawValue) {
                        const item = this.cart[index];
                        if (!item) return;

                        const normalizedQty = this.normalizeQuantity(rawValue, item.quantity);
                        if (normalizedQty === item.quantity) return;

                        item.quantity = normalizedQty;
                        this.recalculateCartItem(item);
                        this.refreshAppliedVoucher();
                    },
                    getPromotionDiscountPercent(categoryId) {
                        if (!this.selectedPromotion || !Array.isArray(this.selectedPromotion.rules)) {
                            return 0;
                        }

                        const rule = this.selectedPromotion.rules.find(
                            r => Number(r.category_id) === Number(categoryId)
                        );

                        if (!rule) return 0;

                        return Number(rule.discount_percent || 0);
                    },
                    recalculateCartItem(item) {
                        const qty = this.normalizeQuantity(item.quantity, 1);
                        const unitPrice = Number(item.unit_price || 0);
                        const baseAmount = qty * unitPrice;
                        const promoPercent = this.getPromotionDiscountPercent(item.category_id);
                        const promoDiscount = Math.min(baseAmount, Number((baseAmount * (promoPercent / 100)).toFixed(2)));

                        item.quantity = qty;
                        item.promo_discount_percent = promoPercent;
                        item.discount_amount = promoDiscount;
                        item.subtotal = baseAmount - promoDiscount;
                    },
                    recalculateCart() {
                        this.cart.forEach(item => this.recalculateCartItem(item));
                        this.refreshAppliedVoucher();
                    },
                    findVoucherByCode(code) {
                        return this.activeVouchers.find(v => String(v.code).toUpperCase() === String(code).toUpperCase()) || null;
                    },
                    calculateVoucherDiscount(voucher, subtotal) {
                        if (!voucher) return 0;

                        const minPurchase = Number(voucher.min_purchase || 0);
                        if (subtotal < minPurchase) {
                            return 0;
                        }

                        let discount = 0;
                        if (voucher.discount_type === 'percentage') {
                            discount = subtotal * (Number(voucher.discount_value || 0) / 100);
                        } else {
                            discount = Number(voucher.discount_value || 0);
                        }

                        if (voucher.max_discount_amount !== null && voucher.max_discount_amount !== undefined) {
                            discount = Math.min(discount, Number(voucher.max_discount_amount));
                        }

                        return Math.max(0, Math.min(discount, subtotal));
                    },
                    applyVoucherCode() {
                        const rawCode = String(this.voucherCodeInput || '').trim().toUpperCase();
                        if (!rawCode) {
                            this.clearVoucher();
                            return;
                        }

                        const voucher = this.findVoucherByCode(rawCode);
                        if (!voucher) {
                            this.appliedVoucher = null;
                            this.voucherErrorMessage = 'Voucher tidak ditemukan atau tidak aktif.';
                            return;
                        }

                        if (this.subtotal < Number(voucher.min_purchase || 0)) {
                            this.appliedVoucher = null;
                            this.voucherErrorMessage = `Minimum belanja voucher ini Rp ${this.formatNumber(voucher.min_purchase || 0)}.`;
                            return;
                        }

                        this.appliedVoucher = voucher;
                        this.voucherCodeInput = String(voucher.code || '').toUpperCase();
                        this.voucherErrorMessage = '';
                    },
                    clearVoucher() {
                        this.appliedVoucher = null;
                        this.voucherErrorMessage = '';
                        this.voucherCodeInput = '';
                    },
                    refreshAppliedVoucher() {
                        if (!this.appliedVoucher) return;

                        if (this.subtotal < Number(this.appliedVoucher.min_purchase || 0)) {
                            this.voucherErrorMessage = 'Voucher dilepas karena tidak memenuhi minimum belanja.';
                            this.appliedVoucher = null;
                            return;
                        }

                        this.voucherErrorMessage = '';
                    },
                    selectPaymentMethod(methodId) {
                        this.selectedPaymentMethod = methodId;
                        this.showPaymentMethodPicker = false;
                        this.showPaymentModal = false;
                    },
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
                            const product = this.getProductById(item.product_id);
                            if (!product) return item;

                            const newPrice = this.getProductPrice(product);
                            const updatedItem = {
                                ...item,
                                unit_price: newPrice
                            };
                            this.recalculateCartItem(updatedItem);
                            return updatedItem;
                        });
                        this.refreshAppliedVoucher();
                    },
                    addToCart(product) {
                        const existing = this.cart.find(i => i.product_id === product.id);
                        const price = this.getProductPrice(product);

                        if (existing) {
                            existing.quantity = this.normalizeQuantity(Number(existing.quantity || 0) + 1, 1);
                            existing.unit_price = price;
                            this.recalculateCartItem(existing);
                        } else {
                            const newItem = {
                                product_id: product.id,
                                name: product.name,
                                category_id: product.category_id,
                                unit_price: price,
                                quantity: 1,
                                discount_amount: 0,
                                promo_discount_percent: 0,
                                subtotal: price,
                                notes: ''
                            };
                            this.recalculateCartItem(newItem);
                            this.cart.push(newItem);
                        }

                        this.refreshAppliedVoucher();
                    },
                    getProductImage(productId) {
                        return this.productImageById[Number(productId)] || 'https://via.placeholder.com/150';
                    },
                    clearCart() {
                        this.cart = [];
                        this.clearVoucher();
                    },
                    increaseQty(index) {
                        const item = this.cart[index];
                        if (!item) return;
                        item.quantity = this.normalizeQuantity(Number(item.quantity || 0) + 1, 1);
                        this.recalculateCartItem(item);
                        this.refreshAppliedVoucher();
                    },
                    decreaseQty(index) {
                        const item = this.cart[index];
                        if (!item) return;

                        const currentQty = this.normalizeQuantity(item.quantity, 1);
                        if (currentQty > 1) {
                            item.quantity = currentQty - 1;
                            this.recalculateCartItem(item);
                        } else {
                            this.cart.splice(index, 1);
                        }

                        this.refreshAppliedVoucher();
                    },
                    editItemNotes(index) {
                        const current = this.cart[index].notes || '';
                        const updated = prompt('Add note:', current);
                        if (updated !== null) {
                            this.cart[index].notes = updated;
                        }
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
                    async processPayment() {
                        if (!this.outletId || !this.cashSessionId) {
                            alert('No active cash session.');
                            return;
                        }

                        if (this.hasVoucherInput && this.voucherCodeInput && !this.appliedVoucher) {
                            this.applyVoucherCode();
                            if (!this.appliedVoucher) {
                                alert(this.voucherErrorMessage || 'Voucher belum valid.');
                                return;
                            }
                        }

                        this.isProcessing = true;

                        const voucherCode = this.appliedVoucher ? String(this.appliedVoucher.code || '').toUpperCase() : null;

                        // Payload dengan promo/voucher
                        const data = {
                            outlet_id: this.outletId,
                            cash_session_id: this.cashSessionId,
                            customer_id: this.selectedCustomerId,
                            notes: this.orderNotes,
                            sales_type: this.salesType,
                            promotion_id: this.selectedPromotionId ? Number(this.selectedPromotionId) : null,
                            voucher_code: voucherCode,
                            discount_type: this.appliedVoucher ? this.appliedVoucher.discount_type : 'none',
                            discount_value: this.appliedVoucher ? Number(this.appliedVoucher.discount_value || 0) : 0,
                            items: this.cart.map(i => ({
                                product_id: i.product_id,
                                quantity: this.normalizeQuantity(i.quantity, 1),
                                unit_price: i.unit_price,
                                discount_amount: Number(i.discount_amount || 0),
                                notes: i.notes || null
                            })),
                            payment_method_id: this.selectedPaymentMethod,
                            payment_amount: this.totalAmount
                        };

                        try {
                            const response = await fetch('{{ route('pos.sales.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify(data)
                            });
                            const result = await this.parseApiResponse(response);

                            if (!response.ok) {
                                if (response.status === 401 || response.status === 419) {
                                    alert('Sesi login habis. Silakan login ulang.');
                                    window.location.href = '{{ route('login') }}';
                                    return;
                                }
                                alert('Error: ' + (result.error || result.message || 'Permintaan gagal diproses.'));
                                return;
                            }

                            if (result.success) {
                                this.lastSale = result.data;
                                this.showSuccessModal = true;
                                this.cart = [];
                                this.selectedPaymentMethod = '';
                                this.selectedPromotionId = '';
                                this.clearVoucher();
                                this.showPaymentMethodPicker = false;
                            } else {
                                alert('Error: ' + result.message);
                            }
                        } catch (e) {
                            if (e.message === 'Sesi login habis. Silakan login ulang.') {
                                alert(e.message);
                                window.location.href = '{{ route('login') }}';
                                return;
                            }
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
                    },

                    // --- History Methods ---
                    openHistory() {
                        this.showHistoryModal = true;
                        this.fetchHistory();
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
                                if (response.status === 401 || response.status === 419) {
                                    alert('Sesi login habis. Silakan login ulang.');
                                    window.location.href = '{{ route('login') }}';
                                    return;
                                }
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
                            if (e.message === 'Sesi login habis. Silakan login ulang.') {
                                alert(e.message);
                                window.location.href = '{{ route('login') }}';
                                return;
                            }
                            alert('Gagal mengambil history transaksi');
                        } finally {
                            this.isLoadingHistory = false;
                        }
                    },
                    selectSale(sale) {
                        this.selectedSale = sale;
                    },
                    printReceiptFromHistory(sale) {
                        window.open(`/pos/sales/${sale.id}/print`, '_blank', 'width=400,height=600');
                    },

                    // --- Void Methods ---
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
                        if (!this.voidReason || !this.voidToken) {
                            alert('Mohon lengkapi alasan dan Token!');
                            return;
                        }

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
                                if (response.status === 401 || response.status === 419) {
                                    alert('Sesi login habis. Silakan login ulang.');
                                    window.location.href = '{{ route('login') }}';
                                    return;
                                }
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
                            if (e.message === 'Sesi login habis. Silakan login ulang.') {
                                alert(e.message);
                                window.location.href = '{{ route('login') }}';
                                return;
                            }
                            alert('Terjadi kesalahan sistem.');
                        } finally {
                            this.isVoiding = false;
                        }
                    }
                },
                watch: {
                    selectedCategory() { this.filterProducts(); },
                    salesType() { this.updateCartPricesBySalesType(); },
                    selectedPromotionId() { this.recalculateCart(); }
                }
            }).mount('#posApp');
        </script>

        @push('scripts')
            <script>
                (function () {
                    var button = document.getElementById('posFullscreenBtn');
                    var root = document.getElementById('posApp');
                    if (!button || !root) return;

                    function getFullscreenElement() {
                        return document.fullscreenElement || document.webkitFullscreenElement || null;
                    }

                    function setTitle() {
                        var isFs = !!getFullscreenElement();
                        button.title = isFs ? 'Keluar Fullscreen' : 'Fullscreen';
                    }

                    async function toggleFullscreen() {
                        try {
                            if (getFullscreenElement()) {
                                var exit = document.exitFullscreen || document.webkitExitFullscreen;
                                if (exit) await exit.call(document);
                                return;
                            }

                            var request = root.requestFullscreen || root.webkitRequestFullscreen;
                            if (!request) {
                                alert('Browser ini tidak mendukung mode fullscreen. Coba pakai F11 (Windows) atau install sebagai aplikasi (PWA).');
                                return;
                            }
                            await request.call(root);
                        } catch (e) {
                            console.error(e);
                        } finally {
                            setTitle();
                        }
                    }

                    button.addEventListener('click', toggleFullscreen);
                    document.addEventListener('fullscreenchange', setTitle);
                    document.addEventListener('webkitfullscreenchange', setTitle);
                    setTitle();
                })();
            </script>
        @endpush
@endsection