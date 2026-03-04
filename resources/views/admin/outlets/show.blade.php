@extends('layouts.admin')

@section('title', 'Detail Outlet')
@section('page-title', 'Detail Outlet')
@section('page-subtitle', 'Informasi lengkap outlet')

@section('content')
<div class="max-w-5xl space-y-6">
    <div class="ui-card bg-white rounded-xl shadow-sm border border-gray-100">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $outlet->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Kode: {{ $outlet->code }}</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($outlet->is_active)
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
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Alamat</p>
                        <p class="text-sm text-gray-900">{{ $outlet->address ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Telepon</p>
                        <p class="text-sm font-medium text-gray-900">{{ $outlet->phone ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Email</p>
                        <p class="text-sm font-medium text-gray-900">{{ $outlet->email ?: '-' }}</p>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Pajak (PB1)</p>
                        <p class="text-sm font-medium text-gray-900">{{ number_format($outlet->tax_rate ?? 0, 2) }}%</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Service Charge</p>
                        <p class="text-sm font-medium text-gray-900">{{ number_format($outlet->service_charge_rate ?? 0, 2) }}%</p>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Dibuat Pada</p>
                        <p class="text-sm text-gray-900">{{ $outlet->created_at?->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a
            href="{{ route('admin.outlets.index') }}"
            class="px-5 py-2 bg-white border border-amber-300 text-amber-900 rounded-lg hover:bg-amber-50 transition flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar
        </a>

        <a
            href="{{ route('admin.outlets.edit', $outlet) }}"
            class="px-5 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg flex items-center"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Outlet
        </a>
    </div>
</div>
@endsection
