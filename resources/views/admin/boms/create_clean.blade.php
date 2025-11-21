@extends('layouts.admin')

@section('title', 'Tambah BOM')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-500">Atur komposisi produk jadi</p>
            <h1 class="text-2xl font-semibold text-slate-900">Bill of Materials</h1>
        </div>
        <a href="{{ route('admin.boms.index') }}" class="imperial-btn-secondary imperial-btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4">
            <div class="font-semibold mb-2">Periksa formulir:</div>
            <ul class="list-disc list-inside space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.boms.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-2xl shadow-premium border border-slate-200 p-6 space-y-6">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Produk Utama <span class="text-rose-500">*</span>
                        </label>
                        <select name="product_id" id="product_id"
                                class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                required>
                            <option value="">Pilih produk...</option>
                            @foreach($finishedProducts as $product)
                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->sku }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama BOM</label>
                        <input type="text" name="name" id="name"
                               class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                               placeholder="Contoh: Resep Nasi Goreng Spesial"
                               value="{{ old('name') }}">
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                  placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                    </div>

                    <label class="inline-flex items-center gap-2 select-none">
                        <input type="checkbox" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500"
                               id="is_active" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                        <span class="text-sm text-slate-700">Aktif</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-amber-600 font-semibold">Komponen</p>
                            <h2 class="text-lg font-semibold text-slate-900">Bahan Penyusun</h2>
                        </div>
                        <button type="button" id="add-component" class="imperial-btn-success imperial-btn-sm">
                            <i class="fas fa-plus"></i> Komponen
                        </button>
                    </div>

                    <div id="components-container" class="space-y-3">
                        @if(old('components'))
                            @foreach(old('components') as $index => $component)
                                <div class="component-row bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                                    <div class="grid gap-3 md:grid-cols-12">
                                        <div class="md:col-span-6">
                                            <label class="text-sm font-medium text-slate-700 mb-1 block">Bahan/Komponen</label>
                                            <select name="components[{{ $index }}][component_product_id]"
                                                    class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                    required>
                                                <option value="">Pilih bahan...</option>
                                                @foreach($rawMaterials as $raw)
                                                    <option value="{{ $raw->id }}" {{ $component['component_product_id'] == $raw->id ? 'selected' : '' }}>
                                                        {{ $raw->name }} ({{ $raw->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="text-sm font-medium text-slate-700 mb-1 block">Jumlah</label>
                                            <input type="number" name="components[{{ $index }}][quantity]"
                                                   class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                   step="0.0001" min="0.0001"
                                                   value="{{ $component['quantity'] }}" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-sm font-medium text-slate-700 mb-1 block">Satuan</label>
                                            <input type="text" name="components[{{ $index }}][uom]"
                                                   class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                   placeholder="kg, pcs, liter..."
                                                   value="{{ $component['uom'] ?? '' }}">
                                        </div>
                                        <div class="md:col-span-1 flex items-end">
                                            <button type="button" class="imperial-btn-danger w-full remove-component">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="component-row bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                                <div class="grid gap-3 md:grid-cols-12">
                                    <div class="md:col-span-6">
                                        <label class="text-sm font-medium text-slate-700 mb-1 block">Bahan/Komponen</label>
                                        <select name="components[0][component_product_id]"
                                                class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                required>
                                            <option value="">Pilih bahan...</option>
                                            @foreach($rawMaterials as $raw)
                                                <option value="{{ $raw->id }}">{{ $raw->name }} ({{ $raw->sku }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="text-sm font-medium text-slate-700 mb-1 block">Jumlah</label>
                                        <input type="number" name="components[0][quantity]"
                                               class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                               step="0.0001" min="0.0001" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-medium text-slate-700 mb-1 block">Satuan</label>
                                        <input type="text" name="components[0][uom]"
                                               class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                               placeholder="kg, pcs, liter...">
                                    </div>
                                    <div class="md:col-span-1 flex items-end">
                                        <button type="button" class="imperial-btn-danger w-full remove-component">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <p class="text-xs text-slate-500">Minimal harus ada satu komponen. Gunakan tombol tambah untuk menambah baris.</p>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.boms.index') }}" class="imperial-btn-secondary">Batal</a>
                <button type="submit" class="imperial-btn">
                    <i class="fas fa-save"></i> Simpan BOM
                </button>
            </div>
        </div>
    </form>
</div>

<script type="application/json" id="raw-materials-data">
{!! json_encode($rawMaterials->map(fn($raw) => ['id' => $raw->id, 'name' => $raw->name, 'sku' => $raw->sku])) !!}
</script>
<script type="application/json" id="component-index-data">
{{ old('components') ? count(old('components')) : 1 }}
</script>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawMaterials = JSON.parse(document.getElementById('raw-materials-data').textContent || '[]');
    let componentIndex = parseInt(document.getElementById('component-index-data').textContent || '1', 10);
    const container = document.getElementById('components-container');
    const addBtn = document.getElementById('add-component');

    function buildRawOptions(selectedId) {
        return rawMaterials.map(raw => {
            const selected = Number(selectedId) === Number(raw.id) ? 'selected' : '';
            return `<option value="${raw.id}" ${selected}>${raw.name} (${raw.sku})</option>`;
        }).join('');
    }

    function createComponentRow(index, data = {}) {
        return `
            <div class="component-row bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="md:col-span-6">
                        <label class="text-sm font-medium text-slate-700 mb-1 block">Bahan/Komponen</label>
                        <select name="components[${index}][component_product_id]"
                                class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                required>
                            <option value="">Pilih bahan...</option>
                            ${buildRawOptions(data.component_product_id)}
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="text-sm font-medium text-slate-700 mb-1 block">Jumlah</label>
                        <input type="number" name="components[${index}][quantity]"
                               class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                               step="0.0001" min="0.0001" value="${data.quantity ?? ''}" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-slate-700 mb-1 block">Satuan</label>
                        <input type="text" name="components[${index}][uom]"
                               class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                               placeholder="kg, pcs, liter..." value="${data.uom ?? ''}">
                    </div>
                    <div class="md:col-span-1 flex items-end">
                        <button type="button" class="imperial-btn-danger w-full remove-component">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    addBtn?.addEventListener('click', function () {
        const newRow = createComponentRow(componentIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        componentIndex++;
    });

    container?.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-component');
        if (!btn) return;

        const rows = container.querySelectorAll('.component-row');
        if (rows.length <= 1) {
            alert('Minimal harus ada 1 komponen.');
            return;
        }
        btn.closest('.component-row')?.remove();
    });
});
</script>
@endpush
