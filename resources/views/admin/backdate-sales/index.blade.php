@extends('layouts.admin')

@section('title', 'Input Penjualan Backdate')
@section('page-title', 'Input Penjualan Backdate')
@section('page-subtitle', 'Input transaksi manual dari admin tanpa mengubah alur POS kasir')

@section('content')
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

            <form method="POST" action="{{ route('admin.backdate-sales.store') }}" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kode Manual</label>
                        <input name="manual_reference" value="{{ old('manual_reference') }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="MBK2-20260503-001" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Penjualan</label>
                        <input type="date" name="sale_date" value="{{ old('sale_date', $defaultSaleDate) }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Outlet</label>
                        <select name="outlet_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->code }} - {{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Bayar</label>
                        <select name="payment_method_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>
                            <option value="">Pilih Metode</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}" @selected(old('payment_method_id') == $method->id)>{{ $method->code }} - {{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jumlah Bayar Catatan</label>
                        <input type="number" name="payment_amount" value="{{ old('payment_amount', 0) }}" min="0" step="0.01" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Referensi Bayar</label>
                        <input name="payment_reference" value="{{ old('payment_reference') }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Opsional">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Customer Terdaftar</label>
                        <select name="customer_id" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="">Walk-in / tidak pilih</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->customer_code }} - {{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nama Customer Manual</label>
                        <input name="customer_name" value="{{ old('customer_name') }}" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Opsional">
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
                        <textarea name="notes" rows="3" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('notes') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Alasan Backdate</label>
                        <textarea name="backdate_reason" rows="3" class="ui-input w-full rounded-lg border border-slate-300 px-3 py-2" required>{{ old('backdate_reason') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="ui-btn ui-btn-primary rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                        Simpan Penjualan Backdate
                    </button>
                </div>
            </form>
        </div>

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
            <input type="number" name="items[__INDEX__][unit_price]" min="0" step="0.01" class="ui-input price-input w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Harga" required>
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
    let index = 0;

    function addRow() {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.replaceAll('__INDEX__', String(index++));
        const row = wrapper.firstElementChild;
        rows.appendChild(row);
    }

    rows.addEventListener('change', function (event) {
        if (!event.target.classList.contains('product-select')) {
            return;
        }

        const option = event.target.selectedOptions[0];
        const row = event.target.closest('.item-row');
        const priceInput = row.querySelector('.price-input');
        if (option && option.dataset.price && !priceInput.value) {
            priceInput.value = option.dataset.price;
        }
    });

    rows.addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-row')) {
            return;
        }

        if (rows.children.length > 1) {
            event.target.closest('.item-row').remove();
        }
    });

    document.getElementById('addItemRow').addEventListener('click', addRow);
    addRow();
});
</script>
@endsection
