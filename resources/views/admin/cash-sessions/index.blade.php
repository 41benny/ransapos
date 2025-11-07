@extends('layouts.admin')

@section('title', 'Riwayat Shift Kasir')
@section('page-title', 'Riwayat Shift Kasir')
@section('page-subtitle', 'Kelola dan monitor semua shift kasir')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    
    <!-- Filter -->
    <div class="p-6 border-b border-gray-100">
        <form method="GET" action="{{ route('admin.cash-sessions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            
            <!-- Outlet Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                <select name="outlet_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                    <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                        {{ $outlet->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kasir</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Kasir</option>
                    @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Semua Status</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Actions -->
            <div class="md:col-span-5 flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.cash-sessions.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Session Number</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Outlet</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Kasir</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Dibuka</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Ditutup</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Saldo Awal</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Total Penjualan</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Selisih</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sessions as $session)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-mono text-gray-900">{{ $session->session_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ $session->outlet->name }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ $session->user->name }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-600">{{ $session->opened_at->format('d M Y, H:i') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($session->closed_at)
                            <span class="text-sm text-gray-600">{{ $session->closed_at->format('d M Y, H:i') }}</span>
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span class="text-sm text-gray-900">Rp {{ number_format($session->opening_balance, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($session->total_sales, 0, ',', '.') }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        @if($session->status === 'closed')
                            @if($session->difference > 0)
                                <span class="text-sm font-semibold text-green-600">+ Rp {{ number_format($session->difference, 0, ',', '.') }}</span>
                            @elseif($session->difference < 0)
                                <span class="text-sm font-semibold text-red-600">- Rp {{ number_format(abs($session->difference), 0, ',', '.') }}</span>
                            @else
                                <span class="text-sm text-gray-600">Rp 0</span>
                            @endif
                        @else
                            <span class="text-sm text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        @if($session->status === 'open')
                            <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Open</span>
                        @else
                            <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Closed</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-gray-500">Tidak ada data shift kasir</p>
                        <p class="text-sm text-gray-400 mt-1">Belum ada shift yang dibuka</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($sessions->hasPages())
    <div class="p-6 border-t border-gray-100">
        {{ $sessions->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

