@extends('layouts.admin')

@section('title', ($sourceType ?? ($bom->source_type ?? 'bundle')) === 'bundle' ? 'Edit Resep Bundle' : 'Edit BOM Produksi')
@section('page-title', 'Edit Resep Produk')

@section('content')
    @php
        $recipeSourceType = $sourceType ?? ($bom->source_type ?? 'bundle');
        $isBundleRecipe = $recipeSourceType === 'bundle';
        $defaultBackUrl = $isBundleRecipe ? route('admin.products.index') : route('admin.boms.index', ['source_type' => 'production']);
        $backUrl = $returnTo ?? $defaultBackUrl;

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

        $rawMaterialsForJs = $rawMaterials->map(function ($raw) {
            return [
                'id' => (string) $raw->id,
                'name' => (string) $raw->name,
                'sku' => (string) $raw->sku,
                'unit' => (string) ($raw->unit ?? ''),
                'purchase_price' => (float) ($raw->purchase_price ?? 0),
            ];
        })->values()->all();
    @endphp

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-slate-500">
                    {{ $isBundleRecipe ? 'Kelola komponen resep bundle/menu jual' : 'Kelola komposisi bahan untuk produksi' }}
                </p>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ $isBundleRecipe ? 'Edit Resep Bundle' : 'Edit Resep Produksi (BOM)' }}
                </h1>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-secondary">
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
            <input type="hidden" name="source_type" value="{{ $recipeSourceType }}">
            <input type="hidden" name="return_to" value="{{ $backUrl }}">

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
                                    <div class="md:col-span-5">
                                        <label class="form-label">Bahan</label>
                                        <select name="components[{{ $index }}][component_product_id]" class="form-input" required>
                                            <option value="">Pilih bahan...</option>
                                            @foreach($rawMaterials as $raw)
                                                <option value="{{ $raw->id }}"
                                                    data-unit="{{ $raw->unit }}"
                                                    data-purchase-price="{{ (float) ($raw->purchase_price ?? 0) }}"
                                                    {{ (string)($component['component_product_id'] ?? '') === (string)$raw->id ? 'selected' : '' }}>
                                                    {{ $raw->name }} ({{ $raw->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Jumlah</label>
                                        <input type="number" name="components[{{ $index }}][quantity]" class="form-input"
                                            min="0.0001" step="0.0001" value="{{ $component['quantity'] ?? '' }}" required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Satuan</label>
                                        <input type="text" name="components[{{ $index }}][uom]" class="form-input"
                                            value="{{ $component['uom'] ?? '' }}" placeholder="gram/ml/pcs">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="form-label">Biaya</label>
                                        <div class="form-input bg-slate-50 text-slate-700 font-semibold component-cost flex items-center">
                                            Rp 0,00
                                        </div>
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

                    <div class="flex justify-end">
                        <div class="px-4 py-2 border border-slate-200 rounded-lg bg-slate-50">
                            <span class="text-sm text-slate-600 mr-2">Total Harga Modal:</span>
                            <span id="components-total-cost" class="text-base font-bold text-slate-900">Rp 0,00</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ $backUrl }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ $isBundleRecipe ? 'Update Resep Bundle' : 'Update Resep Produksi' }}
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
            const totalCostDisplay = document.getElementById('components-total-cost');
            let index = container.querySelectorAll('.component-row').length;

            const rawOptions = @json($rawMaterialsForJs);
            const rawMetaById = rawOptions.reduce((acc, raw) => {
                acc[String(raw.id)] = raw;
                return acc;
            }, {});

            function parseNumber(value) {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value);
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function getSelectFromRow(row) {
                return row.querySelector('select[name*="[component_product_id]"]');
            }

            function getQuantityInputFromRow(row) {
                return row.querySelector('input[name*="[quantity]"]');
            }

            function getUomInputFromRow(row) {
                return row.querySelector('input[name*="[uom]"]');
            }

            function getSelectedRawMeta(row) {
                const select = getSelectFromRow(row);
                const selectedId = String(select?.value || '');
                return selectedId ? (rawMetaById[selectedId] || null) : null;
            }

            function autoFillUnit(row) {
                const meta = getSelectedRawMeta(row);
                const uomInput = getUomInputFromRow(row);
                if (meta && uomInput && !uomInput.value) {
                    uomInput.value = meta.unit || '';
                }
            }

            function updateRowCost(row) {
                const quantityInput = getQuantityInputFromRow(row);
                const costLabel = row.querySelector('.component-cost');
                const quantity = parseNumber(quantityInput?.value);
                const meta = getSelectedRawMeta(row);
                const purchasePrice = parseNumber(meta?.purchase_price);
                const rowCost = quantity * purchasePrice;

                if (costLabel) {
                    costLabel.textContent = formatCurrency(rowCost);
                }

                row.dataset.rowCost = String(rowCost);
                return rowCost;
            }

            function updateAllCosts() {
                let totalCost = 0;
                container.querySelectorAll('.component-row').forEach((row) => {
                    totalCost += updateRowCost(row);
                });

                if (totalCostDisplay) {
                    totalCostDisplay.textContent = formatCurrency(totalCost);
                }
            }

            function buildOptionsHtml() {
                let html = '<option value="">Pilih bahan...</option>';
                rawOptions.forEach(function (raw) {
                    html += `<option value="${escapeHtml(raw.id)}" data-unit="${escapeHtml(raw.unit)}" data-purchase-price="${escapeHtml(raw.purchase_price)}">${escapeHtml(raw.name)} (${escapeHtml(raw.sku)})</option>`;
                });
                return html;
            }

            function buildRowHtml(rowIndex) {
                return `
                    <div class="component-row border border-slate-200 rounded-lg p-4">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <div class="md:col-span-5">
                                <label class="form-label">Bahan</label>
                                <select name="components[${rowIndex}][component_product_id]" class="form-input" required>
                                    ${buildOptionsHtml()}
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="components[${rowIndex}][quantity]" class="form-input" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Satuan</label>
                                <input type="text" name="components[${rowIndex}][uom]" class="form-input" placeholder="gram/ml/pcs">
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">Biaya</label>
                                <div class="form-input bg-slate-50 text-slate-700 font-semibold component-cost flex items-center">Rp 0,00</div>
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

            function bindRowEvents(row) {
                if (row.dataset.bound === '1') {
                    return;
                }

                const select = getSelectFromRow(row);
                const quantityInput = getQuantityInputFromRow(row);

                if (select) {
                    select.addEventListener('change', function () {
                        autoFillUnit(row);
                        updateAllCosts();
                    });
                }

                if (quantityInput) {
                    quantityInput.addEventListener('input', function () {
                        updateAllCosts();
                    });
                }

                row.dataset.bound = '1';
            }

            function initExistingRows() {
                container.querySelectorAll('.component-row').forEach((row) => {
                    autoFillUnit(row);
                    bindRowEvents(row);
                });
                updateAllCosts();
            }

            addButton.addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', buildRowHtml(index));
                const newRow = container.lastElementChild;
                if (newRow) {
                    bindRowEvents(newRow);
                }
                index++;
                updateAllCosts();
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
                updateAllCosts();
            });

            initExistingRows();
        });
    </script>
@endpush
