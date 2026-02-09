@extends('layouts.admin')

@section('title', ($sourceType ?? ($bom->source_type ?? 'bundle')) === 'bundle' ? 'Detail Resep Bundle' : 'Detail BOM Produksi')
@section('page-title', 'Detail Resep Produk')

@section('content')
    @php
        $recipeSourceType = $sourceType ?? ($bom->source_type ?? 'bundle');
        $isBundleRecipe = $recipeSourceType === 'bundle';
        $defaultBackUrl = $isBundleRecipe ? route('admin.products.index') : route('admin.boms.index', ['source_type' => 'production']);
        $backUrl = $returnTo ?? $defaultBackUrl;
    @endphp

    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">
                    {{ $isBundleRecipe ? 'Ringkasan resep bundle/menu jual' : 'Ringkasan resep produksi' }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900">{{ $bom->name ?: 'Resep ' . ($bom->product->name ?? '-') }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.boms.edit', ['bom' => $bom, 'source_type' => $recipeSourceType, 'return_to' => request()->fullUrl()]) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Resep
                </a>
                <a href="{{ $backUrl }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-slate-500">Produk</div>
                    <div class="font-medium text-slate-900">{{ $bom->product->name ?? '-' }} ({{ $bom->product->sku ?? '-' }})</div>
                </div>
                <div>
                    <div class="text-slate-500">Status</div>
                    <div>
                        @if($bom->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">Aktif</span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">Nonaktif</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-slate-500">Catatan</div>
                    <div class="text-slate-900">{{ $bom->notes ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-slate-500">Jenis Resep</div>
                    <div class="font-medium text-slate-900">{{ $isBundleRecipe ? 'Bundle' : 'Produksi' }}</div>
                </div>
                <div>
                    <div class="text-slate-500">Total Komponen</div>
                    <div class="font-medium text-slate-900">{{ $bom->details->count() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Daftar Komponen</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="text-left px-6 py-3 font-semibold text-slate-700">Bahan</th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-700">SKU</th>
                            <th class="text-right px-6 py-3 font-semibold text-slate-700">Qty</th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-700">UOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bom->details as $detail)
                            <tr class="border-t border-slate-100">
                                <td class="px-6 py-3">{{ $detail->component->name ?? '-' }}</td>
                                <td class="px-6 py-3 font-mono text-xs">{{ $detail->component->sku ?? '-' }}</td>
                                <td class="px-6 py-3 text-right">{{ rtrim(rtrim(number_format((float)$detail->quantity, 4, '.', ''), '0'), '.') }}</td>
                                <td class="px-6 py-3">{{ $detail->uom ?: ($detail->component->unit ?? '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-500">Belum ada komponen.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
