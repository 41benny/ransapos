@extends('layouts.admin')

@section('title', 'Buat Transfer Stok')
@section('page-title', 'Buat Transfer Stok')
@section('page-subtitle', 'Buat pengiriman stok barang antar outlet cabang')

@section('content')
<div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Buat Transfer Baru</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Lakukan pemindahan stok produk antar outlet dengan aman</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.stock-transfers.index') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
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

    <form method="POST" action="{{ route('admin.stock-transfers.store') }}" id="transferForm" class="space-y-6">
        @csrf

        {{-- Route Info Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
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
                            class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                            <option value="">Pilih Outlet Pengirim</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('from_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tujuan (To) <span class="text-rose-500">*</span></label>
                        <select name="to_outlet_id" id="to_outlet_id" required
                            class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                            <option value="">Pilih Outlet Penerima</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('to_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tanggal <span class="text-rose-500">*</span></label>
                        <input type="date" name="transfer_date" id="transfer_date" required value="{{ old('transfer_date', date('Y-m-d')) }}"
                            class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>

                    <div class="flex flex-col gap-1.5 lg:col-span-1">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Catatan</label>
                        <input type="text" name="notes" id="notes" value="{{ old('notes') }}" placeholder="Opsional..."
                            class="w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-shopping-basket text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Item Produk yang Di-transfer</h3>
                </div>
                <button type="button" id="addItemBtn"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-[10px] font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                    <i class="fas fa-plus"></i>
                    <span>TAMBAH PRODUK</span>
                </button>
            </div>

            <div class="p-6">
                <div id="itemsContainer" class="space-y-4">
                    {{-- Rows injected by JS --}}
                </div>
            </div>

            <div class="p-6 bg-slate-50/50 border-t border-slate-100 flex justify-end items-center">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.stock-transfers.index') }}" class="text-[11px] font-normal text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest">Batalkan</a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-8 py-3 text-xs font-normal text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95">
                        <i class="fas fa-check text-[10px]"></i>
                        <span>SIMPAN & PROSES TRANSFER</span>
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
    const fromOutletSelect = document.getElementById('from_outlet_id');
    const products = @json($products);

    // Add first item by default
    addItem();

    addItemBtn.addEventListener('click', addItem);

    function addItem() {
        const itemHtml = `
            <div class="group relative bg-white border border-slate-200 rounded-2xl p-4 shadow-sm transition-all hover:shadow-md item-row animate-in zoom-in-95 duration-200" data-index="${itemIndex}">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-end">
                    <div class="md:col-span-5">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Pilih Produk</label>
                        <select name="items[${itemIndex}][product_id]" class="product-select w-full px-4 py-2 text-[11px] font-normal bg-slate-50 border border-slate-100 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                            <option value="">Pilih Produk yang akan dikirim</option>
                            ${products.map(p => `<option value="${p.id}">${p.name} ${p.sku ? '('+p.sku+')' : ''}</option>`).join('')}
                        </select>
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
                               class="quantity-input w-full px-4 py-2 text-[12px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums" placeholder="0.00">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-1 mb-1 block">Catatan Item</label>
                        <input type="text" name="items[${itemIndex}][notes]"
                               class="w-full px-4 py-2 text-[11px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm" placeholder="Catatan kecil...">
                    </div>
                    <div class="md:col-span-1 flex justify-center pb-1">
                        <button type="button" class="remove-item-btn h-9 w-9 inline-flex items-center justify-center bg-white border border-rose-100 text-rose-400 hover:bg-rose-500 hover:text-white hover:border-rose-500 rounded-xl transition-all shadow-sm active:scale-90 p-2">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);

        const newRow = itemsContainer.lastElementChild;
        const productSelect = newRow.querySelector('.product-select');
        const removeBtn = newRow.querySelector('.remove-item-btn');

        // Event listener for product change
        productSelect.addEventListener('change', function() {
            checkAvailableStock(newRow);
        });

        // Event listener for remove button
        removeBtn.addEventListener('click', function() {
            if (itemsContainer.children.length > 1) {
                newRow.classList.add('zoom-out-95', 'opacity-0');
                setTimeout(() => newRow.remove(), 200);
            }
        });

        itemIndex++;
    }

    // Check available stock
    function checkAvailableStock(row) {
        const outletId = fromOutletSelect.value;
        const productId = row.querySelector('.product-select').value;
        const availableStockDiv = row.querySelector('.available-stock');

        if (!outletId || !productId) {
            availableStockDiv.textContent = '-';
            return;
        }

        availableStockDiv.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px] text-indigo-400"></i>';

        fetch(`/admin/stock-transfers/available-stock?product_id=${productId}&outlet_id=${outletId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    availableStockDiv.textContent = `${data.available_stock} ${data.unit}`;
                    availableStockDiv.classList.remove('text-rose-600');
                    availableStockDiv.classList.add('text-slate-800');

                    if (data.available_stock <= 0) {
                        availableStockDiv.classList.remove('text-slate-800');
                        availableStockDiv.classList.add('text-rose-600');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                availableStockDiv.textContent = 'Error';
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
            alert('Minimal harus ada 1 produk untuk melakukan transfer!');
            return false;
        }
    });
});
</script>
@endpush
