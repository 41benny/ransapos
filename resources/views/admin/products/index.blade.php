@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Daftar Produk')
@section('page-subtitle', 'Kelola semua produk yang tersedia')

    @section('breadcrumb')
        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
        <span class="text-violet-600 font-medium">Produk</span>
    @endsection

    @section('header-actions')
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                class="imperial-btn imperial-btn-outline imperial-btn-sm">
                <i class="fas fa-file-import"></i>
                <span>Import Excel</span>
            </button>
            <a href="{{ route('admin.products.create') }}" class="imperial-btn imperial-btn-sm">
                <i class="fas fa-plus"></i>
                <span>Tambah Produk</span>
            </a>
        </div>
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
                            <p class="text-sm text-gray-500 mt-1">Total: <span
                                    class="font-semibold text-violet-600">{{ $products->total() }}</span> produk</p>
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
                                        <span class="font-bold text-gray-900">Rp
                                            {{ number_format($product->selling_price, 0, ',', '.') }}</span>
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
                                            <a href="{{ route('admin.products.show', $product) }}"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <a href="{{ route('admin.products.edit', $product) }}"
                                                class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Yakin ingin menghapus produk {{ $product->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Hapus">
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
                                            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
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

    <!-- Import Modal -->
    <div id="importModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
                onclick="document.getElementById('importModal').classList.add('hidden')"></div>

            <!-- Modal panel -->
            <div
                class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-file-import text-blue-600"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Import Data Produk
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 mb-4">
                                        Upload file Excel (.xlsx) dengan kolom: Nama Produk, Kategori, SKU, Harga Beli,
                                        Harga Jual, Stok, Satuan.
                                    </p>
                                    <div
                                        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-500 transition-colors">
                                        <div class="space-y-1 text-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>
                                            <div class="flex text-sm text-gray-600">
                                                <label for="file-upload"
                                                    class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                    <span>Upload file</span>
                                                    <input id="file-upload" name="file" type="file" class="sr-only" required
                                                        accept=".xlsx, .xls">
                                                </label>
                                                <p class="pl-1">atau drag and drop</p>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                XLSX atau XLS hingga 10MB
                                            </p>
                                        </div>
                                    </div>
                                    <div id="filename-display" class="mt-2 text-sm text-gray-600 hidden">
                                        Selected file: <span class="font-medium"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Import
                        </button>
                        <button type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            onclick="document.getElementById('importModal').classList.add('hidden')">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.getElementById('file-upload').addEventListener('change', function (e) {
            var fileName = e.target.files[0].name;
            var display = document.getElementById('filename-display');
            display.classList.remove('hidden');
            display.querySelector('span').textContent = fileName;
        });
    </script>
@endpush
