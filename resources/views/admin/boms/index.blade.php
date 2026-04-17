@extends('layouts.admin')

@section('title', 'Daftar Resep Produk (BOM)')
@section('page-title', 'Daftar Bill of Materials (BOM)')

@section('content')
    @php
        $activeSourceType = $sourceType ?? 'production';
        $sourceLabels = [
            'production' => 'Produksi',
            'bundle' => 'Bundle',
            'all' => 'Semua',
        ];
    @endphp

    <div class="w-full">
        @if(session('success'))
            <div class="mb-4 bg-green-50 text-green-700 p-4 rounded-lg border border-green-200 shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2 text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="ui-card card p-0 overflow-hidden">
            <!-- Header -->
            <div
                class="px-6 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gray-50/30">
                <div>
                    <p class="text-xs uppercase tracking-wider text-amber-600 font-bold mb-1">Menu Engineering</p>
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-gray-900">Resep Produk (BOM)</h3>
                        <span
                            class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full">{{ $boms->total() }}
                            {{ $sourceLabels[$activeSourceType] ?? 'Resep' }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        @if($activeSourceType === 'production')
                            Kelola resep untuk proses produksi.
                        @elseif($activeSourceType === 'bundle')
                            Daftar resep yang dibuat dari menu bundle.
                        @else
                            Semua resep (produksi dan bundle).
                        @endif
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <a href="{{ route('admin.boms.index', ['source_type' => 'production']) }}"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $activeSourceType === 'production' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Produksi
                        </a>
                        <a href="{{ route('admin.boms.index', ['source_type' => 'bundle']) }}"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $activeSourceType === 'bundle' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Bundle
                        </a>
                        <a href="{{ route('admin.boms.index', ['source_type' => 'all']) }}"
                            class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $activeSourceType === 'all' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Semua
                        </a>
                    </div>
                </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-green-50 rounded-md p-1 border border-green-100">
                            <a href="{{ route('admin.boms.export-excel', ['source_type' => $activeSourceType]) }}" class="px-3 py-1.5 text-xs font-bold text-green-700 hover:bg-green-100 rounded transition-colors flex items-center gap-1.5" title="Export Excel">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </a>
                            <div class="w-px h-4 bg-green-200 mx-0.5"></div>
                            <a href="{{ route('admin.boms.export-pdf', ['source_type' => $activeSourceType]) }}" class="px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 rounded transition-colors flex items-center gap-1.5" title="Export PDF">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </a>
                        </div>
                        <a href="{{ route('admin.boms.create', ['source_type' => 'production', 'return_to' => request()->fullUrl()]) }}" class="ui-btn ui-btn-primary btn btn-primary shadow-lg shadow-blue-500/20">
                            <i class="fas fa-plus"></i>
                            <span>Buat Resep</span>
                        </a>
                    </div>
            </div>

            <!-- Table -->
            <div class="table-container border-x-0 border-b-0 rounded-none">
                <table class="ui-table table-modern">
                    <thead>
                        <tr>
                            <th class="pl-6 w-16" colspan="2">ID & Detail</th>
                            <th>Produk & SKU</th>
                            <th>Nama BOM (Varian)</th>
                            <th>Status</th>
                            <th class="text-center">Komponen</th>
                            <th>Terakhir Update</th>
                            <th class="text-center pr-6 w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($boms as $bom)
                            <tr class="hover:bg-blue-50/30 transition-colors duration-150">
                                <td class="pl-6 font-mono text-gray-500 text-xs">#{{ $bom->id }}</td>
                                <td class="pl-2">
                                    <button type="button" 
                                        class="ui-action-icon bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100 w-8 h-8 flex items-center justify-center rounded"
                                        title="Lihat Komponen"
                                        onclick="toggleIngredients({{ $bom->id }})">
                                        <i class="fas fa-chevron-down transform transition-transform duration-200" id="icon-{{ $bom->id }}"></i>
                                    </button>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center shrink-0">
                                            @if($bom->product->image_url)
                                                <img src="{{ $bom->product->image_url }}" alt=""
                                                    class="w-full h-full object-cover rounded-lg">
                                            @else
                                                <i class="fas fa-box text-gray-400"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900">{{ $bom->product->name }}</div>
                                            <div
                                                class="text-xs text-gray-500 font-mono bg-gray-100 px-1.5 py-0.5 rounded inline-block mt-0.5">
                                                {{ $bom->product->sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($bom->name)
                                        <span class="font-medium text-gray-800">{{ $bom->name }}</span>
                                    @else
                                        <span class="text-gray-400 italic text-sm">- Default -</span>
                                    @endif
                                </td>
                                <td>
                                    @if($bom->is_active)
                                        <span class="badge badge-success shadow-sm">
                                            <i class="fas fa-check-circle mr-1 text-[10px]"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge badge-gray shadow-sm">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span
                                        class="bg-gray-100 text-gray-700 text-xs font-semibold px-2.5 py-1 rounded-md border border-gray-200">
                                        {{ $bom->details_count ?? 0 }} item
                                    </span>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <div class="flex flex-col">
                                        <span>{{ $bom->updated_at->format('d M Y') }}</span>
                                        <span class="text-xs text-gray-400">{{ $bom->updated_at->format('H:i') }}</span>
                                    </div>
                                </td>
                                <td class="text-center pr-6">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.boms.show', ['bom' => $bom, 'source_type' => $activeSourceType, 'return_to' => request()->fullUrl()]) }}"
                                            class="ui-action-icon ui-action-view"
                                            title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.boms.edit', ['bom' => $bom, 'source_type' => $activeSourceType, 'return_to' => request()->fullUrl()]) }}"
                                            class="ui-action-icon ui-action-edit"
                                            title="Edit Resep">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.boms.destroy', ['bom' => $bom, 'source_type' => $activeSourceType, 'return_to' => request()->fullUrl()]) }}" method="POST"
                                            onsubmit="return confirm('Yakin hapus BOM ini? Aksi ini tidak dapat dibatalkan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="ui-action-icon ui-action-delete"
                                                title="Hapus Permanen">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            {{-- Collapsible Ingredient Row --}}
                            <tr id="ingredients-{{ $bom->id }}" class="hidden bg-gray-50/50 border-b border-gray-100 no-hover">
                                <td colspan="8" class="px-6 py-4">
                                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                            <span class="text-xs font-bold uppercase tracking-wider text-gray-600">Daftar Komponen / Bahan</span>
                                            <span class="text-xs text-gray-500">{{ $bom->details->count() }} item terdaftar</span>
                                        </div>
                                        <div class="p-0 overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-100/50 text-gray-600">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-semibold">Nama Bahan</th>
                                                        <th class="px-4 py-2 text-left font-semibold">SKU</th>
                                                        <th class="px-4 py-2 text-right font-semibold">Jumlah</th>
                                                        <th class="px-4 py-2 text-left font-semibold">Satuan</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($bom->details as $detail)
                                                        <tr>
                                                            <td class="px-4 py-2 font-medium text-gray-800">{{ $detail->component->name ?? '-' }}</td>
                                                            <td class="px-4 py-2 text-gray-500 font-mono text-xs">{{ $detail->component->sku ?? '-' }}</td>
                                                            <td class="px-4 py-2 text-right font-bold text-blue-600">{{ (float) $detail->quantity }}</td>
                                                            <td class="px-4 py-2 text-gray-600">{{ $detail->uom ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="fas fa-scroll text-3xl text-gray-300"></i>
                                        </div>
                                        <h3 class="text-gray-900 font-medium mb-1">Belum ada Resep Produk</h3>
                                        <p class="text-gray-500 text-sm mb-4">Mulai dengan membuat BOM untuk produk Anda.</p>
                                        <a href="{{ route('admin.boms.create') }}" class="ui-btn ui-btn-primary btn btn-primary btn-sm">
                                            <i class="fas fa-plus"></i> Buat Resep Baru
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($boms->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    {{ $boms->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function toggleIngredients(id) {
        const row = document.getElementById('ingredients-' + id);
        const icon = document.getElementById('icon-' + id);
        
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            row.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
@endpush
