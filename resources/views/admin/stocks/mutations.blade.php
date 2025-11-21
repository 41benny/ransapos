@extends('layouts.admin')

@section('title', 'History Mutasi Stok')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">History Mutasi Stok</h1>
            <p class="text-sm text-gray-600 mt-1">Audit trail perubahan stok</p>
        </div>
        <a href="{{ route('admin.stocks.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-arrow-left mr-2"></i>Kembali ke Stok
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.stocks.mutations') }}">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                    <select name="outlet_id" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Mutasi</label>
                    <select name="mutation_type" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua Tipe</option>
                        <option value="in" {{ request('mutation_type') == 'in' ? 'selected' : '' }}>Masuk (In)</option>
                        <option value="out" {{ request('mutation_type') == 'out' ? 'selected' : '' }}>Keluar (Out)</option>
                        <option value="adjustment" {{ request('mutation_type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        <option value="transfer_in" {{ request('mutation_type') == 'transfer_in' ? 'selected' : '' }}>Transfer In</option>
                        <option value="transfer_out" {{ request('mutation_type') == 'transfer_out' ? 'selected' : '' }}>Transfer Out</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Referensi</label>
                    <select name="reference_type" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua</option>
                        <option value="purchase" {{ request('reference_type') == 'purchase' ? 'selected' : '' }}>Pembelian</option>
                        <option value="sale" {{ request('reference_type') == 'sale' ? 'selected' : '' }}>Penjualan</option>
                        <option value="stock_opname" {{ request('reference_type') == 'stock_opname' ? 'selected' : '' }}>Opname</option>
                        <option value="stock_transfer" {{ request('reference_type') == 'stock_transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full border-gray-300 rounded-lg">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                    <a href="{{ route('admin.stocks.mutations') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>

            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Cari Produk</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama produk..." class="w-full border-gray-300 rounded-lg">
            </div>
        </form>
    </div>

    <!-- Mutations Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Outlet</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipe</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Sebelum</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Stok Sesudah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referensi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($mutations as $mutation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $mutation->mutation_date->format('d/m/Y') }}
                                <div class="text-xs text-gray-500">{{ $mutation->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $mutation->product->name }}</div>
                                <div class="text-xs text-gray-500">{{ $mutation->product->sku ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $mutation->outlet->name }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($mutation->mutation_type == 'in')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Masuk
                                    </span>
                                @elseif($mutation->mutation_type == 'out')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Keluar
                                    </span>
                                @elseif($mutation->mutation_type == 'adjustment')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Adjustment
                                    </span>
                                @elseif($mutation->mutation_type == 'transfer_in')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Transfer In
                                    </span>
                                @elseif($mutation->mutation_type == 'transfer_out')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Transfer Out
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-semibold {{ $mutation->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $mutation->quantity >= 0 ? '+' : '' }}{{ number_format($mutation->quantity, 2) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-600">
                                {{ number_format($mutation->stock_before, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                {{ number_format($mutation->stock_after, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ ucfirst(str_replace('_', ' ', $mutation->reference_type ?? '-')) }}
                                @if($mutation->reference_id)
                                    <div class="text-xs text-gray-400">#{{ $mutation->reference_id }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $mutation->notes ?? '-' }}
                                @if($mutation->creator)
                                    <div class="text-xs text-gray-400 mt-1">oleh {{ $mutation->creator->name }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-history text-4xl mb-3 text-gray-300"></i>
                                <p>Tidak ada data mutasi stok</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($mutations->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $mutations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
