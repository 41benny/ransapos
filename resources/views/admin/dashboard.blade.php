@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">

    <!-- Card: Total Produk -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-blue-50 rounded-xl">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium mb-1">Total Produk</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_products'] }}</p>
            <a href="{{ route('admin.products.index') }}" class="text-sm text-blue-600 hover:text-blue-700 mt-3 inline-flex items-center gap-1">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Card: Total Outlet -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-purple-50 rounded-xl">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium mb-1">Total Outlet</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['total_outlets'] }}</p>
            <a href="{{ route('admin.outlets.index') }}" class="text-sm text-purple-600 hover:text-purple-700 mt-3 inline-flex items-center gap-1">
                Lihat semua
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Card: Penjualan Hari Ini -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-green-50 rounded-xl">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium mb-1">Penjualan Hari Ini</p>
            <p class="text-2xl font-bold text-gray-900">Rp {{ number_format($stats['total_sales_today'], 0, ',', '.') }}</p>
            <p class="text-sm text-gray-500 mt-2">Transaksi selesai hari ini</p>
        </div>
    </div>

    <!-- Card: Stok Rendah -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-orange-50 rounded-xl">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <div>
            <p class="text-sm text-gray-500 font-medium mb-1">Stok Rendah</p>
            <p class="text-3xl font-bold text-gray-900">{{ $stats['low_stock_items'] }}</p>
            <p class="text-sm text-orange-600 mt-2 font-medium">Perlu restock segera</p>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="text-lg font-bold text-gray-900 mb-6">Aksi Cepat</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.products.index') }}"
            class="flex items-center gap-4 p-5 rounded-xl border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-all group">
            <div class="p-3 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-blue-700">Tambah Produk</p>
                <p class="text-sm text-gray-500">Buat produk baru</p>
            </div>
        </a>

        <a href="{{ route('pos.dashboard') }}"
            class="flex items-center gap-4 p-5 rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 transition-all shadow-md hover:shadow-lg group">
            <div class="p-3 bg-white/20 rounded-lg backdrop-blur-sm">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-white">Buka POS</p>
                <p class="text-sm text-white/80">Mulai transaksi</p>
            </div>
        </a>

        <a href="{{ route('admin.suppliers.index') }}"
            class="flex items-center gap-4 p-5 rounded-xl border border-gray-200 hover:border-purple-300 hover:bg-purple-50 transition-all group">
            <div class="p-3 bg-purple-100 rounded-lg group-hover:bg-purple-200 transition-colors">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-gray-900 group-hover:text-purple-700">Kelola Supplier</p>
                <p class="text-sm text-gray-500">Daftar supplier</p>
            </div>
        </a>
    </div>
</div>

@endsection

