@extends('layouts.pos')

@section('title', 'Transaksi Penjualan')
@section('page-title', 'Transaksi Baru')

@section('content')
<div class="h-full flex flex-col md:flex-row" id="posApp">
    
    <!-- Left: Product Selection -->
    <div class="flex-1 bg-gray-800 p-4 md:p-6 overflow-y-auto">
        
        <!-- Alert jika tidak ada session aktif -->
        @if(!$activeSession)
        <div class="bg-red-900 border border-red-700 text-red-200 px-4 py-3 rounded-lg mb-4">
            <strong>Peringatan!</strong> Tidak ada sesi kasir yang aktif. Silakan buka shift terlebih dahulu.
        </div>
        @endif

        <!-- Search Bar -->
        <div class="mb-6">
            <input type="text" 
                   v-model="searchQuery"
                   @input="filterProducts"
                   placeholder="Cari produk..." 
                   class="w-full px-4 py-3 bg-gray-700 text-white placeholder-gray-400 rounded-lg border border-gray-600 focus:border-indigo-500 focus:outline-none">
        </div>

        <!-- Category Tabs -->
        <div class="flex space-x-2 mb-6 overflow-x-auto">
            <button @click="selectedCategory = null" 
                    :class="selectedCategory === null ? 'bg-indigo-600' : 'bg-gray-700 hover:bg-gray-600'" 
                    class="px-4 py-2 text-white rounded-lg whitespace-nowrap transition">
                Semua
            </button>
            @foreach($categories as $category)
            <button @click="selectedCategory = {{ $category->id }}" 
                    :class="selectedCategory === {{ $category->id }} ? 'bg-indigo-600' : 'bg-gray-700 hover:bg-gray-600'" 
                    class="px-4 py-2 text-white rounded-lg whitespace-nowrap transition">
                {{ $category->name }}
            </button>
            @endforeach
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <template v-for="product in filteredProducts" :key="product.id">
                <button @click="addToCart(product)" 
                        class="bg-gray-700 hover:bg-gray-600 rounded-lg p-4 text-left transition group">
                    <div class="aspect-square bg-gray-600 rounded-lg mb-3 flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h4 class="text-sm font-medium text-white mb-1 truncate" v-text="product.name"></h4>
                    <p class="text-xs text-gray-400 mb-2" v-text="product.sku"></p>
                    <p class="text-lg font-bold text-indigo-400">Rp @{{ formatNumber(product.selling_price) }}</p>
                </button>
            </template>

            <div v-if="filteredProducts.length === 0" class="col-span-full text-center py-16">
                <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-400">Tidak ada produk ditemukan</p>
            </div>
        </div>
    </div>

    <!-- Right: Cart, Customer & Payment -->
    <div class="w-full md:w-[420px] bg-gray-900 flex flex-col border-t md:border-t-0 md:border-l border-gray-700">
        
        <!-- Cart Header -->
        <div class="p-6 border-b border-gray-700 space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                        <span>Keranjang</span>
                        <span v-if="hasAnyNotes"
                              class="inline-flex items-center rounded-full bg-amber-500/20 text-amber-300 text-[11px] font-semibold px-2 py-0.5">
                            Catatan
                        </span>
                    </h3>
                    <p class="text-sm text-gray-400 mt-1">@{{ cart.length }} item</p>
                </div>
            </div>

            <!-- Customer Selection -->
            <div class="space-y-2">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Customer</label>
                    <select v-model="selectedCustomerId"
                            class="w-full px-3 py-2 bg-gray-800 text-white rounded-lg border border-gray-700 focus:border-indigo-500 focus:outline-none text-sm">
                        <option value="">Walk-in / Umum</option>
                        <option v-for="customer in customers"
                                :key="customer.id"
                                :value="customer.id">
                            @{{ customer.name }} (@{{ customer.phone || customer.customer_code }})
                        </option>
                    </select>
                </div>
                <div v-if="selectedCustomer" class="text-xs text-indigo-300">
                    <span>Poin: @{{ formatNumber(selectedCustomer.loyalty_points || 0) }}</span>
                    <span v-if="selectedCustomer.member_tier" class="ml-2">
                        • Tier: @{{ selectedCustomer.member_tier }}
                    </span>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">Nama Pelanggan (opsional)</label>
                    <input type="text"
                           v-model="customerName"
                           placeholder="Isi untuk walk-in customer"
                           class="w-full px-3 py-2 bg-gray-800 text-white rounded-lg border border-gray-700 focus:border-indigo-500 focus:outline-none text-sm">
                </div>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-6">
            <!-- Empty State -->
            <div v-if="cart.length === 0" class="flex flex-col items-center justify-center h-full text-center">
                <svg class="w-20 h-20 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="text-gray-500">Keranjang masih kosong</p>
                <p class="text-sm text-gray-600 mt-1">Pilih produk untuk memulai</p>
            </div>

            <!-- Cart Items List -->
            <div v-else class="space-y-3">
                <div v-for="(item, index) in cart" :key="index" 
                     class="bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-white mb-1" v-text="item.name"></h4>
                            <p class="text-xs text-gray-400" v-text="item.sku"></p>
                            <p v-if="item.notes" class="text-xs text-amber-300 mt-1">
                                @{{ item.notes }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <button @click="removeFromCart(index)" 
                                    class="text-red-400 hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <button @click="editItemNotes(index)"
                                    class="text-[10px] text-indigo-300 hover:text-indigo-200 underline-offset-2 hover:underline">
                                Catatan
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button @click="decreaseQty(index)" 
                                    class="w-8 h-8 bg-gray-700 hover:bg-gray-600 text-white rounded flex items-center justify-center">
                                -
                            </button>
                            <input type="number" 
                                   v-model.number="item.quantity"
                                   @change="updateItemTotal(index)"
                                   min="1"
                                   class="w-16 px-2 py-1 bg-gray-700 text-white text-center rounded">
                            <button @click="increaseQty(index)" 
                                    class="w-8 h-8 bg-gray-700 hover:bg-gray-600 text-white rounded flex items-center justify-center">
                                +
                            </button>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">@Rp @{{ formatNumber(item.unit_price) }}</p>
                            <p class="text-sm font-semibold text-white">Rp @{{ formatNumber(item.subtotal) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cart Summary & Payment -->
        <div class="border-t border-gray-700 p-6">
            <div class="space-y-3 mb-6">
                <div class="flex justify-between text-gray-300">
                    <span>Subtotal</span>
                    <span>Rp @{{ formatNumber(subtotal) }}</span>
                </div>
                <div class="flex justify-between text-gray-300">
                    <span>Diskon</span>
                    <span>Rp @{{ formatNumber(discountAmount) }}</span>
                </div>
                <div class="border-t border-gray-700 pt-3">
                    <div class="flex justify-between text-white">
                        <span class="text-lg font-semibold">Total</span>
                        <span class="text-2xl font-bold text-indigo-400">Rp @{{ formatNumber(totalAmount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Order Notes / Special Requests -->
            <div class="mb-4">
                <label class="block text-sm text-gray-300 mb-2">Catatan Pesanan (opsional)</label>
                <textarea v-model="orderNotes"
                          rows="2"
                          placeholder="Contoh: Nasi goreng pedas sedang, minuman less ice, tanpa saos..."
                          class="w-full px-3 py-2 bg-gray-800 text-white rounded-lg border border-gray-700 focus:border-indigo-500 focus:outline-none text-sm resize-none"></textarea>
            </div>

            <!-- Payment Method -->
            <div v-if="cart.length > 0" class="mb-4">
                <label class="block text-sm text-gray-300 mb-2">Metode Pembayaran</label>
                <select v-model="selectedPaymentMethod" 
                        class="w-full px-4 py-2 bg-gray-800 text-white rounded-lg border border-gray-700 focus:border-indigo-500 focus:outline-none">
                    <option value="">Pilih metode...</option>
                    @foreach($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>

            <button @click="processPayment" 
                    :disabled="cart.length === 0 || !selectedPaymentMethod || isProcessing"
                    :class="cart.length === 0 || !selectedPaymentMethod || isProcessing ? 'bg-gray-700 text-gray-500 cursor-not-allowed' : 'bg-indigo-600 hover:bg-indigo-700 text-white'"
                    class="w-full py-4 rounded-lg font-semibold transition">
                <span v-if="!isProcessing">Bayar</span>
                <span v-else>Memproses...</span>
            </button>
        </div>
    </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<script>
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
            discountType: 'none',
            discountValue: 0,
            isProcessing: false,
            outletId: {{ $activeSession->outlet_id ?? 'null' }},
            cashSessionId: {{ $activeSession->id ?? 'null' }},
            selectedCustomerId: '',
            customerName: '',
            orderNotes: '',
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
        totalAmount() {
            return this.subtotal - this.discountAmount;
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
            
            if (existingItem) {
                existingItem.quantity++;
                existingItem.subtotal = existingItem.quantity * existingItem.unit_price;
            } else {
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    sku: product.sku,
                    quantity: 1,
                    unit_price: product.selling_price,
                    discount_amount: 0,
                    subtotal: product.selling_price,
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
                    alert(`Transaksi berhasil!\nInvoice: ${result.data.invoice_number}\nTotal: Rp ${this.formatNumber(result.data.total_amount)}`);
                    
                    // Reset cart
                    this.cart = [];
                    this.selectedPaymentMethod = '';
                    
                    // Redirect atau reload
                    // window.location.href = '{{ route('pos.dashboard') }}';
                } else {
                    alert('Gagal: ' + result.message + '\n' + (result.error || ''));
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            } finally {
                this.isProcessing = false;
            }
        }
    },
    watch: {
        selectedCategory() {
            this.filterProducts();
        }
    }
}).mount('#posApp');
</script>
@endsection
