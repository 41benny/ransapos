@extends('layouts.admin')

@section('title', 'Perangkat POS')
@section('page-title', 'Perangkat POS')
@section('page-subtitle', 'Batasi akses POS per perangkat')

@section('content')
    <div class="w-full max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

            @if(session('success'))
                <div class="p-6">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle text-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="p-6">
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if(session('pairing_code'))
                <div class="px-6">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p class="text-sm font-medium text-amber-900">Kode Pairing</p>
                                <p class="text-xs text-amber-700">Masukkan kode ini di tablet kasir.</p>
                            </div>
                            <div class="text-2xl font-bold tracking-widest text-amber-900">
                                {{ session('pairing_code') }}
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-amber-700">Kode berlaku {{ config('pos.pairing_ttl_minutes', 15) }} menit.</p>
                    </div>
                </div>
            @endif

            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Kelola Perangkat POS</h3>
                        <p class="text-sm text-gray-500 mt-1">Buat kode pairing untuk tablet kasir per outlet.</p>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Kontrol Fitur Perangkat</h4>
                        <p class="text-xs text-gray-500 mt-1">Jika nonaktif, POS bisa dibuka dari perangkat mana saja.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="px-2 py-1 text-xs rounded-full {{ $deviceEnforced ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                            {{ $deviceEnforced ? 'Aktif' : 'Nonaktif' }}
                        </span>
                        <form method="POST" action="{{ route('admin.pos-devices.enforce') }}">
                            @csrf
                            <input type="hidden" name="enabled" value="{{ $deviceEnforced ? 0 : 1 }}">
                            <button type="submit"
                                class="px-3 py-2 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50">
                                {{ $deviceEnforced ? 'Nonaktifkan Fitur' : 'Aktifkan Fitur' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="p-6 border-b border-gray-100">
                <form method="POST" action="{{ route('admin.pos-devices.pairing') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @csrf
                    <div>
                        <label for="outlet_id" class="block text-sm font-medium text-gray-700 mb-1">Outlet</label>
                        <select id="outlet_id" name="outlet_id" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Perangkat (opsional)</label>
                        <input id="name" name="name" type="text" class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500" placeholder="Kasir Tablet 1">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white rounded-lg transition shadow-md hover:shadow-lg">
                            Buat Kode Pairing
                        </button>
                    </div>
                </form>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-xs uppercase text-gray-500 border-b">
                            <tr>
                                <th class="py-3 pr-4">Perangkat</th>
                                <th class="py-3 pr-4">Outlet</th>
                                <th class="py-3 pr-4">Status</th>
                                <th class="py-3 pr-4">Terakhir Aktif</th>
                                <th class="py-3 pr-4">Dibuat Oleh</th>
                                <th class="py-3 pr-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($devices as $device)
                                @php
                                    $statusLabel = $device->is_active
                                        ? ($device->isPaired() ? 'Aktif' : 'Menunggu Pairing')
                                        : 'Nonaktif';
                                    $statusClass = $device->is_active
                                        ? ($device->isPaired() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800')
                                        : 'bg-gray-100 text-gray-700';
                                @endphp
                                <tr>
                                    <td class="py-3 pr-4 font-medium text-gray-900">
                                        {{ $device->name ?: 'Tanpa nama' }}
                                        <div class="text-xs text-gray-500">#{{ $device->id }}</div>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">{{ $device->outlet?->name ?? '-' }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">
                                        {{ $device->last_seen_at ? $device->last_seen_at->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td class="py-3 pr-4 text-gray-700">
                                        {{ $device->creator?->name ?? '-' }}
                                    </td>
                                    <td class="py-3 pr-4 text-right">
                                        @if($device->is_active)
                                            <form method="POST" action="{{ route('admin.pos-devices.revoke', $device) }}" onsubmit="return confirm('Nonaktifkan perangkat ini?')" class="inline-flex">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 text-xs text-red-700 hover:bg-red-50 rounded-lg">Nonaktifkan</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-10 text-center text-gray-500">Belum ada perangkat terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
