@extends('layouts.admin')

@section('title', 'Edit Pembelian')
@section('page-title', 'Edit Pembelian')
@section('page-subtitle', 'Perbarui data pesanan pembelian (PO)')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Edit Pembelian</h1>
                <p
                    class="text-[11px] font-mono text-slate-500 mt-1 uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded w-fit border border-slate-200">
                    {{ $purchase->purchase_number }}
                </p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.purchases.show', $purchase) }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Detail</span>
                </a>
            </div>
        </div>

        @if(session('error'))
            <div
                class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-start gap-3 text-rose-600 animate-in slide-in-from-top-2">
                <i class="fas fa-circle-exclamation mt-0.5"></i>
                <div>
                    <p class="text-xs font-normal font-semibold">Gagal memperbarui pembelian</p>
                    <p class="text-[11px] font-normal mt-0.5">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div
                class="mb-6 rounded-xl bg-amber-50 border border-amber-100 p-4 flex flex-col gap-2 text-amber-700 animate-in slide-in-from-top-2 text-xs">
                <div class="flex items-center gap-2 font-normal">
                    <i class="fas fa-circle-exclamation"></i>
                    <span class="font-semibold">Mohon periksa kembali formulir Anda:</span>
                </div>
                <ul class="list-disc list-inside pl-2 space-y-1 font-normal opacity-90">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="purchaseClientDebug"
            class="hidden mb-6 rounded-xl bg-amber-50 border border-amber-100 p-4 text-[11px] font-normal text-amber-700 italic animate-in slide-in-from-top-2">
        </div>

        <form action="{{ route('admin.purchases.update', $purchase) }}" method="POST" id="purchaseForm" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Info Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-500 text-[10px]"></i>
                        <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Informasi
                            Transaksi</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet
                                Tujuan <span class="text-rose-500">*</span></label>
                            <select name="outlet_id" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                <option value="">Pilih outlet...</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ old('outlet_id', $purchase->outlet_id) == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Supplier
                                <span class="text-rose-500">*</span></label>
                            <select name="supplier_id" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                <option value="">Pilih supplier...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tanggal
                                Pembelian <span class="text-rose-500">*</span></label>
                            <input type="date" name="purchase_date"
                                value="{{ old('purchase_date', $purchase->purchase_date->format('Y-m-d')) }}" required
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Item Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shopping-basket text-indigo-500 text-[10px]"></i>
                        <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Daftar
                            Item Pembelian</h3>
                    </div>
                    <button type="button" onclick="addItem()"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-[10px] font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                        <i class="fas fa-plus"></i>
                        <span>TAMBAH ITEM</span>
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200" id="itemsTable">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                    Hubungkan Produk</th>
                                <th
                                    class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 w-28">
                                    Qty</th>
                                <th
                                    class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 w-32">
                                    Harga Satuan</th>
                                <th
                                    class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 w-32">
                                    Diskon (Item)</th>
                                <th
                                    class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400 w-32">
                                    Subtotal</th>
                                <th
                                    class="px-5 py-3 text-center text-[9px] font-normal uppercase tracking-widest text-slate-400 w-16">
                                </th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody" class="divide-y divide-slate-100 bg-white">
                            {{-- Items injected by JS --}}
                        </tbody>
                    </table>
                </div>
                <div class="p-4 bg-slate-50/50 border-t border-slate-100">
                    <p class="text-[9px] text-slate-400 italic font-normal uppercase tracking-wider">* Anda dapat mengubah
                        jumlah atau harga item yang sudah ada.</p>
                </div>
            </div>

            {{-- Summary Grid --}}
            <div class="flex flex-col lg:flex-row gap-6">
                {{-- Notes & Adjustments --}}
                <div class="w-full lg:w-2/3 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Pajak (Tax
                                Rp)</label>
                            <input type="text" name="tax_amount" value="{{ old('tax_amount', $purchase->tax_amount) }}"
                                inputmode="decimal" data-currency-input="1" onchange="calculateTotal()" id="taxAmount"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Diskon
                                Global (Rp)</label>
                            <input type="text" name="discount_amount"
                                value="{{ old('discount_amount', $purchase->discount_amount) }}" inputmode="decimal"
                                data-currency-input="1" onchange="calculateTotal()" id="discountAmount"
                                class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Catatan
                            Tambahan</label>
                        <textarea name="notes" rows="3" placeholder="Contoh: Pembayaran tempo 30 hari..."
                            class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">{{ old('notes', $purchase->notes) }}</textarea>
                    </div>
                </div>

                {{-- Calculations --}}
                <div
                    class="w-full lg:w-1/3 bg-white rounded-2xl shadow-sm border border-slate-200 p-6 overflow-hidden flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-[11px] font-normal text-slate-500">
                            <span class="uppercase tracking-widest">Subtotal Item</span>
                            <span class="text-slate-700 tabular-nums" id="displaySubtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-normal text-slate-500">
                            <span class="uppercase tracking-widest">Pajak (Tax)</span>
                            <span class="text-slate-700 tabular-nums" id="displayTax">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center text-[11px] font-normal text-slate-500">
                            <span class="uppercase tracking-widest">Diskon Global</span>
                            <span class="text-rose-500 tabular-nums" id="displayDiscount">Rp 0</span>
                        </div>
                    </div>
                    <div class="mt-8 pt-6 border-t border-slate-100 flex justify-between items-end">
                        <div class="flex flex-col">
                            <span
                                class="text-[9px] font-normal text-slate-400 uppercase tracking-[0.2em] leading-none mb-1">Total
                                Akhir</span>
                            <span class="text-2xl font-normal text-indigo-600 tracking-tight leading-none tabular-nums"
                                id="displayTotal">Rp 0</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Bar --}}
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.purchases.show', $purchase) }}"
                    class="text-[11px] font-normal text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest mr-2">Batal</a>
                <button type="submit" id="submitPurchaseBtn"
                    class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-10 py-3.5 text-xs font-normal text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95 uppercase tracking-widest">
                    <i class="fas fa-save text-[10px]"></i>
                    <span>Perbarui Data Pembelian</span>
                </button>
            </div>
        </form>
    </div>

    @php
        $existingItemsForEdit = old('items');

        if (!is_array($existingItemsForEdit) || count($existingItemsForEdit) === 0) {
            $existingItemsForEdit = $purchase->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_amount' => (float) $item->discount_amount,
                ];
            })->values()->all();
        } else {
            $existingItemsForEdit = collect($existingItemsForEdit)->map(function ($item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                ];
            })->values()->all();
        }
    @endphp

    <script>
        let itemIndex = 0;
        const products = @json($products);
        const existingItems = @json($existingItemsForEdit);

        const productMasterListId = 'purchaseProductMasterList';
        const normalizedProducts = products.map((product) => {
            const id = String(product.id);
            const name = String(product.name ?? '').trim();
            const sku = String(product.sku ?? '').trim();
            const label = sku ? `${name} (${sku})` : name;

            return {
                id,
                name,
                sku,
                label,
                purchasePrice: Number(product.purchase_price ?? product.cost_price ?? 0),
            };
        });
        const productsById = new Map(normalizedProducts.map((product) => [product.id, product]));
        const productsByLabel = new Map(normalizedProducts.map((product) => [normalizeKeyword(product.label), product]));
        const productsBySku = new Map(
            normalizedProducts
                .filter((product) => product.sku)
                .map((product) => [normalizeKeyword(product.sku), product])
        );

        function normalizeKeyword(value) {
            return String(value ?? '').trim().toLowerCase();
        }

        function parseCurrencyInput(value) {
            const raw = String(value ?? '').trim().replace(/[^\d,.\-]/g, '');
            if (!raw) return 0;

            let normalized = raw;
            const hasComma = normalized.includes(',');
            const dotCount = (normalized.match(/\./g) || []).length;

            if (hasComma) {
                normalized = normalized.replace(/\./g, '').replace(',', '.');
            } else if (dotCount > 0) {
                const dotParts = normalized.split('.');
                const decimalLike = dotCount === 1 && dotParts[1] && dotParts[1].length > 0 && dotParts[1].length <= 2;
                if (!decimalLike) normalized = normalized.replace(/\./g, '');
            }

            normalized = normalized.replace(/(?!^)-/g, '');
            const parsed = Number(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function formatCurrencyInput(value) {
            const numeric = Number(value || 0);
            if (!Number.isFinite(numeric)) return '';
            return numeric.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function bindCurrencyInput(input, onAfterRender = null) {
            if (!input || input.dataset.currencyBound === '1') return;
            input.dataset.currencyBound = '1';
            const render = () => {
                const raw = String(input.value ?? '').trim();
                if (!raw) {
                    if (typeof onAfterRender === 'function') onAfterRender();
                    return;
                }
                input.value = formatCurrencyInput(parseCurrencyInput(raw));
                if (typeof onAfterRender === 'function') onAfterRender();
            };
            input.addEventListener('input', render);
            input.addEventListener('blur', render);
            render();
        }

        function sanitizeCurrencyInputValue(value) {
            const raw = String(value ?? '').trim();
            if (!raw) return '';
            return String(parseCurrencyInput(raw));
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        function mountProductDatalist() {
            if (document.getElementById(productMasterListId)) return;
            const datalist = document.createElement('datalist');
            datalist.id = productMasterListId;
            datalist.innerHTML = normalizedProducts.map((product) => `<option value="${escapeHtml(product.label)}"></option>`).join('');
            document.body.appendChild(datalist);
        }

        function resolveProduct(inputValue, allowPartial = false) {
            const keyword = normalizeKeyword(inputValue);
            if (!keyword) return null;
            const exactLabelMatch = productsByLabel.get(keyword);
            if (exactLabelMatch) return exactLabelMatch;
            const exactSkuMatch = productsBySku.get(keyword);
            if (exactSkuMatch) return exactSkuMatch;
            const exactNameMatches = normalizedProducts.filter((product) => normalizeKeyword(product.name) === keyword);
            if (exactNameMatches.length === 1) return exactNameMatches[0];
            if (!allowPartial) return null;
            const partialMatches = normalizedProducts.filter((product) => {
                const productName = normalizeKeyword(product.name);
                const productSku = normalizeKeyword(product.sku);
                return productName.includes(keyword) || productSku.includes(keyword);
            });
            if (partialMatches.length === 1) return partialMatches[0];
            return null;
        }

        function addItem(initialData = {}) {
            const currentIndex = itemIndex;
            const tbody = document.getElementById('itemsBody');
            const row = document.createElement('tr');
            row.className = 'group hover:bg-slate-50/50 transition-colors';
            row.id = `item-${currentIndex}`;

            const selectedProductId = String(initialData?.product_id ?? '');
            const selectedProduct = productsById.get(selectedProductId);
            const selectedProductLabel = selectedProduct ? selectedProduct.label : (selectedProductId ? `Produk #${selectedProductId}` : '');
            const quantity = initialData?.quantity ?? 1;
            const unitPrice = initialData?.unit_price ?? (selectedProduct?.purchasePrice ?? 0);
            const discountAmount = initialData?.discount_amount ?? 0;

            row.innerHTML = `
                <td class="px-5 py-4">
                    <div class="flex flex-col gap-1.5">
                        <input type="text" id="product-input-${currentIndex}" list="${productMasterListId}"
                               value="${escapeHtml(selectedProductLabel)}"
                               placeholder="Ketik nama atau SKU..."
                               oninput="syncProductInput(${currentIndex})"
                               onchange="syncProductInput(${currentIndex}, true)"
                               class="w-full px-4 py-2 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                        <input type="hidden" name="items[${currentIndex}][product_id]" id="product-id-${currentIndex}" value="${escapeHtml(selectedProductId)}">
                    </div>
                </td>
                <td class="px-5 py-4">
                    <input type="number" name="items[${currentIndex}][quantity]" value="${quantity}" min="0.01" step="0.01" required
                           onchange="calculateItemSubtotal(${currentIndex})"
                           class="w-full h-10 px-3 text-right text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums" id="qty-${currentIndex}">
                </td>
                <td class="px-5 py-4">
                    <input type="text" name="items[${currentIndex}][unit_price]" value="${unitPrice}" inputmode="decimal" data-currency-input="1" required
                           onchange="calculateItemSubtotal(${currentIndex})"
                           class="w-full h-10 px-3 text-right text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums" id="price-${currentIndex}">
                </td>
                <td class="px-5 py-4">
                    <input type="text" name="items[${currentIndex}][discount_amount]" value="${discountAmount}" inputmode="decimal" data-currency-input="1"
                           onchange="calculateItemSubtotal(${currentIndex})"
                           class="w-full h-10 px-3 text-right text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums" id="discount-${currentIndex}">
                </td>
                <td class="px-5 py-4 text-right">
                    <span class="text-[12px] font-normal text-slate-800 tabular-nums" id="subtotal-${currentIndex}">Rp 0</span>
                </td>
                <td class="px-5 py-4 text-center">
                    <button type="button" onclick="removeItem(${currentIndex})"
                            class="h-8 w-8 inline-flex items-center justify-center bg-white border border-rose-100 text-rose-400 hover:bg-rose-500 hover:text-white hover:border-rose-500 rounded-xl transition-all shadow-sm active:scale-95">
                        <i class="fas fa-times text-[10px]"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(row);
            bindCurrencyInput(document.getElementById(`price-${currentIndex}`), () => calculateItemSubtotal(currentIndex));
            bindCurrencyInput(document.getElementById(`discount-${currentIndex}`), () => calculateItemSubtotal(currentIndex));
            if (selectedProductId) syncProductInput(currentIndex, true);
            calculateItemSubtotal(currentIndex);
            itemIndex++;
        }

        function syncProductInput(index, allowPartial = false) {
            const textInput = document.getElementById(`product-input-${index}`);
            const productIdInput = document.getElementById(`product-id-${index}`);
            const priceInput = document.getElementById(`price-${index}`);
            if (!textInput || !productIdInput) return;

            const previousProductId = productIdInput.value;
            const selectedProduct = resolveProduct(textInput.value, allowPartial);

            if (!selectedProduct) {
                productIdInput.value = '';
                if (allowPartial && normalizeKeyword(textInput.value)) textInput.classList.add('border-amber-400');
                else textInput.classList.remove('border-amber-400');
                return;
            }

            textInput.classList.remove('border-amber-400');
            textInput.value = selectedProduct.label;
            productIdInput.value = selectedProduct.id;
            if (priceInput && previousProductId !== selectedProduct.id) priceInput.value = formatCurrencyInput(selectedProduct.purchasePrice);
            calculateItemSubtotal(index);
        }

        function calculateItemSubtotal(index) {
            const qty = parseFloat(document.getElementById(`qty-${index}`).value) || 0;
            const price = parseCurrencyInput(document.getElementById(`price-${index}`).value);
            const discount = parseCurrencyInput(document.getElementById(`discount-${index}`).value);
            const subtotal = (qty * price) - discount;
            document.getElementById(`subtotal-${index}`).textContent = formatRupiah(subtotal);
            calculateTotal();
        }

        function removeItem(index) {
            const itemRow = document.getElementById(`item-${index}`);
            if (itemRow) itemRow.remove();
            calculateTotal();
        }

        function calculateTotal() {
            let subtotal = 0;
            const tbody = document.getElementById('itemsBody');
            const rows = tbody.getElementsByTagName('tr');
            for (let row of rows) {
                const qtyInput = row.querySelector('input[name*="[quantity]"]');
                const priceInput = row.querySelector('input[name*="[unit_price]"]');
                const discountInput = row.querySelector('input[name*="[discount_amount]"]');
                if (qtyInput && priceInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseCurrencyInput(priceInput.value);
                    const discount = parseCurrencyInput(discountInput.value);
                    subtotal += (qty * price) - discount;
                }
            }
            const tax = parseCurrencyInput(document.getElementById('taxAmount').value);
            const discount = parseCurrencyInput(document.getElementById('discountAmount').value);
            const total = subtotal + tax - discount;
            document.getElementById('displaySubtotal').textContent = formatRupiah(subtotal);
            document.getElementById('displayTax').textContent = formatRupiah(tax);
            document.getElementById('displayDiscount').textContent = formatRupiah(discount);
            document.getElementById('displayTotal').textContent = formatRupiah(total);
        }

        function formatRupiah(amount) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
        }

        function showClientDebug(message) {
            const debugBox = document.getElementById('purchaseClientDebug');
            if (!debugBox) return;
            debugBox.textContent = message;
            debugBox.classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('purchaseForm');
            const submitButton = document.getElementById('submitPurchaseBtn');
            mountProductDatalist();

            if (Array.isArray(existingItems) && existingItems.length > 0) {
                existingItems.forEach((item) => addItem(item));
            } else {
                addItem();
            }

            calculateTotal();
            bindCurrencyInput(document.getElementById('taxAmount'), calculateTotal);
            bindCurrencyInput(document.getElementById('discountAmount'), calculateTotal);

            form.addEventListener('submit', function (event) {
                const invalidInput = form.querySelector(':invalid');
                if (invalidInput) {
                    event.preventDefault();
                    showClientDebug('Form belum valid. Cek field wajib yang disorot browser.');
                    invalidInput.reportValidity();
                    invalidInput.focus();
                    return;
                }
                const productIdInputs = Array.from(document.querySelectorAll('#itemsBody input[name*="[product_id]"]'));
                const selectedProducts = productIdInputs.filter((inputEl) => inputEl.value);
                if (selectedProducts.length < 1) {
                    event.preventDefault();
                    showClientDebug('Minimal pilih 1 produk sebelum menyimpan.');
                    return;
                }
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-60', 'cursor-not-allowed');
                    submitButton.textContent = 'Menyimpan...';
                }
                form.querySelectorAll('[data-currency-input="1"]').forEach((input) => {
                    input.value = sanitizeCurrencyInputValue(input.value);
                });
            });
        });
    </script>
@endsection