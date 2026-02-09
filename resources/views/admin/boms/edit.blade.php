@extends('layouts.admin')

@section('title', 'Edit BOM')
@section('page-title', 'Edit Resep Produk')

@section('content')
    @php
        $components = old('components');
        if (!$components) {
            $components = $bom->details->map(function ($detail) {
                return [
                    'component_product_id' => $detail->component_product_id,
                    'quantity' => $detail->quantity,
                    'uom' => $detail->uom,
                ];
            })->toArray();
        }

        if (empty($components)) {
            $components = [['component_product_id' => '', 'quantity' => '', 'uom' => '']];
        }
    @endphp

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">Kelola komposisi bahan untuk produk</p>
                <h1 class="text-2xl font-semibold text-slate-900">Edit Bill of Materials</h1>
            </div>
            <a href="{{ route('admin.boms.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg p-4">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.boms.update', $bom) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="bg-white border border-slate-200 rounded-lg p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="form-label">Produk Utama</label>
                        <input type="text" class="form-input bg-gray-100" value="{{ $bom->product->name }} ({{ $bom->product->sku }})" readonly>
                    </div>

                    <div>
                        <label for="name" class="form-label">Nama BOM</label>
                        <input type="text" name="name" id="name" class="form-input" value="{{ old('name', $bom->name) }}" placeholder="Contoh: Resep Es Kopi Susu">
                    </div>
                </div>

                <div>
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea name="notes" id="notes" rows="3" class="form-input" placeholder="Catatan tambahan">{{ old('notes', $bom->notes) }}</textarea>
                </div>

                <div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            {{ old('is_active', $bom->is_active ? 1 : 0) ? 'checked' : '' }}>
                        <span class="text-sm text-slate-700">BOM Aktif</span>
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-slate-900">Komponen Bahan</h3>
                        <button type="button" id="add-component" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Tambah Komponen
                        </button>
                    </div>

                    <div id="components-container" class="space-y-3">
                        @foreach($components as $index => $component)
                            <div class="component-row border border-slate-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                                    <div class="md:col-span-6">
                                        <label class="form-label">Bahan</label>
                                        <select name="components[{{ $index }}][component_product_id]" class="form-input" required>
                                            <option value="">Pilih bahan...</option>
                                            @foreach($rawMaterials as $raw)
                                                <option value="{{ $raw->id }}" {{ (string)($component['component_product_id'] ?? '') === (string)$raw->id ? 'selected' : '' }}>
                                                    {{ $raw->name }} ({{ $raw->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="form-label">Jumlah</label>
                                        <input type="number" name="components[{{ $index }}][quantity]" class="form-input"
                                            min="0.0001" step="0.0001" value="{{ $component['quantity'] ?? '' }}" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Satuan</label>
                                        <input type="text" name="components[{{ $index }}][uom]" class="form-input"
                                            value="{{ $component['uom'] ?? '' }}" placeholder="gram/ml/pcs">
                                    </div>
                                    <div class="md:col-span-1 flex items-end">
                                        <button type="button" class="remove-component p-2 text-red-600 hover:text-red-800" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.boms.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update BOM
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('components-container');
            const addButton = document.getElementById('add-component');
            let index = container.querySelectorAll('.component-row').length;

            const rawOptions = @json($rawMaterials->map(function ($raw) {
                return ['id' => $raw->id, 'name' => $raw->name, 'sku' => $raw->sku];
            }));

            function buildOptionsHtml() {
                let html = '<option value="">Pilih bahan...</option>';
                rawOptions.forEach(function (raw) {
                    html += `<option value="${raw.id}">${raw.name} (${raw.sku})</option>`;
                });
                return html;
            }

            function buildRowHtml(rowIndex) {
                return `
                    <div class="component-row border border-slate-200 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-6">
                                <label class="form-label">Bahan</label>
                                <select name="components[${rowIndex}][component_product_id]" class="form-input" required>
                                    ${buildOptionsHtml()}
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="components[${rowIndex}][quantity]" class="form-input" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="components[${rowIndex}][uom]" class="form-input" placeholder="gram/ml/pcs">
                            </div>
                            <div class="md:col-span-1 flex items-end">
                                <button type="button" class="remove-component p-2 text-red-600 hover:text-red-800" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            addButton.addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', buildRowHtml(index));
                index++;
            });

            container.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-component');
                if (!button) {
                    return;
                }

                if (container.querySelectorAll('.component-row').length <= 1) {
                    alert('Minimal harus ada 1 komponen.');
                    return;
                }

                button.closest('.component-row')?.remove();
            });
        });
    </script>
@endpush
