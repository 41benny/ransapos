@extends('layouts.admin')

@section('title', 'Kartu Stok - ' . $product->name)

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kartu Stok</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $product->name }} - {{ $outlet->name }}</p>
            </div>
            <a href="{{ route('admin.stocks.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>

        <!-- Product Info Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Produk</p>
                    <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                    <p class="text-xs text-gray-500">SKU: {{ $product->sku ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Outlet</p>
                    <p class="font-semibold text-gray-900">{{ $outlet->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Stok Saat Ini</p>
                    <p class="text-2xl font-bold text-indigo-600">
                        {{ number_format($currentStock->quantity ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $product->unit ?? 'pcs' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Nilai Stok (HPP)</p>
                    <p class="text-xl font-bold text-gray-900">
                        Rp {{ number_format(($currentStock->quantity ?? 0) * ($product->purchase_price ?? 0), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Filter Period -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET" action="{{ route('admin.stocks.card') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full border-gray-300 rounded-lg">
                </div>
                <div class="flex items-end gap-2 col-span-2">
                    <button type="submit" class="flex-1 bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">
                        <i class="fas fa-filter mr-2"></i>Filter Periode
                    </button>
                    <a href="{{ route('admin.stocks.card', ['product_id' => $product->id, 'outlet_id' => $outlet->id]) }}"
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Stock Card Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">History Mutasi Stok</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Masuk</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Keluar</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
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
                                <td class="px-6 py-4 text-center">
                                    @if($mutation->mutation_type == 'in')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            In
                                        </span>
                                    @elseif($mutation->mutation_type == 'out')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Out
                                        </span>
                                    @elseif($mutation->mutation_type == 'adjustment')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Adj
                                        </span>
                                    @elseif($mutation->mutation_type == 'transfer_in')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            Trf In
                                        </span>
                                    @elseif($mutation->mutation_type == 'transfer_out')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                            Trf Out
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($mutation->quantity > 0)
                                        <span class="font-semibold text-green-600">
                                            {{ number_format($mutation->quantity, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($mutation->quantity < 0)
                                        <span class="font-semibold text-red-600">
                                            {{ number_format(abs($mutation->quantity), 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">
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
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-history text-4xl mb-3 text-gray-300"></i>
                                    <p>Tidak ada mutasi stok untuk periode ini</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Statistics -->
        @if($mutations->count() > 0)
            @php
                $totalIn = $mutations->where('quantity', '>', 0)->sum('quantity');
                $totalOut = abs($mutations->where('quantity', '<', 0)->sum('quantity'));
                $netChange = $totalIn - $totalOut;
            @endphp
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-green-50 rounded-lg shadow p-4 border border-green-200">
                    <p class="text-sm text-green-700 mb-1">Total Masuk</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($totalIn, 2) }}</p>
                </div>
                <div class="bg-red-50 rounded-lg shadow p-4 border border-red-200">
                    <p class="text-sm text-red-700 mb-1">Total Keluar</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($totalOut, 2) }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg shadow p-4 border border-blue-200">
                    <p class="text-sm text-blue-700 mb-1">Perubahan Bersih</p>
                    <p class="text-2xl font-bold {{ $netChange >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange, 2) }}
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
