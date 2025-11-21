@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Daftar Produk')
@section('page-subtitle', 'Kelola semua produk yang tersedia')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
    <span class="text-violet-600 font-medium">Produk</span>
@endsection

@section('header-actions')
    <a href="{{ route('admin.products.create') }}" class="imperial-btn imperial-btn-sm">
        <i class="fas fa-plus"></i>
        <span>Tambah Produk</span>
    </a>
@endsection

@section('content')

<!-- Alert Success/Error -->
@if(session('success'))
<div class="alert alert-success">
    <i class="fas fa-check-circle text-lg"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

@if(session('error'))
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle text-lg"></i>
    <span>{{ session('error') }}</span>
</div>
@endif

<div class="page-fullwidth">
<div class="card-premium page-card-fill">

    <!-- Header -->
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-box text-violet-500"></i>
                    Semua Produk
                </h3>
                <p class="text-sm text-gray-500 mt-1">Total: <span class="font-semibold text-violet-600">{{ $products->total() }}</span> produk</p>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="table-premium">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Jual</th>
                    <th>Satuan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        <span class="font-mono font-semibold">{{ $product->sku }}</span>
                    </td>
                    <td>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $product->name }}</p>
                            @if($product->description)
                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($product->description, 50) }}</p>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-gray">
                            <i class="fas fa-tag text-xs"></i>
                            {{ $product->category->name ?? '-' }}
                        </span>
                    </td>
                    <td>
                        <span class="font-bold text-gray-900">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        <span>{{ $product->unit }}</span>
                    </td>
                    <td>
                        @if($product->is_active)
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle"></i>
                                Aktif
                            </span>
                        @else
                            <span class="badge badge-gray">
                                <i class="fas fa-times-circle"></i>
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.products.show', $product) }}" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>

                            <a href="{{ route('admin.products.edit', $product) }}" class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus produk {{ $product->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <p class="text-gray-500">Belum ada produk</p>
                            <p class="text-sm text-gray-400 mt-1">Tambahkan produk pertama Anda</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($products->hasPages())
    <div class="p-6 border-t border-gray-100">
        {{ $products->links() }}
    </div>
    @endif
</div>
</div>
@endsection
