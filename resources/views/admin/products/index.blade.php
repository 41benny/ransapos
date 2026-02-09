@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Daftar Produk')

@section('content')

    <!-- Alert Success/Error -->
    @if(session('success'))
        <div class="mb-4 bg-green-50 text-green-700 p-4 rounded-lg border border-green-200">
            <i class="fas fa-check-circle mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-50 text-red-700 p-4 rounded-lg border border-red-200">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="w-full max-w-7xl mx-auto">
        <div class="card bg-white p-6">

            <!-- Header Content -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Semua Produk</h2>
                    <p class="text-sm text-gray-500">Total: <span class="font-semibold">{{ $products->total() }}</span>
                        produk</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        onclick="document.getElementById('importModal').classList.remove('hidden'); document.getElementById('importModal').style.display = 'block';"
                        class="btn btn-secondary">
                        <i class="fas fa-file-import"></i>
                        <span>Import Excel</span>
                    </button>

                    <div class="relative" id="productCreateDropdownWrapper">
                        <button type="button" id="productCreateDropdownButton" class="btn btn-primary" aria-expanded="false"
                            aria-haspopup="true">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Baru</span>
                            <i class="fas fa-chevron-down text-xs ml-1"></i>
                        </button>
                        <div id="productCreateDropdownMenu"
                            class="hidden absolute right-0 mt-2 w-48 rounded-lg border border-gray-100 bg-white shadow-lg z-30 overflow-hidden">
                            <a href="{{ route('admin.products.create') }}"
                                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-box w-5"></i>
                                <span>Tambah Produk</span>
                            </a>
                            <a href="{{ route('admin.products.create-bundle') }}"
                                class="flex items-center gap-2 px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-layer-group w-5"></i>
                                <span>Tambah Bundle</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-container overflow-x-auto">
                <table class="table-modern" id="productsTable" style="table-layout: auto;">
                    <thead>
                        <tr>
                            <th class="resizable" style="min-width: 100px; position: relative;">
                                SKU
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable" style="min-width: 150px; position: relative;">
                                Nama Produk
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable" style="min-width: 120px; position: relative;">
                                Kategori
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable" style="min-width: 120px; position: relative;">
                                Harga Jual
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable" style="min-width: 80px; position: relative;">
                                Satuan
                                <div class="resize-handle"></div>
                            </th>
                            <th class="resizable" style="min-width: 100px; position: relative;">
                                Status
                                <div class="resize-handle"></div>
                            </th>
                            <th style="min-width: 100px;">Aksi</th>
                        </tr>
                        <!-- Filter Row -->
                        <!-- Filter Row -->
                        <tr class="filter-row bg-gray-50">
                            <th class="px-3 py-2">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="sku" placeholder="Filter SKU...">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="name" placeholder="Filter Nama...">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="category" placeholder="Filter Kategori...">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="price" placeholder="Filter Harga...">
                            </th>
                            <th class="px-3 py-2">
                                <input type="text"
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="unit" placeholder="Filter Satuan...">
                            </th>
                            <th class="px-3 py-2">
                                <select
                                    class="filter-input w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    data-name="status">
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </th>
                            <th class="px-3 py-2">
                                <button type="button" id="clearFilters"
                                    class="w-full px-2 py-1 text-xs bg-gray-200 hover:bg-gray-300 rounded transition-colors"
                                    title="Clear all filters">
                                    <i class="fas fa-times"></i>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td class="font-mono text-xs font-semibold">{{ $product->sku }}</td>
                                <td>
                                    <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                    @if($product->description)
                                        <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">
                                            {{ Str::limit($product->description, 50) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $product->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="font-medium">
                                    Rp {{ number_format($product->selling_price, 0, ',', '.') }}
                                </td>
                                <td>{{ $product->unit }}</td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-gray">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.products.show', $product) }}"
                                            class="text-blue-600 hover:text-blue-800" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="{{ route('admin.products.edit', $product) }}"
                                            class="text-amber-600 hover:text-amber-800" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        @if($product->product_type === 'finished_good')
                                            @if($product->bomHeader)
                                                <a href="{{ route('admin.boms.show', $product->bomHeader) }}"
                                                    class="text-indigo-600 hover:text-indigo-800" title="Lihat Resep/BOM">
                                                    <i class="fas fa-book-open"></i>
                                                </a>
                                                <a href="{{ route('admin.boms.edit', $product->bomHeader) }}"
                                                    class="text-teal-600 hover:text-teal-800" title="Edit Resep/BOM">
                                                    <i class="fas fa-flask"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('admin.boms.create') }}" class="text-gray-500 hover:text-gray-700"
                                                    title="Buat Resep/BOM">
                                                    <i class="fas fa-receipt"></i>
                                                </a>
                                            @endif
                                        @endif

                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                            class="inline"
                                            onsubmit="return confirm('Yakin ingin menghapus produk {{ $product->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
                                        <p>Belum ada produk</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="mt-6">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importModal" style="display: none;" class="fixed inset-0 z-[9999] overflow-y-auto"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold leading-6 text-gray-900" id="modal-title">Import Data Produk</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">
                            Upload file Excel (.xlsx) sesuai format. Bisa import produk sekaligus resep/BOM (kolom
                            bom_komponen_*).
                        </p>
                    </div>
                </div>

                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="bg-white px-4 py-6 sm:p-6">
                        <div
                            class="relative flex w-full justify-center rounded-lg border-2 border-dashed border-gray-300 px-6 py-10 hover:border-blue-500 hover:bg-blue-50/50 transition-colors">
                            <div class="text-center">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-3"></i>
                                <div class="flex text-sm leading-6 text-gray-600 justify-center">
                                    <label for="file-upload"
                                        class="relative cursor-pointer rounded-md bg-white font-semibold text-blue-600 focus-within:outline-none hover:text-blue-500">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="file" type="file" class="sr-only" required
                                            accept=".xlsx, .xls">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">XLSX up to 10MB</p>
                            </div>
                        </div>

                        <div id="filename-display"
                            class="mt-3 hidden rounded-md bg-gray-50 p-2 text-sm text-gray-700 border border-gray-200 flex items-center gap-2">
                            <i class="fas fa-file-excel text-green-600"></i>
                            <span class="font-medium truncate"></span>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" class="btn btn-primary w-full sm:w-auto sm:ml-3">
                            Import Sekarang
                        </button>
                        <button type="button" class="btn btn-secondary w-full sm:w-auto mt-3 sm:mt-0"
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
    <style>
        /* Resize handle styling */
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            cursor: col-resize;
            user-select: none;
            z-index: 10;
        }

        .resize-handle:hover {
            background-color: rgba(59, 130, 246, 0.5);
        }

        .resizing {
            cursor: col-resize;
            user-select: none;
        }

        /* Filter row styling */
        .filter-row th {
            background-color: #f9fafb;
            border-bottom: 2px solid #e5e7eb;
        }

        /* Hidden row for filtering */
        .hidden-row {
            display: none !important;
        }
    </style>

    <script>
        // ========================================
        // FILE UPLOAD HANDLER
        // ========================================
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

        // ========================================
        // DROPDOWN MENU HANDLER
        // ========================================
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

        // ========================================
        // SERVER-SIDE FILTERING
        // ========================================
        const filterInputs = document.querySelectorAll('.filter-input');
        const clearFiltersBtn = document.getElementById('clearFilters');

        // Populate inputs from URL parameters
        function populateFilters() {
            const params = new URLSearchParams(window.location.search);

            filterInputs.forEach(input => {
                const name = input.dataset.name;
                if (params.has(name)) {
                    input.value = params.get(name);
                }
            });
        }

        // Debounce function
        let debounceTimer;
        function updateFilter(name, value) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const url = new URL(window.location.href);

                if (value.trim() !== '') {
                    url.searchParams.set(name, value.trim());
                } else {
                    url.searchParams.delete(name);
                }

                // Reset to page 1 when filtering changes
                url.searchParams.delete('page');

                window.location.href = url.toString();
            }, 500); // 500ms debounce
        }

        // Attach listeners
        filterInputs.forEach(input => {
            const name = input.dataset.name;

            if (input.tagName === 'SELECT') {
                input.addEventListener('change', (e) => {
                    updateFilter(name, e.target.value);
                });
            } else {
                input.addEventListener('input', (e) => {
                    updateFilter(name, e.target.value);
                });
            }
        });

        // Clear all filters
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function () {
                const url = new URL(window.location.href);

                filterInputs.forEach(input => {
                    url.searchParams.delete(input.dataset.name);
                });

                url.searchParams.delete('page');
                window.location.href = url.toString();
            });
        }

        // Initialize on load
        populateFilters();

        // ========================================
        // RESIZABLE COLUMNS
        // ========================================
        const table = document.getElementById('productsTable');
        const STORAGE_KEY = 'products_table_column_widths';

        // Load saved column widths from localStorage
        function loadColumnWidths() {
            const savedWidths = localStorage.getItem(STORAGE_KEY);
            if (savedWidths) {
                try {
                    const widths = JSON.parse(savedWidths);
                    const headers = table.querySelectorAll('th.resizable');
                    headers.forEach((th, index) => {
                        if (widths[index]) {
                            th.style.width = widths[index] + 'px';
                        }
                    });
                } catch (e) {
                    console.error('Error loading column widths:', e);
                }
            }
        }

        // Save column widths to localStorage
        function saveColumnWidths() {
            const headers = table.querySelectorAll('th.resizable');
            const widths = Array.from(headers).map(th => th.offsetWidth);
            localStorage.setItem(STORAGE_KEY, JSON.stringify(widths));
        }

        // Initialize resizable columns
        function initResizableColumns() {
            const headers = table.querySelectorAll('th.resizable');

            headers.forEach((th, index) => {
                const handle = th.querySelector('.resize-handle');
                if (!handle) return;

                let startX, startWidth;

                handle.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    startX = e.pageX;
                    startWidth = th.offsetWidth;

                    document.body.classList.add('resizing');

                    function onMouseMove(e) {
                        const diff = e.pageX - startX;
                        const newWidth = Math.max(50, startWidth + diff); // Minimum 50px
                        th.style.width = newWidth + 'px';
                    }

                    function onMouseUp() {
                        document.body.classList.remove('resizing');
                        document.removeEventListener('mousemove', onMouseMove);
                        document.removeEventListener('mouseup', onMouseUp);
                        saveColumnWidths();
                    }

                    document.addEventListener('mousemove', onMouseMove);
                    document.addEventListener('mouseup', onMouseUp);
                });
            });
        }

        // Initialize on page load
        if (table) {
            loadColumnWidths();
            initResizableColumns();
        }
    </script>
@endpush