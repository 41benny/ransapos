@extends('layouts.admin')

@section('title', 'Produk')
@section('page-title', 'Produk')
@section('page-subtitle', 'Manajemen item produk, kategori, harga jual, dan paket bundle (BOM)')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Daftar Produk</h1>
                <p class="text-xs font-normal text-slate-700 mt-0.5">Total <span
                        class="text-indigo-600 font-medium">{{ $products->total() }}</span> item terdaftar dalam sistem</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <button
                    onclick="document.getElementById('importModal').classList.remove('hidden'); document.getElementById('importModal').style.display = 'block';"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-file-import text-[10px] text-slate-400"></i>
                    <span>Import Excel</span>
                </button>

                <div class="relative" id="productCreateDropdownWrapper">
                    <button type="button" id="productCreateDropdownButton"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                        <i class="fas fa-plus text-[10px]"></i>
                        <span>Tambah Baru</span>
                        <i class="fas fa-chevron-down text-[8px] ml-1 opacity-70"></i>
                    </button>
                    <div id="productCreateDropdownMenu"
                        class="hidden absolute right-0 mt-2 w-48 rounded-xl border border-slate-100 bg-white shadow-xl z-50 overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                        <a href="{{ route('admin.products.create') }}"
                            class="flex items-center gap-2 px-4 py-3 text-[11.5px] font-normal text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-box w-4 text-[10px]"></i>
                            <span>Tambah Produk Tunggal</span>
                        </a>
                        <a href="{{ route('admin.products.create-bundle') }}"
                            class="flex items-center gap-2 px-4 py-3 text-[11.5px] font-normal text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-colors border-t border-slate-50">
                            <i class="fas fa-layer-group w-4 text-[10px]"></i>
                            <span>Tambah Paket Bundle</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert Section --}}
        @if(session('success'))
            <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-3 text-rose-600">
                <i class="fas fa-exclamation-circle text-[10px]"></i>
                <p class="text-xs font-normal">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Main Table Area --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200" id="productsTable">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-700 resizable group"
                                style="min-width: 100px; position: relative;">
                                SKU
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-700 resizable"
                                style="min-width: 200px; position: relative;">
                                Nama Produk
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-700 resizable"
                                style="min-width: 120px; position: relative;">
                                Kategori
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-700 resizable"
                                style="min-width: 120px; position: relative;">
                                Harga Jual
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-700 resizable"
                                style="min-width: 80px; position: relative;">
                                Satuan
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-700 resizable"
                                style="min-width: 100px; position: relative;">
                                Status
                                <div class="resize-handle"></div>
                            </th>
                            <th class="px-5 py-3 text-center text-xs font-normal uppercase tracking-widest text-slate-700"
                                style="min-width: 100px;">Aksi</th>
                        </tr>
                        {{-- Compact Filter Row --}}
                        <tr class="bg-white border-b border-slate-100 no-print">
                            <td class="px-3 py-2">
                                <input type="text" data-name="sku" placeholder="Cari SKU..."
                                    class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" data-name="name" placeholder="Cari nama produk..."
                                    class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" data-name="category" placeholder="Kategori..."
                                    class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" data-name="price" placeholder="Harga..."
                                    class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300 text-right">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" data-name="unit" placeholder="Satuan..."
                                    class="filter-input w-full px-3 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all placeholder:text-slate-300 text-center">
                            </td>
                            <td class="px-3 py-2">
                                <select data-name="status"
                                    class="filter-input w-full px-2 py-1.5 text-xs font-normal bg-slate-50 border-none rounded-lg focus:ring-1 focus:ring-indigo-500 transition-all">
                                    <option value="">Semua</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" id="clearFilters" title="Reset filter"
                                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg bg-slate-50 text-slate-600 hover:text-rose-500 hover:bg-rose-50 transition-all">
                                    <i class="fas fa-times-circle text-[12px]"></i>
                                </button>
                            </td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($products as $product)
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-5 py-3.5">
                                    <span
                                        class="text-xs font-mono text-slate-600 uppercase tracking-widest leading-none">{{ $product->sku }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-normal text-slate-900 leading-tight group-hover:text-indigo-600 transition-colors">{{ $product->name }}</span>
                                        @if($product->description)
                                            <span
                                                class="text-xs font-normal text-slate-600 mt-1 line-clamp-1 italic">{{ $product->description }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-normal text-slate-700">
                                        {{ $product->category->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right tabular-nums">
                                    <span class="text-sm font-normal text-slate-900">Rp
                                        {{ number_format((float) $product->selling_price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="text-sm font-normal text-slate-700">{{ $product->unit }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    @if($product->is_active)
                                        <span
                                            class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-normal text-emerald-600 ring-1 ring-inset ring-emerald-200 uppercase tracking-widest">Aktif</span>
                                    @else
                                        <span
                                            class="inline-flex items-center rounded-full bg-slate-50 px-2.5 py-0.5 text-[9px] font-normal text-slate-400 ring-1 ring-inset ring-slate-200 uppercase tracking-widest">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-center">
                                    <div class="relative inline-block text-left product-actions-dropdown">
                                        <button type="button"
                                            class="action-dropdown-btn inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-normal text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                                            <span>Aksi</span>
                                            <i class="fas fa-chevron-down text-[8px] text-slate-400"></i>
                                        </button>
                                        <div
                                            class="action-dropdown-menu hidden absolute right-0 z-[100] mt-2 w-44 origin-top-right rounded-xl bg-white shadow-xl border border-slate-100 ring-1 ring-black ring-opacity-5 animate-in fade-in slide-in-from-top-2 duration-200">
                                            <div class="py-1">
                                                <a href="{{ route('admin.products.show', $product) }}"
                                                    class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                                                    <i class="fas fa-eye w-4 text-center opacity-70"></i>
                                                    Lihat Detail
                                                </a>
                                                <a href="{{ route('admin.products.edit', $product) }}"
                                                    class="flex items-center gap-2 px-4 py-2 text-[11px] text-slate-600 hover:bg-slate-50 hover:text-amber-600 border-t border-slate-50">
                                                    <i class="fas fa-edit w-4 text-center opacity-70"></i>
                                                    Edit Produk
                                                </a>

                                                @if($product->bomHeader)
                                                    <a href="{{ route('admin.boms.show', ['bom' => $product->bomHeader, 'source_type' => 'bundle', 'return_to' => request()->fullUrl()]) }}"
                                                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-indigo-600 border-t border-slate-50">
                                                        <i class="fas fa-book-open w-4 text-center opacity-70"></i>
                                                        Lihat Resep (BOM)
                                                    </a>
                                                    <a href="{{ route('admin.boms.edit', ['bom' => $product->bomHeader, 'source_type' => 'bundle', 'return_to' => request()->fullUrl()]) }}"
                                                        class="flex items-center gap-2 px-4 py-2 text-[11px] text-slate-600 hover:bg-slate-50 hover:text-teal-600 border-t border-slate-50">
                                                        <i class="fas fa-flask w-4 text-center opacity-70"></i>
                                                        Edit Resep (BOM)
                                                    </a>
                                                @endif

                                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                                    class="block border-t border-slate-50"
                                                    onsubmit="return confirm('Yakin hapus produk {{ $product->name }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-sm text-rose-500 hover:bg-rose-50 transition-colors">
                                                        <i class="fas fa-trash w-4 text-center opacity-70"></i>
                                                        Hapus Produk
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center opacity-30">
                                        <i class="fas fa-box-open text-5xl text-slate-300 mb-4"></i>
                                        <p class="text-[11px] font-normal text-slate-500 italic uppercase tracking-widest">Belum
                                            ada produk terdaftar</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Area --}}
            @if($products->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/20">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Import Modal --}}
    <div id="importModal" style="display: none;" class="fixed inset-0 z-[9999] overflow-y-auto no-print">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-lg p-8 animate-in zoom-in-95 duration-300">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-lg font-normal text-slate-800 tracking-tight">Import Data Produk</h3>
                        <p class="text-sm font-normal text-slate-500 mt-1">Gunakan file Excel (.xlsx) sesuai dengan template
                            sistem</p>
                    </div>
                    <button onclick="document.getElementById('importModal').style.display = 'none';"
                        class="text-slate-400 hover:text-slate-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form action="{{ route('admin.products.import') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-6">
                    @csrf
                    <div
                        class="relative flex w-full justify-center rounded-2xl border-2 border-dashed border-slate-200 px-6 py-12 hover:border-indigo-400 hover:bg-indigo-50/30 transition-all group">
                        <div class="text-center">
                            <div
                                class="h-12 w-12 bg-slate-50 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                <i class="fas fa-cloud-upload-alt text-xl text-slate-400"></i>
                            </div>
                            <div class="flex text-[11.5px] leading-6 text-slate-600 justify-center">
                                <label for="file-upload"
                                    class="relative cursor-pointer rounded-md font-semibold text-indigo-600 hover:text-indigo-500">
                                    <span>Pilih file Excel</span>
                                    <input id="file-upload" name="file" type="file" class="sr-only" required
                                        accept=".xlsx, .xls">
                                </label>
                                <p class="pl-1 text-slate-400">atau drag & drop</p>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-widest">Format: .xlsx (Max 10MB)
                            </p>
                        </div>
                    </div>

                    <div id="filename-display"
                        class="hidden rounded-xl bg-slate-50 p-3 text-xs font-normal text-slate-600 border border-slate-100 flex items-center gap-3">
                        <i class="fas fa-file-excel text-emerald-500"></i>
                        <span class="truncate"></span>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="document.getElementById('importModal').style.display = 'none';"
                            class="flex-1 px-4 py-3 rounded-xl bg-slate-100 text-slate-600 text-xs font-normal transition-all hover:bg-slate-200 active:scale-95 uppercase tracking-widest">Batal</button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 rounded-xl bg-slate-900 text-white text-xs font-normal transition-all hover:bg-slate-800 active:scale-95 shadow-lg uppercase tracking-widest">Mulai
                            Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <style>
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            cursor: col-resize;
            z-index: 10;
        }

        .resize-handle:hover {
            background: rgba(99, 102, 241, 0.5);
        }

        .resizing {
            cursor: col-resize;
            user-select: none;
        }
    </style>

    <script>
        // File upload display
        const fileUploadEl = document.getElementById('file-upload');
        if (fileUploadEl) {
            fileUploadEl.addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    const display = document.getElementById('filename-display');
                    display.classList.remove('hidden');
                    display.querySelector('span').textContent = fileName;
                }
            });
        }

        // Dropdown handler
        const wrapper = document.getElementById('productCreateDropdownWrapper');
        const btn = document.getElementById('productCreateDropdownButton');
        const menu = document.getElementById('productCreateDropdownMenu');

        if (btn && menu) {
            btn.addEventListener('click', () => menu.classList.toggle('hidden'));
            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) menu.classList.add('hidden');
            });
        }

        // Action Dropdown Handler
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.action-dropdown-btn');
            const container = e.target.closest('.product-actions-dropdown');

            // Close all other menus first
            if (btn || !container) {
                document.querySelectorAll('.action-dropdown-menu').forEach(menu => {
                    if (!container || menu !== container.querySelector('.action-dropdown-menu')) {
                        menu.classList.add('hidden');
                    }
                });
            }

            // Toggle current menu
            if (btn && container) {
                const menu = container.querySelector('.action-dropdown-menu');
                menu.classList.toggle('hidden');
            }
        });

        // Filtering logic
        const filterInputs = document.querySelectorAll('.filter-input');
        const clearBtn = document.getElementById('clearFilters');

        function updateFilter(name, value) {
            const url = new URL(window.location.href);
            if (value.trim()) url.searchParams.set(name, value.trim());
            else url.searchParams.delete(name);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        let timer;
        filterInputs.forEach(input => {
            const name = input.dataset.name;
            input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', (e) => {
                clearTimeout(timer);
                timer = setTimeout(() => updateFilter(name, e.target.value), 600);
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                const url = new URL(window.location.origin + window.location.pathname);
                window.location.href = url.toString();
            });
        }

        // Preserve URL params in inputs on load
        const params = new URLSearchParams(window.location.search);
        filterInputs.forEach(input => {
            if (params.has(input.dataset.name)) input.value = params.get(input.dataset.name);
        });

        // Column resizing logic
        const table = document.getElementById('productsTable');
        if (table) {
            const headers = table.querySelectorAll('th.resizable');
            headers.forEach(th => {
                const handle = th.querySelector('.resize-handle');
                if (handle) {
                    let startX, startWidth;
                    handle.addEventListener('mousedown', (e) => {
                        startX = e.pageX; startWidth = th.offsetWidth;
                        document.body.classList.add('resizing');
                        const move = (e) => th.style.width = Math.max(50, startWidth + (e.pageX - startX)) + 'px';
                        const up = () => {
                            document.body.classList.remove('resizing');
                            document.removeEventListener('mousemove', move);
                            document.removeEventListener('mouseup', up);
                        };
                        document.addEventListener('mousemove', move);
                        document.addEventListener('mouseup', up);
                    });
                }
            });
        }
    </script>
@endpush