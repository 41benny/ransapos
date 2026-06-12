@extends('layouts.admin')

@section('title', 'Mapping Packaging Produk')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mapping Produk &rarr; Packaging</h1>
            <p class="text-gray-600 mt-1">Tentukan packaging utama tiap produk untuk menghitung estimasi pemakaian dari penjualan.</p>
        </div>
        <div class="text-sm">
            <span class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 font-medium">
                {{ $unmappedCount }} produk belum dimapping
            </span>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Cari Produk</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nama / SKU"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tampilkan</label>
            <select name="filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
                <option value="">Semua produk</option>
                <option value="unmapped" {{ request('filter') === 'unmapped' ? 'selected' : '' }}>Belum dimapping saja</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm">Filter</button>
        @if(request('q') || request('filter'))
        <a href="{{ route('admin.packaging-mappings.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
        @endif
    </form>

    @foreach($products as $product)
    <form id="map-{{ $product->id }}" action="{{ route('admin.packaging-mappings.update', $product) }}" method="POST" class="hidden">
        @csrf @method('PUT')
    </form>
    @endforeach

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Produk</th>
                    <th class="px-4 py-3 text-left">Kategori</th>
                    <th class="px-4 py-3 text-left">Packaging</th>
                    <th class="px-4 py-3 text-left">Qty / Produk</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                    @php $mapping = $product->packagingMappings->first(); @endphp
                    <tr class="{{ $mapping ? '' : 'bg-amber-50/50' }}">
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-gray-800">{{ $product->name }}</div>
                            <div class="text-xs text-gray-400">{{ $product->sku }}</div>
                        </td>
                        <td class="px-4 py-2.5 text-gray-600">{{ $product->category->name ?? '-' }}</td>
                        <td class="px-4 py-2.5">
                            <select form="map-{{ $product->id }}" name="packaging_item_id"
                                    class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm w-48">
                                <option value="">— Tidak dimapping —</option>
                                @foreach($packagingItems as $item)
                                <option value="{{ $item->id }}" {{ $mapping && $mapping->packaging_item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-2.5">
                            <input type="number" form="map-{{ $product->id }}" name="qty_per_product" min="0.01" step="0.01"
                                   value="{{ $mapping ? (float) $mapping->qty_per_product : 1 }}"
                                   class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-right">
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <button type="submit" form="map-{{ $product->id }}"
                                    class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs">
                                <i class="fas fa-save mr-1"></i> Simpan
                            </button>
                        </td>
                    </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Tidak ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>
@endsection
