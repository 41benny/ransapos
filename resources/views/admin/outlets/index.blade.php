@extends('layouts.admin')

@section('title', 'Outlet')
@section('page-title', 'Daftar Outlet')
@section('page-subtitle', 'Kelola cabang/toko Anda')

@section('content')
<div class="page-fullwidth">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

    @if(session('success'))
        <div class="p-6">
            <div class="alert alert-success">
                <i class="fas fa-check-circle text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-6">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif
    
    <!-- Header -->
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Semua Outlet</h3>
                <p class="text-sm text-gray-500 mt-1">Total: {{ $outlets->total() }} outlet</p>
            </div>
            <a href="{{ route('admin.outlets.create') }}" class="px-4 py-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-sky-500 text-white rounded-lg transition shadow-md hover:shadow-lg inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Outlet
            </a>
        </div>
    </div>

    <!-- Grid View -->
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($outlets as $outlet)
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $outlet->name }}</h4>
                            <p class="text-xs text-gray-500 font-mono">{{ $outlet->code }}</p>
                        </div>
                    </div>
                    @if($outlet->is_active)
                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Aktif</span>
                    @else
                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">Nonaktif</span>
                    @endif
                </div>

                <div class="space-y-2 mb-4">
                    @if($outlet->address)
                    <div class="flex items-start text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>{{ Str::limit($outlet->address, 60) }}</span>
                    </div>
                    @endif

                    @if($outlet->phone)
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>{{ $outlet->phone }}</span>
                    </div>
                    @endif

                    @if($outlet->email)
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $outlet->email }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex items-center space-x-2 pt-4 border-t border-gray-100">
                    <a href="{{ route('admin.outlets.edit', $outlet) }}" class="flex-1 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50 rounded-lg transition text-center">
                        Edit
                    </a>
                    <a href="{{ route('admin.outlets.show', $outlet) }}" class="flex-1 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50 rounded-lg transition text-center">
                        Detail
                    </a>
                </div>
            </div>
            @empty
            <div class="col-span-3 py-16 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <p class="text-gray-500">Belum ada outlet</p>
                <p class="text-sm text-gray-400 mt-1">Tambahkan outlet pertama Anda</p>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($outlets->hasPages())
        <div class="mt-6">
            {{ $outlets->links() }}
        </div>
        @endif
    </div>
</div>
</div>
@endsection

