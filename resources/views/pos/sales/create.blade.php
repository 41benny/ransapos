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
                <!-- Brand / Logo Area -->
                <div class="flex items-center gap-3">
                    <div class="bg-primary text-white p-2 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Moresto<span
                            class="text-primary">POS</span>
                    </h1>
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
                    <div class="hidden xl:flex items-center gap-2">
                        @if($activeSession)
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Shift Aktif #{{ $activeSession->id }}
                            </span>

                            <a href="{{ route('pos.sessions.print', $activeSession->id) }}" target="_blank"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
                                title="Cetak Rekap Shift">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </a>

                            <a href="{{ route('pos.sessions.close') }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-700 transition"
                                title="Tutup Shift">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </a>
                        @else
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                Shift Belum Dibuka
                            </span>

                            <a href="{{ route('pos.sessions.open') }}"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 transition"
                                title="Buka Shift">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                            </a>
                        @endif

                        <a href="{{ route('pos.attendance.index') }}"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-700 transition"
                            title="Absensi">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                            </svg>
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-700 transition"
                                title="Logout">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </form>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 p-0.5 border-2 border-white shadow-sm overflow-hidden">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=EF4444&color=fff"
                            alt="User" class="w-full h-full rounded-full object-cover">
                    </div>
                </div>
            </div>

            <!-- Quick Actions (Tablet) -->
            <div
                class="hidden md:flex xl:hidden flex-none px-6 py-3 bg-surface-light border-b border-gray-200 dark:border-red-900/30">
                <div class="w-full flex items-center gap-2 overflow-x-auto scrollbar-hide">
                    @if($activeSession)
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                            Shift Aktif #{{ $activeSession->id }}
                        </span>

                        <a href="{{ route('pos.sessions.print', $activeSession->id) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Rekap
                        </a>

                        <a href="{{ route('pos.sessions.close') }}"
                            class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-700 text-xs font-semibold transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Tutup Shift
                        </a>
                    @else
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">
                            Shift Belum Dibuka
                        </span>

                        <a href="{{ route('pos.sessions.open') }}"
                            class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-700 text-xs font-semibold transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Buka Shift
                        </a>
                    @endif

                    <button @click="openHistory"
                        class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-semibold transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Riwayat
                    </button>

                    <a href="{{ route('pos.attendance.index') }}"
                        class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-violet-100 hover:bg-violet-200 text-violet-700 text-xs font-semibold transition shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197" />
                        </svg>
                        Absensi
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="shrink-0">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 h-9 rounded-lg bg-rose-100 hover:bg-rose-200 text-rose-700 text-xs font-semibold transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
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
                    <span class="text-sm text-gray-500 font-medium">showing @{{ filteredProducts.length }} items</span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                    <template v-for="product in filteredProducts" :key="product.id">
                        <div class="bg-surface-light rounded-2xl p-4 shadow-sm border border-transparent hover:border-primary/30 hover:shadow-xl hover:shadow-primary/5 transition-all duration-300 group flex flex-col h-full cursor-pointer"
                            @click="addToCart(product)">

                            <!-- Image -->
                            <div class="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative bg-gray-50">
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
                                    class="text-base font-bold text-gray-800 group-hover:text-red-600 transition mb-1 leading-snug">
                                    @{{ product.name }}</h3>
                                <!-- SKU/Desc -->
                                <p class="text-xs text-gray-400 mb-3 line-clamp-2">@{{ product.description || product.sku }}
                                </p>

                                <div class="mt-auto flex items-center justify-between">
                                    <span class="text-lg font-bold text-primary">
                                        <span class="text-xs text-primary/70 align-top mr-0.5">Rp</span>@{{
                                        formatNumber(getProductPrice(product)) }}
                                    </span>
                                    <button
                                        class="w-8 h-8 rounded-full bg-red-50 text-primary flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm">
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
            class="w-full md:w-[400px] xl:w-[420px] bg-surface-light border-l border-gray-200 dark:border-red-900/30 flex flex-col h-full min-h-0 shadow-2xl z-20">
            <!-- Order Header -->
            <div class="flex-none p-6 border-b border-gray-100">
                <div class="flex justify-between items-start mb-1">
                    <h2 class="text-2xl font-bold text-slate-900">Current Order</h2>
                    <button @click="cart = []" v-if="cart.length > 0"
                        class="text-primary p-2 hover:bg-red-50 rounded-lg transition">
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
            <div class="flex-1 min-h-0 overflow-y-auto px-6 py-4 custom-scrollbar space-y-4">
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
                <div v-else class="py-8 flex flex-col items-center justify-center text-gray-300">
                    <svg class="w-16 h-16 mb-4 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-sm font-medium text-gray-400">Keranjang masih kosong</p>
                </div>
            </div>

            <!-- Checkout Section -->
            <div class="flex-none p-6 border-t border-gray-100 bg-background-light/50 max-h-[42vh] overflow-y-auto custom-scrollbar">
                <!-- Order Note Input -->
                <div class="mb-4">
                    <input type="text" v-model="orderNotes" placeholder="Add order note..."
                        class="w-full bg-transparent border-b border-gray-300 focus:border-primary py-2 text-sm focus:outline-none placeholder-gray-400 transition">
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
                        <span class="text-2xl font-black text-primary tracking-tight">Rp @{{ formatNumber(totalAmount)
                            }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <div class="relative">
                        <div v-show="showPaymentMethodPicker"
                            class="absolute bottom-full left-0 right-0 mb-2 z-30 rounded-xl border border-primary/20 bg-white p-3 shadow-2xl shadow-black/10">
                            <div class="grid grid-cols-2 gap-2 max-h-44 overflow-y-auto pr-1 custom-scrollbar">
                                <button type="button" v-for="method in paymentMethods" :key="'pm-' + method.id"
                                    @click="selectPaymentMethod(method.id)"
                                    :class="Number(selectedPaymentMethod) === Number(method.id)
                                        ? 'bg-primary text-white border-primary shadow-md shadow-red-500/20'
                                        : 'bg-white text-gray-700 border-gray-200 hover:border-primary/40 hover:bg-red-50'"
                                    class="min-h-[40px] px-2 py-2 border rounded-lg text-xs md:text-[13px] font-semibold transition text-center leading-tight">
                                    @{{ method.name }}
                                </button>
                            </div>
                        </div>

                        <button type="button" @click="showPaymentMethodPicker = !showPaymentMethodPicker"
                            class="w-full rounded-xl border border-primary/20 bg-white px-3 py-2.5 flex items-center justify-between gap-3 text-left">
                            <div class="min-w-0">
                                <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Metode Pembayaran</p>
                                <p class="text-sm font-bold text-primary truncate mt-0.5">@{{ selectedPaymentMethodName || 'Belum dipilih' }}</p>
                            </div>
                            <svg :class="showPaymentMethodPicker ? 'rotate-180' : ''"
                                class="w-4 h-4 text-gray-400 transition-transform shrink-0" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <button @click="processPayment"
                        :disabled="cart.length === 0 || !selectedPaymentMethod || isProcessing"
                        :class="cart.length === 0 || !selectedPaymentMethod || isProcessing 
                            ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                            : 'bg-primary hover:bg-primary-hover text-white shadow-lg shadow-red-500/30'"
                        class="w-full py-4 rounded-xl font-bold text-lg transition flex items-center justify-center gap-2">
                        <span v-if="!isProcessing">@{{ selectedPaymentMethodName ? 'Bayar Sekarang' : 'Pilih Metode Dulu' }}</span>
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
                        productById: {},
                        productImageById: {},

                        cart: [],
                        searchQuery: '',
                        selectedCategory: null,
                        filteredProducts: [],

                        salesType: 'regular',
                        selectedPaymentMethod: '',
                        selectedCustomerId: '',
                        showPaymentMethodPicker: false,

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
                    this.productById = Object.fromEntries(this.products.map(product => [Number(product.id), product]));
                    this.productImageById = Object.fromEntries(this.products.map(product => [
                        Number(product.id),
                        product.image_url || 'https://via.placeholder.com/150'
                    ]));
                    this.filteredProducts = this.products;
                },
                methods: {
                    // ... Existing Methods ...
                    getProductById(productId) {
                        return this.productById[Number(productId)] || null;
                    },
                    selectPaymentMethod(methodId) {
                        this.selectedPaymentMethod = methodId;
                        this.showPaymentMethodPicker = false;
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
                        return this.productImageById[Number(productId)] || 'https://via.placeholder.com/150';
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
                                this.showPaymentMethodPicker = false;
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
                            const response = await fetch('{{ route('pos.sales.history') }}');
                            const result = await response.json();
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
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    reason: this.voidReason,
                                    token: this.voidToken
                                })
                            });
                            const result = await response.json();

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
                    }
                },
                watch: {
                    selectedCategory() { this.filterProducts(); },
                    salesType() { this.updateCartPricesBySalesType(); }
                }
            }).mount('#posApp');
        </script>
@endsection
