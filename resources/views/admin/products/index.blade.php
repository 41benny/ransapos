@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Daftar Produk')
@section('page-subtitle', 'Kelola semua produk yang tersedia')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
    <span class="text-violet-600 font-medium">Produk</span>
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

    <div class="w-full">
        <div class="card-premium h-full min-h-[calc(100vh-180px)]">

            <!-- Header -->
            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-box text-violet-500"></i>
                            Semua Produk
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Total: <span
                                class="font-semibold text-violet-600">{{ $products->total() }}</span> produk</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            onclick="document.getElementById('importModal').classList.remove('hidden'); document.getElementById('importModal').style.display = 'block';"
                            class="imperial-btn imperial-btn-outline imperial-btn-sm">
                            <i class="fas fa-file-import"></i>
                            <span>Import Excel</span>
                        </button>
                        <div class="relative" id="productCreateDropdownWrapper">
                            <button type="button" id="productCreateDropdownButton"
                                class="imperial-btn imperial-btn-sm inline-flex items-center gap-2"
                                aria-expanded="false"
                                aria-haspopup="true">
                                <i class="fas fa-sliders-h"></i>
                                <span>Tambah Baru</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div id="productCreateDropdownMenu"
                                class="hidden absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white shadow-xl z-30 overflow-hidden">
                                <a href="{{ route('admin.products.create') }}"
                                    class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                    <i class="fas fa-box"></i>
                                    <span>Tambah Produk</span>
                                </a>
                                <a href="{{ route('admin.products.create-bundle') }}"
                                    class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                                    <i class="fas fa-layer-group"></i>
                                    <span>Tambah Bundle</span>
                                </a>
                            </div>
                        </div>
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
    <div id="importModal" style="display: none;" class="fixed inset-0 z-[9999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Center Container -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">
                
                <!-- Header -->
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-file-import text-blue-600 text-lg"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">Import Data Produk</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Upload file Excel (.xlsx) sesuai format. Pastikan kolom: Nama Produk, SKU, Harga, dsb.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-4 py-6 sm:p-6">
                        <!-- Drop Zone -->
                        <div class="relative flex w-full justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 transition-colors hover:border-blue-500 hover:bg-blue-50/50 group">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                                <div class="mt-4 flex text-sm leading-6 text-gray-600 justify-center">
                                    <label for="file-upload" class="relative cursor-pointer rounded-md bg-white font-semibold text-blue-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-blue-600 focus-within:ring-offset-2 hover:text-blue-500 hover:underline">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="file" type="file" class="sr-only" required accept=".xlsx, .xls">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">XLSX up to 10MB</p>
                            </div>
                        </div>
                        
                        <!-- File Name Display -->
                        <div id="filename-display" class="mt-3 hidden rounded-lg bg-gray-50 p-3 text-sm text-gray-700 border border-gray-200 flex items-center gap-2">
                            <i class="fas fa-file-excel text-green-600"></i>
                            <span class="font-medium truncate"></span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto transition-colors">
                            <i class="fas fa-upload mr-2"></i> Import Sekarang
                        </button>
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors"
                            onclick="document.getElementById('importModal').classList.add('hidden'); document.getElementById('importModal').style.display = 'none';">
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
        const fileUploadEl = document.getElementById('file-upload');
        if (fileUploadEl) {
            fileUploadEl.addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name;
                if (!fileName) {
                    return;
                }

                const display = document.getElementById('filename-display');
                display.classList.remove('hidden');
                display.querySelector('span').textContent = fileName;
            });
        }

        const createMenuWrapper = document.getElementById('productCreateDropdownWrapper');
        const createMenuButton = document.getElementById('productCreateDropdownButton');
        const createMenu = document.getElementById('productCreateDropdownMenu');

        function closeCreateMenu() {
            if (!createMenu || !createMenuButton) {
                return;
            }

            createMenu.classList.add('hidden');
            createMenuButton.setAttribute('aria-expanded', 'false');
        }

        if (createMenuButton && createMenu && createMenuWrapper) {
            createMenuButton.addEventListener('click', function () {
                const isHidden = createMenu.classList.contains('hidden');
                createMenu.classList.toggle('hidden', !isHidden);
                createMenuButton.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            });

            document.addEventListener('click', function (event) {
                if (!createMenuWrapper.contains(event.target)) {
                    closeCreateMenu();
                }
            });
        }
    </script>
@endpush
