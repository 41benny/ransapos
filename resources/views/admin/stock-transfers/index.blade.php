@extends('layouts.admin')

@section('title', 'Transfer Stok Antar Outlet')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Transfer Stok</h1>
            <p class="text-sm text-gray-600 mt-1">Kelola transfer stok antar outlet</p>
        </div>
        <a href="{{ route('admin.stock-transfers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>Buat Transfer Baru
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('admin.stock-transfers.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Outlet</label>
                    <select name="from_outlet_id" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('from_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ke Outlet</label>
                    <select name="to_outlet_id" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" {{ request('to_outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-lg">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_transit" {{ request('status') == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                    <a href="{{ route('admin.stock-transfers.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Transfers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Transfer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ke</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat Oleh</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.stock-transfers.show', $transfer->id) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $transfer->transfer_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $transfer->transfer_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $transfer->fromOutlet->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $transfer->toOutlet->name }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                {{ $transfer->items->count() }} item(s)
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($transfer->status == 'pending')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pending
                                    </span>
                                @elseif($transfer->status == 'in_transit')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        In Transit
                                    </span>
                                @elseif($transfer->status == 'received')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Received
                                    </span>
                                @elseif($transfer->status == 'cancelled')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Cancelled
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $transfer->creator->name ?? '-' }}
                                <div class="text-xs text-gray-400">{{ $transfer->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.stock-transfers.show', $transfer->id) }}" class="text-indigo-600 hover:text-indigo-900 text-sm">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-exchange-alt text-4xl mb-3 text-gray-300"></i>
                                <p>Tidak ada data transfer stok</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($transfers->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
