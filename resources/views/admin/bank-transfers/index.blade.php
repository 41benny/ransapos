@extends('layouts.admin')

@section('title', 'Daftar Transfer Bank')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Daftar Transfer Bank</h1>
            <a href="{{ route('admin.bank-transfers.create') }}"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                + Transfer Baru
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No Transfer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ke</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transfers as $transfer)
                        <tr>
                            <td class="px-6 py-4 text-sm">{{ $transfer->transfer_number }}</td>
                            <td class="px-6 py-4 text-sm">{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <div>{{ $transfer->fromAccount->name }}</div>
                                <div class="text-xs text-gray-500">{{ $transfer->fromAccount->outlet->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div>{{ $transfer->toAccount->name }}</div>
                                <div class="text-xs text-gray-500">{{ $transfer->toAccount->outlet->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">Rp {{ number_format($transfer->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <a href="{{ route('admin.bank-transfers.show', $transfer) }}"
                                    class="text-indigo-600 hover:text-indigo-900">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Belum ada transfer yang tercatat
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($transfers->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection