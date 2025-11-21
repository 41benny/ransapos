@extends('layouts.pos')

@section('title', 'POS Dashboard')
@section('page-title', 'Dashboard Kasir')

@section('content')
<div class="h-full p-6">
    
    <div class="flex items-center justify-end mb-4">
        @if(auth()->user()->hasRole(['admin', 'manager', 'kitchen']))
        <a href="{{ route('pos.kitchen.index') }}"
           class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-sm rounded-lg text-white flex items-center gap-2 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8c.917-1.833 2.75-3 5-3a4 4 0 010 8h-1m-4-5c-.917-1.833-2.75-3-5-3a4 4 0 000 8h1m3 5v-6" />
            </svg>
            <span>Kitchen Display</span>
        </a>
        @endif
    </div>
    
    @if($activeSession)
    <!-- Active Session Info -->
    <div class="bg-green-800 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-200 text-sm mb-1">Sesi Aktif</p>
                <h3 class="text-2xl font-bold text-white">{{ $activeSession->session_number }}</h3>
                <p class="text-green-200 text-sm mt-2">
                    Dibuka: {{ $activeSession->opened_at->format('d M Y, H:i') }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-green-200 text-sm mb-1">Saldo Awal</p>
                <p class="text-2xl font-bold text-white">Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}</p>
            </div>
            <a href="{{ route('pos.sessions.close') }}" 
               class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">
                Tutup Shift
            </a>
        </div>
    </div>

    <!-- Today's Sales -->
    @if($todaySales && $todaySales->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-gray-800 rounded-lg p-6">
            <p class="text-gray-400 text-sm mb-2">Total Transaksi</p>
            <p class="text-3xl font-bold text-white">{{ $todaySales->count() }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6">
            <p class="text-gray-400 text-sm mb-2">Total Penjualan</p>
            <p class="text-3xl font-bold text-white">Rp {{ number_format($todaySales->sum('total_amount'), 0, ',', '.') }}</p>
        </div>
        <div class="bg-gray-800 rounded-lg p-6">
            <p class="text-gray-400 text-sm mb-2">Rata-rata Transaksi</p>
            <p class="text-3xl font-bold text-white">Rp {{ number_format($todaySales->avg('total_amount'), 0, ',', '.') }}</p>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="bg-gray-800 rounded-lg">
        <div class="p-6 border-b border-gray-700">
            <h3 class="text-xl font-semibold text-white">Transaksi Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Pelanggan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Waktu</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-300 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($todaySales->take(10) as $sale)
                    <tr class="hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm font-mono text-white">{{ $sale->invoice_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-300">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                        <td class="px-6 py-4 text-sm font-semibold text-white">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-300">{{ $sale->created_at->format('H:i') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 text-xs font-medium bg-green-900 text-green-300 rounded-full">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="bg-gray-800 rounded-lg p-12 text-center">
        <svg class="w-16 h-16 text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-gray-400 mb-2">Belum ada transaksi hari ini</p>
        <a href="{{ route('pos.sales.create') }}" 
           class="inline-block px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition mt-4">
            Mulai Transaksi
        </a>
    </div>
    @endif

    @else
    <!-- No Active Session -->
    <div class="flex items-center justify-center h-full">
        <div class="text-center max-w-md">
            <div class="w-24 h-24 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-3">Tidak Ada Sesi Aktif</h2>
            <p class="text-gray-400 mb-8">Buka shift kasir untuk mulai melayani transaksi</p>
            <a href="{{ route('pos.sessions.open') }}" 
               class="inline-block px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold text-lg transition">
                Buka Shift Kasir
            </a>
        </div>
    </div>
    @endif

    <!-- Quick Action Button -->
    @if($activeSession)
    <a href="{{ route('pos.sales.create') }}" 
       class="fixed bottom-8 right-8 w-16 h-16 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </a>
    @endif
</div>
@endsection

