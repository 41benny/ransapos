@extends('layouts.admin')

@section('title', 'Input Penjualan Backdate')
@section('page-title', 'Input Penjualan Backdate')
@section('page-subtitle', 'Input transaksi manual dari admin tanpa mengubah alur POS kasir')

@section('content')
@php
    $editingSale = $editingSale ?? null;
    $isEditing = (bool) $editingSale;
    $primaryPayment = $editingSale?->payments?->first();
    $formAction = $isEditing ? route('admin.backdate-sales.update', $editingSale) : route('admin.backdate-sales.store');
    $formItems = old('items');
    if ($formItems === null && $editingSale) {
        $formItems = $editingSale->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'discount_amount' => (float) $item->discount_amount,
            'notes' => $item->notes,
        ])->values()->all();
    }
    $productPriceMap = $products->mapWithKeys(function ($product) {
        return [
            (string) $product->id => [
                'id' => $product->id,
                'selling_price' => (float) $product->selling_price,
                'price_levels' => $product->price_levels ?? [],
            ],
        ];
    })->all();
@endphp
<div class="page-fullwidth space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <p class="font-semibold mb-2">Validasi gagal:</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 2xl:grid-cols-3 gap-6">
        <div class="2xl:col-span-2 ui-card bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-900">Form Manual</h2>
                <p class="text-sm text-slate-500 mt-1">Batas tanggal mundur maksimal {{ $maxBackdateDays }} hari.</p>
            </div>

            @if($isEditing)
                <div class="mx-6 mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Mode edit invoice <span class="font-semibold">{{ $editingSale->invoice_number }}</span>. Harga akan dihitung ulang dari metode penjualan dan outlet yang dipilih.
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}" class="p-6 space-y-6">
                @csrf
                @if($isEditing)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Manual</label>
                        <input name="manual_reference" value="{{ old('manual_reference', $editingSale?->manual_reference) }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="MBK2-20260503-001" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Penjualan</label>
                        <input type="date" name="sale_date" value="{{ old('sale_date', $editingSale?->sale_date?->toDateString() ?? $defaultSaleDate) }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Outlet</label>
                        <select name="outlet_id" id="outlet_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $editingSale?->outlet_id) == $outlet->id)>{{ $outlet->code }} - {{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Penjualan</label>
                        <select name="sales_type" id="sales_type" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            @foreach($salesTypes as $code => $label)
                                <option value="{{ $code }}" @selected(old('sales_type', $editingSale?->sales_type ?? 'regular') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Bayar</label>
                        <select name="payment_method_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            <option value="">Pilih Metode</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}" @selected(old('payment_method_id', $primaryPayment?->payment_method_id) == $method->id)>{{ $method->code }} - {{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Bayar Catatan</label>
                        <input type="number" name="payment_amount" value="{{ old('payment_amount', $editingSale?->total_amount ?? 0) }}" min="0" step="0.01" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Referensi Bayar</label>
                        <input name="payment_reference" value="{{ old('payment_reference', $primaryPayment?->reference_number) }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Opsional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Customer Terdaftar</label>
                        <select name="customer_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Walk-in / tidak pilih</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id', $editingSale?->customer_id) == $customer->id)>{{ $customer->customer_code }} - {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Customer Manual</label>
                        <input name="customer_name" value="{{ old('customer_name', $editingSale?->customer_name) }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Opsional">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="block text-sm font-medium text-slate-700">Item Produk</label>
                        <button type="button" id="addItemRow" class="ui-btn ui-btn-ghost rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Tambah Item
                        </button>
                    </div>
                    <div id="itemRows" class="space-y-3"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Catatan Transaksi</label>
                        <textarea name="notes" rows="3" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes', $editingSale?->notes) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Backdate</label>
                        <textarea name="backdate_reason" rows="3" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>{{ old('backdate_reason', $editingSale?->backdate_reason) }}</textarea>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <p class="text-slate-500">Subtotal Item</p>
                            <p class="font-semibold text-slate-900" id="summarySubtotal">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Diskon Item</p>
                            <p class="font-semibold text-slate-900" id="summaryDiscount">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Total Form</p>
                            <p class="text-lg font-bold text-indigo-600" id="summaryTotal">Rp 0</p>
                        </div>
                        <div>
                            <p class="text-slate-500">Jumlah Item</p>
                            <p class="font-semibold text-slate-900" id="summaryItemCount">0</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    @if($isEditing)
                        <a href="{{ route('admin.backdate-sales.index') }}" class="ui-btn ui-btn-ghost rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal Edit</a>
                    @endif
                    <button type="submit" class="ui-btn ui-btn-primary rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        {{ $isEditing ? 'Update Penjualan Backdate' : 'Simpan Penjualan Backdate' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
        <div class="ui-card bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-900">Import Excel</h2>
                <p class="text-sm text-slate-500 mt-1">Upload file untuk preview validasi sebelum disimpan.</p>
            </div>
            <form method="POST" action="{{ route('admin.backdate-sales.import.preview') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">File Excel</label>
                    <input type="file" name="file" accept=".xlsx,.xls" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                </div>
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600 space-y-2">
                    <p>Format utama: 1 baris per item, digabung dengan <span class="font-mono">kode_transaksi_manual</span>.</p>
                    <a href="{{ route('admin.backdate-sales.template') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">Download template Excel</a>
                </div>
                <button type="submit" class="ui-btn ui-btn-primary w-full rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    Upload & Preview
                </button>
            </form>
        </div>
        <div class="ui-card bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-base font-semibold text-slate-900">Transaksi Backdate</h2>
                <p class="text-sm text-slate-500 mt-1">30 transaksi terakhir yang bisa diedit.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($backdatedSales as $sale)
                    <div class="px-6 py-4 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $sale->manual_reference ?: $sale->invoice_number }}</p>
                                <p class="text-xs text-slate-500">{{ $sale->sale_date?->format('d/m/Y') }} &middot; {{ $sale->outlet?->code }} &middot; {{ strtoupper(str_replace('_', ' ', $sale->sales_type)) }}</p>
                                <p class="mt-1 font-semibold text-indigo-600">Rp {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</p>
                            </div>
                            <a href="{{ route('admin.backdate-sales.edit', $sale) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-5 text-sm text-slate-500">Belum ada transaksi backdate.</div>
                @endforelse
            </div>
        </div>
        </div>
    </div>
</div>

<template id="item-row-template">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 item-row">
        <div class="lg:col-span-5">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Produk</label>
            <select name="items[__INDEX__][product_id]" class="ui-input product-select w-full rounded-lg border border-slate-300 px-3 py-2" required>
                <option value="">Pilih Produk</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ (float) $product->selling_price }}">{{ $product->sku }} - {{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-1">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Qty</label>
            <input type="number" name="items[__INDEX__][quantity]" min="0.01" step="0.01" value="1" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Qty" required>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Harga</label>
            <input type="number" name="items[__INDEX__][unit_price]" min="0" step="0.01" class="ui-input price-input w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2" placeholder="Harga" readonly required>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Diskon</label>
            <input type="number" name="items[__INDEX__][discount_amount]" min="0" step="0.01" value="0" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Diskon">
        </div>
        <div class="lg:col-span-1">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Catatan</label>
            <input name="items[__INDEX__][notes]" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Catatan">
        </div>
        <div class="lg:col-span-1 flex items-end justify-end">
            <button type="button" class="remove-row w-full rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">Hapus</button>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.getElementById('itemRows');
    const template = document.getElementById('item-row-template').innerHTML.trim();
    const outletSelect = document.getElementById('outlet_id');
    const salesTypeSelect = document.getElementById('sales_type');
    const initialItems = @json($formItems ?? []);
    const productMap = @json($productPriceMap);
    let index = 0;

    function formatRupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function resolveProductPrice(productId) {
        const product = productMap[productId];
        if (!product) {
            return 0;
        }

        const level = salesTypeSelect.value || 'regular';
        const outletId = outletSelect.value ? String(outletSelect.value) : null;
        const priceLevels = product.price_levels || {};
        const priceLevelKey = Object.keys(priceLevels).find((key) => String(key).toLowerCase() === String(level).toLowerCase());
        const levelData = priceLevelKey ? priceLevels[priceLevelKey] : null;

        if (typeof levelData === 'number' || typeof levelData === 'string') {
            return Number(levelData || 0);
        }

        if (levelData && typeof levelData === 'object') {
            if (outletId && levelData.outlets && levelData.outlets[outletId] && Number(levelData.outlets[outletId]) > 0) {
                return Number(levelData.outlets[outletId]);
            }
            if (levelData.default !== undefined && levelData.default !== null) {
                return Number(levelData.default || 0);
            }
        }

        const regularKey = Object.keys(priceLevels).find((key) => String(key).toLowerCase() === 'regular');
        const regularData = regularKey ? priceLevels[regularKey] : null;
        if (typeof regularData === 'number' || typeof regularData === 'string') {
            return Number(regularData || 0);
        }
        if (regularData && typeof regularData === 'object' && regularData.default !== undefined) {
            return Number(regularData.default || 0);
        }

        return Number(product.selling_price || 0);
    }

    function updateRowPrice(row) {
        const productSelect = row.querySelector('.product-select');
        const priceInput = row.querySelector('.price-input');
        priceInput.value = resolveProductPrice(productSelect.value);
    }

    function updateAllPrices() {
        rows.querySelectorAll('.item-row').forEach(updateRowPrice);
        updateSummary();
    }

    function updateSummary() {
        let gross = 0;
        let discount = 0;
        let itemCount = 0;

        rows.querySelectorAll('.item-row').forEach(function (row) {
            const productId = row.querySelector('.product-select').value;
            const qty = Number(row.querySelector('input[name$="[quantity]"]').value || 0);
            const price = Number(row.querySelector('.price-input').value || 0);
            const rowDiscount = Number(row.querySelector('input[name$="[discount_amount]"]').value || 0);

            if (productId && qty > 0) {
                itemCount += 1;
            }

            gross += qty * price;
            discount += Math.min(qty * price, Math.max(0, rowDiscount));
        });

        document.getElementById('summarySubtotal').textContent = formatRupiah(gross);
        document.getElementById('summaryDiscount').textContent = formatRupiah(discount);
        document.getElementById('summaryTotal').textContent = formatRupiah(Math.max(0, gross - discount));
        document.getElementById('summaryItemCount').textContent = itemCount;
    }

    function addRow(data = {}) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.replaceAll('__INDEX__', String(index++));
        const row = wrapper.firstElementChild;
        rows.appendChild(row);
        row.querySelector('.product-select').value = data.product_id || '';
        row.querySelector('input[name$="[quantity]"]').value = data.quantity || 1;
        row.querySelector('input[name$="[discount_amount]"]').value = data.discount_amount || 0;
        row.querySelector('input[name$="[notes]"]').value = data.notes || '';
        updateRowPrice(row);
        updateSummary();
    }

    rows.addEventListener('change', function (event) {
        if (event.target.classList.contains('product-select')) {
            updateRowPrice(event.target.closest('.item-row'));
        }

        updateSummary();
    });

    rows.addEventListener('input', function () {
        updateSummary();
    });

    rows.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-row')) {
            return;
        }

        if (rows.children.length > 1) {
            event.target.closest('.item-row').remove();
            updateSummary();
        }
    });

    document.getElementById('addItemRow').addEventListener('click', addRow);
    outletSelect.addEventListener('change', updateAllPrices);
    salesTypeSelect.addEventListener('change', updateAllPrices);

    if (initialItems.length > 0) {
        initialItems.forEach(addRow);
    } else {
        addRow();
    }
});
</script>
@endsection
