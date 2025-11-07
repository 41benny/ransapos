@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan statistik dan aktivitas sistem')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Card: Total Produk -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Produk</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_products'] }}</p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-sm text-blue-600 hover:text-blue-700 mt-4 inline-block">
            Lihat semua →
        </a>
    </div>

    <!-- Card: Total Outlet -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Outlet</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_outlets'] }}</p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
        </div>
        <a href="{{ route('admin.outlets.index') }}" class="text-sm text-green-600 hover:text-green-700 mt-4 inline-block">
            Lihat semua →
        </a>
    </div>

    <!-- Card: Penjualan Hari Ini -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Penjualan Hari Ini</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">Rp {{ number_format($stats['total_sales_today'], 0, ',', '.') }}</p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-gray-500 mt-4">Transaksi selesai hari ini</p>
    </div>

    <!-- Card: Stok Rendah -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Stok Rendah</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['low_stock_items'] }}</p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
        </div>
        <p class="text-sm text-red-600 mt-4">Perlu restock segera</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aksi Cepat</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <a href="{{ route('admin.products.index') }}" 
           class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition group">
            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            <div>
                <p class="font-medium text-gray-900 group-hover:text-indigo-600">Tambah Produk</p>
                <p class="text-sm text-gray-500">Buat produk baru</p>
            </div>
        </a>

        <a href="{{ route('pos.dashboard') }}" 
           class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition group">
            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <div>
                <p class="font-medium text-gray-900 group-hover:text-indigo-600">Buka POS</p>
                <p class="text-sm text-gray-500">Mulai transaksi</p>
            </div>
        </a>

        <a href="{{ route('admin.suppliers.index') }}" 
           class="flex items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition group">
            <svg class="w-8 h-8 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <div>
                <p class="font-medium text-gray-900 group-hover:text-indigo-600">Kelola Supplier</p>
                <p class="text-sm text-gray-500">Daftar supplier</p>
            </div>
        </a>
    </div>
</div>

@endsection

