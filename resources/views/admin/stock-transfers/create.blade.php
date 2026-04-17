@extends('layouts.admin')

@php
    $isEditMode = isset($stockTransfer);
@endphp

@section('title', $isEditMode ? 'Edit Transfer Stok' : 'Buat Transfer Stok')
@section('page-title', $isEditMode ? 'Edit Transfer Stok' : 'Buat Transfer Stok')
@section('page-subtitle', $isEditMode ? 'Perbarui draft pengiriman stok antar outlet' : 'Buat pengiriman stok barang antar outlet cabang')

@section('content')
<div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">{{ $isEditMode ? 'Edit Draft Transfer' : 'Buat Transfer Baru' }}</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">
                {{ $isEditMode ? 'Perbarui data transfer bahan baku sebelum proses kirim dilakukan' : 'Lakukan pemindahan stok bahan baku antar outlet dengan aman' }}
            </p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.stock-transfers.index') }}"
                class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Kembali ke Daftar</span>
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex flex-col gap-2 text-rose-600 animate-in slide-in-from-top-2 text-xs">
            <div class="flex items-center gap-2 font-normal">
                <i class="fas fa-circle-exclamation"></i>
                <span>Mohon periksa kembali formulir Anda:</span>
            </div>
            <ul class="list-disc list-inside pl-2 space-y-1 font-normal opacity-90">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $isEditMode ? route('admin.stock-transfers.update', $stockTransfer->id) : route('admin.stock-transfers.store') }}" id="transferForm" class="space-y-6">
        @csrf
        @if($isEditMode)
            @method('PUT')
        @endif

        {{-- Route Info Card --}}
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <i class="fas fa-route text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Rute Pengiriman</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Asal (From) <span class="text-rose-500">*</span></label>
                        <select name="from_outlet_id" id="from_outlet_id" required
                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                            <option value="">Pilih Outlet Pengirim</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('from_outlet_id', $isEditMode ? $stockTransfer->from_outlet_id : null) == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tujuan (To) <span class="text-rose-500">*</span></label>
                        <select name="to_outlet_id" id="to_outlet_id" required
                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                            <option value="">Pilih Outlet Penerima</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('to_outlet_id', $isEditMode ? $stockTransfer->to_outlet_id : null) == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" name="transfer_date" id="transfer_date" required value="{{ old('transfer_date', $isEditMode ? optional($stockTransfer->transfer_date)->format('Y-m-d') : date('Y-m-d')) }}"
                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Catatan</label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes', $isEditMode ? $stockTransfer->notes : null) }}" placeholder="Opsional..."
                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Card --}}
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-shopping-basket text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Item Bahan Baku yang Di-transfer</h3>
                </div>
                <button type="button" id="addItemBtn"
                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-[10px] font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-plus"></i>
                    <span>TAMBAH BAHAN</span>
                </button>
            </div>

            <div class="p-6">
                <div id="itemsContainer" class="space-y-4">
                    {{-- Rows injected by JS --}}
                </div>
            </div>

            <div class="sticky bottom-0 z-20 p-6 bg-slate-50/95 backdrop-blur supports-[backdrop-filter]:bg-slate-50/85 border-t border-slate-100 flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <button type="button" id="addItemBtnBottom"
                    class="ui-btn ui-btn-primary inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-[11px] font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-plus text-[10px]"></i>
                    <span>TAMBAH BAHAN LAGI</span>
                </button>
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.stock-transfers.index') }}" class="text-[11px] font-normal text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Batalkan</a>
                    <button type="submit"
                        class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-slate-900 px-8 py-3 text-xs font-normal text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>{{ $isEditMode ? 'SIMPAN PERUBAHAN DRAFT' : 'SIMPAN & PROSES TRANSFER' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let itemIndex = 0;
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemBtn = document.getElementById('addItemBtn');
    const addItemBtnBottom = document.getElementById('addItemBtnBottom');
    const fromOutletSelect = document.getElementById('from_outlet_id');
    const availableStockBaseUrl = @json(route('admin.stock-transfers.available-stock'));
    const products = @json($productsPayload);
    const productMap = new Map(products.map(product => [String(product.id), product]));
    const prefillItems = @json(old('items', $isEditMode ? ($prefillItems ?? []) : []));

    // Add initial rows
    if (Array.isArray(prefillItems) && prefillItems.length > 0) {
        prefillItems.forEach(item => addItem(item));
    } else {
        addItem();
    }

    addItemBtn.addEventListener('click', () => addItem(null, { focusSearch: true, scrollIntoView: true }));
    addItemBtnBottom.addEventListener('click', () => addItem(null, { focusSearch: true, scrollIntoView: true }));

    function addItem(prefill = null, options = {}) {
        const { focusSearch = false, scrollIntoView = false } = options;
        const itemHtml = `
            <div class="group relative bg-white border border-slate-200 rounded-2xl p-4 shadow-sm transition-all hover:shadow-md item-row animate-in zoom-in-95 duration-200" data-index="${itemIndex}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                    <div class="md:col-span-5">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Pilih Bahan Baku</label>
                        <div class="relative">
                            <input type="text"
                                   class="product-search ui-input w-full px-4 py-2 text-[11px] font-normal bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                                   placeholder="Ketik nama bahan baku atau SKU..."
                                   autocomplete="off"
                                   required>
                            <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id-input" required>
                            <div class="product-suggestions absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-auto hidden"></div>
                        </div>
                        <p class="product-meta mt-1 ml-1 text-[9px] text-slate-400">Ketik minimal 1 huruf untuk mencari bahan baku.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Tersedia</label>
                        <div class="available-stock text-[12px] font-normal text-slate-800 bg-slate-50 border border-slate-100 rounded-xl px-4 py-2 min-h-[40px] flex items-center">
                            -
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Qty Kirim</label>
                        <input type="number" name="items[${itemIndex}][quantity]" step="0.01" min="0.01" required
                               class="quantity-input ui-input w-full px-4 py-2 text-[12px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums" placeholder="0.00">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Catatan Item</label>
                        <input type="text" name="items[${itemIndex}][notes]"
                               class="ui-input w-full px-4 py-2 text-[11px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm" placeholder="Catatan kecil...">
                    </div>
                    <div class="md:col-span-1 flex justify-center pb-1">
                        <button type="button" class="remove-item-btn ui-btn ui-btn-ghost h-9 w-9 inline-flex items-center justify-center bg-white border border-rose-100 text-rose-400 hover:bg-rose-500 hover:text-white hover:border-rose-500 rounded-xl transition-all shadow-sm active:scale-90 p-2">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);

        const newRow = itemsContainer.lastElementChild;
        const productSearch = newRow.querySelector('.product-search');
        const productIdInput = newRow.querySelector('.product-id-input');
        const productSuggestions = newRow.querySelector('.product-suggestions');
        const productMeta = newRow.querySelector('.product-meta');
        const removeBtn = newRow.querySelector('.remove-item-btn');

        // Event listener for autocomplete input
        productSearch.addEventListener('input', function() {
            productIdInput.value = '';
            productMeta.textContent = 'Pilih dari daftar saran agar bahan baku terset.';
            renderSuggestions(newRow, productSearch.value);
            checkAvailableStock(newRow);
        });

        productSearch.addEventListener('focus', function() {
            renderSuggestions(newRow, productSearch.value);
        });

        productSearch.addEventListener('blur', function() {
            setTimeout(() => {
                productSuggestions.classList.add('hidden');
            }, 120);
        });

        productSuggestions.addEventListener('mousedown', function(event) {
            const btn = event.target.closest('.product-suggestion-item');
            if (!btn) {
                return;
            }

            event.preventDefault();
            const product = productMap.get(btn.dataset.productId);
            if (!product) {
                return;
            }

            applySelectedProduct(newRow, product);
        });

        // Event listener for remove button
        removeBtn.addEventListener('click', function() {
            if (itemsContainer.children.length > 1) {
                newRow.classList.add('zoom-out-95', 'opacity-0');
                setTimeout(() => newRow.remove(), 200);
            }
        });

        if (prefill) {
            const selectedProduct = productMap.get(String(prefill.product_id));
            if (selectedProduct) {
                applySelectedProduct(newRow, selectedProduct);
            }

            if (typeof prefill.quantity !== 'undefined' && prefill.quantity !== null) {
                const qtyInput = newRow.querySelector('.quantity-input');
                qtyInput.value = prefill.quantity;
            }

            if (typeof prefill.notes !== 'undefined' && prefill.notes !== null) {
                const notesInput = newRow.querySelector(`input[name="items[${itemIndex}][notes]"]`);
                notesInput.value = prefill.notes;
            }
        }

        if (scrollIntoView) {
            requestAnimationFrame(() => {
                newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

        if (focusSearch) {
            requestAnimationFrame(() => {
                productSearch.focus();
            });
        }

        itemIndex++;
    }

    function productTypeLabel(type) {
        switch (type) {
            case 'raw_material':
                return 'Bahan Baku';
            case 'finished_good':
                return 'Produk Jadi';
            case 'service':
                return 'Jasa';
            default:
                return 'Produk';
        }
    }

    function productSearchText(product) {
        return [
            product.name || '',
            product.sku || '',
            productTypeLabel(product.product_type),
        ].join(' ').toLowerCase();
    }

    function renderSuggestions(row, keyword) {
        const suggestionsWrap = row.querySelector('.product-suggestions');
        const term = (keyword || '').trim().toLowerCase();

        if (!term) {
            suggestionsWrap.classList.add('hidden');
            suggestionsWrap.innerHTML = '';
            return;
        }

        const filtered = products
            .filter(product => productSearchText(product).includes(term))
            .slice(0, 15);

        if (filtered.length === 0) {
            suggestionsWrap.innerHTML = '<div class="px-3 py-2 text-[10px] text-slate-400">Tidak ada bahan baku yang cocok</div>';
            suggestionsWrap.classList.remove('hidden');
            return;
        }

        suggestionsWrap.innerHTML = filtered.map(product => `
            <button type="button"
                    class="product-suggestion-item w-full text-left px-3 py-2 border-b border-slate-100 last:border-b-0 hover:bg-slate-50 transition-colors"
                    data-product-id="${product.id}">
                <div class="text-[11px] text-slate-800">${escapeHtml(product.name)}</div>
                <div class="text-[9px] text-slate-500 mt-0.5">
                    ${productTypeLabel(product.product_type)}${product.sku ? ` | SKU: ${escapeHtml(product.sku)}` : ''}
                </div>
            </button>
        `).join('');

        suggestionsWrap.classList.remove('hidden');
    }

    function applySelectedProduct(row, product) {
        const productSearch = row.querySelector('.product-search');
        const productIdInput = row.querySelector('.product-id-input');
        const productMeta = row.querySelector('.product-meta');
        const suggestionsWrap = row.querySelector('.product-suggestions');

        productSearch.value = product.name;
        productIdInput.value = product.id;
        productMeta.textContent = `${productTypeLabel(product.product_type)}${product.sku ? ` | SKU: ${product.sku}` : ''}${product.unit ? ` | Unit: ${product.unit}` : ''}`;
        suggestionsWrap.classList.add('hidden');
        checkAvailableStock(row);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Check available stock
    function checkAvailableStock(row) {
        const outletId = fromOutletSelect.value;
        const productId = row.querySelector('.product-id-input').value;
        const availableStockDiv = row.querySelector('.available-stock');

        if (!outletId || !productId) {
            availableStockDiv.textContent = '-';
            return;
        }

        availableStockDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px] text-indigo-400"></i>';

        fetch(`${availableStockBaseUrl}?product_id=${productId}&outlet_id=${outletId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP_${response.status}`);
                }

                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error('INVALID_RESPONSE');
                }

                availableStockDiv.textContent = `${data.available_stock} ${data.unit}`;
                availableStockDiv.classList.remove('text-rose-600');
                availableStockDiv.classList.add('text-slate-800');

                if (data.available_stock <= 0) {
                    availableStockDiv.classList.remove('text-slate-800');
                    availableStockDiv.classList.add('text-rose-600');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (error.message === 'HTTP_403') {
                    availableStockDiv.textContent = 'Akses ditolak';
                } else if (error.message === 'HTTP_404') {
                    availableStockDiv.textContent = 'Route tidak ditemukan';
                } else {
                    availableStockDiv.textContent = 'Error';
                }
                availableStockDiv.classList.remove('text-slate-800');
                availableStockDiv.classList.add('text-rose-600');
            });
    }

    // Refresh stock when from outlet changes
    fromOutletSelect.addEventListener('change', function() {
        document.querySelectorAll('.item-row').forEach(row => {
            checkAvailableStock(row);
        });
    });

    // Form validation
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const fromOutlet = document.getElementById('from_outlet_id').value;
        const toOutlet = document.getElementById('to_outlet_id').value;

        if (fromOutlet && toOutlet && fromOutlet === toOutlet) {
            e.preventDefault();
            alert('Outlet pengirim dan penerima tidak boleh sama!');
            return false;
        }

        const items = document.querySelectorAll('.item-row');
        if (items.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 bahan baku untuk melakukan transfer!');
            return false;
        }

        for (const row of items) {
            const productId = row.querySelector('.product-id-input')?.value;
            const productSearch = row.querySelector('.product-search');
            if (!productId) {
                e.preventDefault();
                alert('Pilih bahan baku dari daftar autocomplete terlebih dahulu.');
                if (productSearch) {
                    productSearch.focus();
                }
                return false;
            }
        }
    });
});
</script>
@endpush
