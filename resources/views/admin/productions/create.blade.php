@extends('layouts.admin')

@section('title', 'Buat Produksi')
@section('page-title', 'Buat Produksi')
@section('page-subtitle', 'Eksekusi BOM produksi menjadi stok hasil produksi')

@section('content')
<div class="w-full max-w-5xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Buat Produksi</h1>
            <p class="text-xs font-normal text-slate-500 mt-0.5">Pilih BOM produksi, isi jumlah hasil, lalu stok bahan akan berkurang otomatis.</p>
        </div>
        <a href="{{ route('admin.productions.index') }}" class="ui-btn ui-btn-secondary inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs text-slate-600">
            <i class="fas fa-arrow-left text-[10px]"></i>
            <span>Kembali</span>
        </a>
    </div>

    @if($boms->isEmpty())
        <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
            <i class="fas fa-clipboard-list text-4xl text-slate-300 mb-4"></i>
            <p class="text-sm text-slate-600">Belum ada BOM produksi aktif.</p>
            <a href="{{ route('admin.boms.create', ['source_type' => 'production']) }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs text-white">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Buat BOM Produksi</span>
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('admin.productions.store') }}" class="space-y-6">
            @csrf
            <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                    <i class="fas fa-flask text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Data Produksi</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">BOM Produksi</label>
                        <select name="bom_id" id="bom_id" required class="ui-input w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg">
                            <option value="">Pilih resep produksi</option>
                            @foreach($boms as $bom)
                                <option value="{{ $bom->id }}" @selected(old('bom_id') == $bom->id)>
                                    {{ $bom->product->name ?? '-' }} ({{ $bom->product->sku ?? '-' }}) - {{ $bom->name ?: 'Resep Produksi' }}
                                </option>
                            @endforeach
                        </select>
                        @error('bom_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Outlet Produksi</label>
                        <select name="outlet_id" required class="ui-input w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg">
                            <option value="">Pilih outlet/gudang</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected(old('outlet_id') == $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                        @error('outlet_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tanggal Produksi</label>
                        <input type="date" name="production_date" value="{{ old('production_date', now()->toDateString()) }}" required class="ui-input w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg">
                        @error('production_date') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Jumlah Hasil Produksi</label>
                        <input type="number" step="0.0001" min="0.0001" name="quantity" value="{{ old('quantity') }}" required placeholder="Contoh: 50" class="ui-input w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg">
                        @error('quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-col gap-1.5 md:col-span-2">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Catatan</label>
                        <textarea name="notes" rows="3" class="ui-input w-full px-3 py-2 text-sm bg-white border border-slate-200 rounded-lg" placeholder="Contoh: Marinasi batch pagi">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center gap-2">
                    <i class="fas fa-list-check text-indigo-500 text-[10px]"></i>
                    <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Komposisi BOM Terpilih</h3>
                </div>
                <div id="bom-preview" class="p-6 text-sm text-slate-500">
                    Pilih BOM produksi untuk melihat bahan yang akan dipakai per 1 hasil.
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.productions.index') }}" class="ui-btn ui-btn-secondary inline-flex items-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs text-slate-600">Batal</a>
                <button type="submit" class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2 text-xs text-white shadow-sm hover:bg-indigo-700">
                    <i class="fas fa-check text-[10px]"></i>
                    <span>Proses Produksi</span>
                </button>
            </div>
        </form>
    @endif
</div>

@if($boms->isNotEmpty())
<script>
    const productionBoms = @json($boms->mapWithKeys(function ($bom) {
        return [
            $bom->id => [
                'product' => $bom->product?->name,
                'unit' => $bom->product?->unit,
                'materials' => $bom->details->map(function ($detail) {
                    return [
                        'name' => $detail->component?->name,
                        'sku' => $detail->component?->sku,
                        'quantity' => (float) $detail->quantity,
                        'uom' => $detail->uom ?: $detail->component?->unit,
                    ];
                })->values(),
            ],
        ];
    }));

    const bomSelect = document.getElementById('bom_id');
    const preview = document.getElementById('bom-preview');

    function renderBomPreview() {
        const selected = productionBoms[bomSelect.value];
        if (!selected) {
            preview.innerHTML = 'Pilih BOM produksi untuk melihat bahan yang akan dipakai per 1 hasil.';
            return;
        }

        const rows = selected.materials.map((material) => `
            <tr class="border-b border-slate-100 last:border-0">
                <td class="py-2 pr-4">
                    <div class="text-slate-800">${material.name ?? '-'}</div>
                    <div class="text-[10px] text-slate-400 uppercase tracking-widest">${material.sku ?? ''}</div>
                </td>
                <td class="py-2 text-right text-slate-700">${material.quantity} ${material.uom ?? ''}</td>
            </tr>
        `).join('');

        preview.innerHTML = `
            <div class="mb-3 text-xs text-slate-500">Output: <span class="font-semibold text-slate-800">${selected.product}</span> per 1 ${selected.unit ?? ''}</div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-2 text-left text-[10px] uppercase tracking-widest text-slate-400 font-normal">Bahan</th>
                        <th class="py-2 text-right text-[10px] uppercase tracking-widest text-slate-400 font-normal">Qty per hasil</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        `;
    }

    bomSelect.addEventListener('change', renderBomPreview);
    renderBomPreview();
</script>
@endif
@endsection
