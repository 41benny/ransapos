@extends('layouts.admin')

@section('title', 'Approval Adjustment Packaging')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Approval Adjustment Packaging</h1>
            <p class="text-gray-600 mt-1">Setujui atau tolak request adjustment packaging dari outlet.</p>
        </div>
        <span class="px-3 py-1.5 rounded-lg bg-amber-100 text-amber-700 font-medium text-sm">
            {{ $pendingCount }} menunggu approval
        </span>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Semua</option>
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $v)
                <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tipe</label>
            <select name="type" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Semua</option>
                <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>Masuk</option>
                <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Keluar</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Outlet</label>
            <select name="outlet_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Semua</option>
                @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Item</label>
            <select name="packaging_item_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Semua</option>
                @foreach($packagingItems as $item)
                <option value="{{ $item->id }}" {{ request('packaging_item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm">Filter</button>
        <a href="{{ route('admin.packaging-adjustments.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
    </form>

    @if($defaultPending)
    <p class="text-xs text-gray-500 mb-3"><i class="fas fa-info-circle mr-1"></i> Menampilkan adjustment <strong>pending</strong> secara default. Gunakan filter untuk melihat semua.</p>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Outlet</th>
                    <th class="px-4 py-3 text-left">Shift</th>
                    <th class="px-4 py-3 text-left">Kasir</th>
                    <th class="px-4 py-3 text-center">Tipe</th>
                    <th class="px-4 py-3 text-left">Item</th>
                    <th class="px-4 py-3 text-right">Qty</th>
                    <th class="px-4 py-3 text-left">Alasan / Catatan</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($adjustments as $adj)
                <tr>
                    <td class="px-4 py-2.5 text-gray-500">{{ $adj->created_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-2.5 text-gray-700">{{ $adj->outlet->name ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $adj->cashSession->session_number ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-gray-700">{{ $adj->requestedBy->name ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-[11px] {{ $adj->type === 'in' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $adj->type === 'in' ? 'Masuk' : 'Keluar' }}
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-gray-800 font-medium">{{ $adj->packagingItem->name ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-right font-mono">{{ (float) $adj->qty }}</td>
                    <td class="px-4 py-2.5 text-gray-600">
                        {{ $adj->reason }}
                        @if($adj->note)<div class="text-xs text-gray-400">{{ $adj->note }}</div>@endif
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        @php
                            $badge = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'approved' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                            ][$adj->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-[11px] {{ $badge }}">{{ ucfirst($adj->status) }}</span>
                        @if($adj->status === 'approved' && $adj->approvedBy)
                            <div class="text-[10px] text-gray-400 mt-1">oleh {{ $adj->approvedBy->name }}</div>
                        @elseif($adj->status === 'rejected' && $adj->rejectedBy)
                            <div class="text-[10px] text-gray-400 mt-1">oleh {{ $adj->rejectedBy->name }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-2.5 text-center">
                        @if($adj->status === 'pending')
                        <div class="flex items-center justify-center gap-2">
                            @if(auth()->user()->hasPermission('packaging-adjustments.approve'))
                            <form action="{{ route('admin.packaging-adjustments.approve', $adj) }}" method="POST" onsubmit="return confirm('Setujui adjustment ini?')">
                                @csrf @method('PUT')
                                <button class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs"><i class="fas fa-check"></i></button>
                            </form>
                            @endif
                            @if(auth()->user()->hasPermission('packaging-adjustments.reject'))
                            <form action="{{ route('admin.packaging-adjustments.reject', $adj) }}" method="POST" onsubmit="return confirm('Tolak adjustment ini?')">
                                @csrf @method('PUT')
                                <button class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs"><i class="fas fa-times"></i></button>
                            </form>
                            @endif
                        </div>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">Tidak ada adjustment.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $adjustments->links() }}</div>
</div>
@endsection
