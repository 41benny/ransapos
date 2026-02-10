@extends('layouts.admin')

@section('title', 'Manajemen Stok')

@section('content')
    <div class="page-fullwidth px-0">
        <div class="px-6 py-6 page-card-fill">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manajemen Stok</h1>
                    <p class="text-sm text-gray-600 mt-1">Monitoring stok produk per outlet</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.stocks.adjustment') }}"
                        class="btn btn-secondary text-indigo-700 border-indigo-200 hover:bg-indigo-50">
                        <i class="fas fa-adjust"></i>Stock Adjustment
                    </a>
                    <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary">
                        <i class="fas fa-exchange-alt"></i>Transfer Stok
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Produk</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_products'] }}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-box text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Nilai Stok</p>
                            <p class="text-2xl font-bold text-gray-900">Rp
                                {{ number_format($stats['total_value'], 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Stok Minimum</p>
                            <p class="text-2xl font-bold text-yellow-600">{{ $stats['low_stock_count'] }}</p>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Stok Habis</p>
                            <p class="text-2xl font-bold text-red-600">{{ $stats['out_of_stock'] }}</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" action="{{ route('admin.stocks.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                        <select name="outlet_id" class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <select name="category_id" class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari Produk</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau SKU..."
                            class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filter</label>
                        <select name="low_stock" class="w-full h-9 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Semua Stok</option>
                            <option value="1" {{ request('low_stock') == '1' ? 'selected' : '' }}>Stok Minimum</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 btn btn-primary h-9 justify-center">
                            <i class="fas fa-search"></i>Filter
                        </button>
                        <a href="{{ route('admin.stocks.index') }}"
                            class="btn btn-secondary h-9 w-9 justify-center p-0">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Stock Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Saat Ini
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Min</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Nilai (HPP)
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($stocks as $stock)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $stock->product->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $stock->product->sku ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $stock->product->category->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $stock->outlet->name }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span
                                            class="font-semibold text-gray-900">{{ number_format($stock->quantity, 2) }}</span>
                                        <span class="text-xs text-gray-500 ml-1">{{ $stock->product->unit ?? 'pcs' }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-600">
                                        {{ number_format($stock->product->min_stock ?? 0, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                                        Rp
                                        {{ number_format($stock->quantity * ($stock->product->purchase_price ?? 0), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($stock->quantity <= 0)
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                Habis
                                            </span>
                                        @elseif($stock->quantity < ($stock->product->min_stock ?? 0))
                                            <span
                                                class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Minimum
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Normal
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <a href="{{ route('admin.stocks.card', ['product_id' => $stock->product_id, 'outlet_id' => $stock->outlet_id]) }}"
                                            class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            <i class="fas fa-history mr-1"></i>Kartu Stok
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                                        <p>Tidak ada data stok</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($stocks->hasPages())
                    <div class="px-6 py-4 border-t">
                        {{ $stocks->links() }}
                    </div>
                @endif
            </div>

            <!-- Quick Links -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('admin.stocks.mutations') }}"
                    class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">History Mutasi</h3>
                            <p class="text-sm text-gray-600">Lihat riwayat perubahan stok</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.stock-transfers.index') }}"
                    class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-exchange-alt text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Transfer Stok</h3>
                            <p class="text-sm text-gray-600">Transfer antar outlet</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.purchases.index') }}"
                    class="bg-white rounded-lg shadow p-4 hover:shadow-lg transition">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Pembelian</h3>
                            <p class="text-sm text-gray-600">Tambah stok via pembelian</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
@endsection