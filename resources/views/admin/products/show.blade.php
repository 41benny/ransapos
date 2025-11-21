@extends('layouts.admin')

@section('title', 'Detail Produk')
@section('page-title', 'Detail Produk')
@section('page-subtitle', 'Informasi lengkap produk')

@section('content')
<div class="max-w-5xl space-y-6">
    <!-- Info Produk Utama -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">SKU: {{ $product->sku }}</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($product->is_active)
                    <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Aktif</span>
                @else
                    <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Nonaktif</span>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Kolom Kiri -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Kategori</p>
                        <p class="text-sm font-medium text-gray-900">{{ $product->category->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Satuan</p>
                        <p class="text-sm font-medium text-gray-900">{{ $product->unit }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Harga Beli</p>
                        <p class="text-lg font-semibold text-gray-900">Rp {{ number_format($product->purchase_price, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Harga Jual</p>
                        <p class="text-lg font-semibold text-indigo-600">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Deskripsi</p>
                        <p class="text-sm text-gray-900">{{ $product->description ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Stok Minimal</p>
                        <p class="text-sm font-medium text-gray-900">{{ $product->min_stock ?? 0 }} {{ $product->unit }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Dibuat Oleh</p>
                        <p class="text-sm font-medium text-gray-900">{{ $product->creator->name ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Dibuat Pada</p>
                        <p class="text-sm text-gray-900">{{ $product->created_at->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stok per Outlet -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Stok per Outlet</h3>
            <p class="text-sm text-gray-500 mt-1">Ketersediaan stok di setiap outlet</p>
        </div>

        <div class="overflow-x-auto">
            <table class="imperial-table w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Outlet</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stok Tersedia</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Terakhir Dimutasi</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($product->stocks as $stock)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $stock->outlet->name }}</p>
                            <p class="text-xs text-gray-500">{{ $stock->outlet->code }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900">{{ number_format($stock->quantity, 0) }} {{ $product->unit }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600">
                                {{ $stock->last_mutation_at ? $stock->last_mutation_at->format('d M Y H:i') : '-' }}
                            </p>
                        </td>
                        <td class="px-6 py-4">
                            @if($stock->quantity <= 0)
                                <span class="px-3 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Habis</span>
                            @elseif($product->min_stock && $stock->quantity <= $product->min_stock)
                                <span class="px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Menipis</span>
                            @else
                                <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Aman</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="text-gray-500">Belum ada data stok</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a
            href="{{ route('admin.products.index') }}"
            class="px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>

        <a
            href="{{ route('admin.products.edit', $product) }}"
            class="px-5 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Produk
        </a>
    </div>
</div>
@endsection

