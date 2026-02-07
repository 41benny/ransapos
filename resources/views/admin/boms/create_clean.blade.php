@extends('layouts.admin')

@section('title', 'Tambah BOM')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Atur komposisi produk jadi</p>
                <h1 class="text-2xl font-semibold text-slate-900">Bill of Materials (v1.6)</h1>
            </div>
            <a href="{{ route('admin.boms.index') }}" class="btn btn-secondary !rounded-none">
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

        @php
            $rawMap = $rawMaterials->keyBy('id');
            $finishedMap = $finishedProducts->keyBy('id');
            $selectedFinished = $finishedMap->get(old('product_id'));
            $selectedFinishedLabel = $selectedFinished ? ($selectedFinished->name . ' (' . $selectedFinished->sku . ')') : '';
        @endphp

        <form action="{{ route('admin.boms.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="bg-white !rounded-none shadow-premium border border-slate-200 p-6 space-y-6">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-4">
                        <div class="relative" data-autocomplete-wrap>
                            <label for="product_id" class="form-label">
                                Produk Utama <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="product_id"
                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
                                placeholder="Ketik produk..." value="{{ $selectedFinishedLabel }}" data-finished-input required>
                            <input type="hidden" name="product_id" value="{{ old('product_id') }}" data-finished-id>
                            <div class="autocomplete-panel absolute z-30 mt-1 w-full max-h-64 overflow-auto !rounded-none border border-slate-200 bg-white shadow-lg text-sm hidden"
                                data-autocomplete-panel></div>
                        </div>

                        <div>
                            <label for="name" class="form-label">Nama BOM</label>
                            <input type="text" name="name" id="name"
                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
                                placeholder="Contoh: Resep Nasi Goreng Spesial" value="{{ old('name') }}">
                        </div>

                        <div>
                            <label for="notes" class="form-label">Catatan</label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
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
                            <button type="button" id="add-component"
                                class="btn btn-secondary !rounded-none">
                                <i class="fas fa-plus"></i> Tambah Komponen
                            </button>
                        </div>

                        <div id="components-container" class="space-y-3">
                            @if(old('components'))
                                @foreach(old('components') as $index => $component)
                                    <div class="component-row bg-white border border-slate-200 !rounded-none p-4 shadow-sm">
                                        <div class="grid gap-3 md:grid-cols-12">
                                            <div class="md:col-span-6 relative" data-autocomplete-wrap>
                                                <label class="form-label mb-1 block">Bahan/Komponen</label>
                                                @php
                                                    $selectedRaw = $rawMap->get($component['component_product_id'] ?? null);
                                                    $selectedLabel = $selectedRaw ? ($selectedRaw->name . ' (' . $selectedRaw->sku . ')') : '';
                                                @endphp
                                                <input type="text"
                                                    class="w-full form-input !rounded-none"
                                                    placeholder="Ketik bahan..." value="{{ $selectedLabel }}" data-raw-input required>
                                                <input type="hidden" name="components[{{ $index }}][component_product_id]"
                                                    value="{{ $component['component_product_id'] ?? '' }}" data-raw-id>
                                                <div class="autocomplete-panel absolute z-30 mt-1 w-full max-h-64 overflow-auto !rounded-none border border-slate-200 bg-white shadow-lg text-sm hidden"
                                                    data-autocomplete-panel></div>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="form-label mb-1 block">Jumlah</label>
                                                <input type="number" name="components[{{ $index }}][quantity]"
                                                    class="w-full form-input !rounded-none"
                                                    step="0.0001" min="0.0001" value="{{ $component['quantity'] }}" required>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="form-label mb-1 block">Satuan</label>
                                                <input type="text" name="components[{{ $index }}][uom]"
                                                    class="w-full form-input !rounded-none"
                                                    placeholder="kg, pcs, liter..." value="{{ $component['uom'] ?? '' }}">
                                            </div>
                                            <div class="md:col-span-1 flex items-end">
                                                <button type="button" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 !rounded-none transition-colors duration-150 remove-component flex items-center justify-center" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="component-row bg-white border border-slate-200 !rounded-none p-4 shadow-sm">
                                    <div class="grid gap-3 md:grid-cols-12">
                                        <div class="md:col-span-6 relative" data-autocomplete-wrap>
                                            <label class="form-label mb-1 block">Bahan/Komponen</label>
                                            <input type="text"
                                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
                                                placeholder="Ketik bahan..." data-raw-input required>
                                            <input type="hidden" name="components[0][component_product_id]" value="" data-raw-id>
                                            <div class="autocomplete-panel absolute z-30 mt-1 w-full max-h-64 overflow-auto !rounded-none border border-slate-200 bg-white shadow-lg text-sm hidden"
                                                data-autocomplete-panel></div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="form-label mb-1 block">Jumlah</label>
                                            <input type="number" name="components[0][quantity]"
                                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
                                                step="0.0001" min="0.0001" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="form-label mb-1 block">Satuan</label>
                                            <input type="text" name="components[0][uom]"
                                                class="w-full form-input !rounded-none bg-gray-50 shadow-sm transition-colors"
                                                placeholder="kg, pcs, liter...">
                                        </div>
                                        <div class="md:col-span-1 flex items-end">
                                            <button type="button" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 !rounded-none transition-colors duration-150 remove-component flex items-center justify-center" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <p class="text-xs text-slate-500">Minimal harus ada satu komponen. Gunakan tombol tambah untuk
                            menambah baris.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.boms.index') }}"
                        class="btn btn-secondary !rounded-none">Batal</a>
                    <button type="submit"
                        class="btn btn-primary !rounded-none">
                        <i class="fas fa-save"></i> Simpan BOM
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script type="application/json" id="raw-materials-data">
    {!! json_encode($rawMaterials->map(function ($raw) { return ['id' => $raw->id, 'name' => $raw->name, 'sku' => $raw->sku]; })) !!}
    </script>
    <script type="application/json" id="component-index-data">
    {{ old('components') ? count(old('components')) : 1 }}
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const rawMaterials = JSON.parse(document.getElementById('raw-materials-data').textContent || '[]');
            const rawItems = rawMaterials.map(raw => ({
                id: String(raw.id),
                label: `${raw.name} (${raw.sku})`,
                search: `${raw.name} ${raw.sku}`.toLowerCase()
            }));
            const rawIdByLabel = new Map(rawItems.map(raw => [raw.label, raw.id]));
            const rawLabelById = new Map(rawItems.map(raw => [raw.id, raw.label]));
            const finishedProducts = @json($finishedProducts->map(function ($product) { return ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku]; }));
            const finishedItems = finishedProducts.map(product => ({
                id: String(product.id),
                label: `${product.name} (${product.sku})`,
                search: `${product.name} ${product.sku}`.toLowerCase()
            }));
            const finishedIdByLabel = new Map(finishedItems.map(product => [product.label, product.id]));
            const finishedLabelById = new Map(finishedItems.map(product => [product.id, product.label]));
            let componentIndex = parseInt(document.getElementById('component-index-data').textContent || '1', 10);
            const container = document.getElementById('components-container');
            const addBtn = document.getElementById('add-component');
            const form = container?.closest('form');
            const finishedInput = document.querySelector('[data-finished-input]');
            const finishedHidden = document.querySelector('[data-finished-id]');

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function syncSelection(input, hidden, mapByLabel, label) {
                const mapped = mapByLabel.get(input.value) || '';
                hidden.value = mapped;
                if (input.value && !mapped) {
                    input.setCustomValidity(`Pilih ${label} dari daftar.`);
                } else {
                    input.setCustomValidity('');
                }
            }

            function initAutocomplete(input, hidden, items, mapByLabel, label) {
                if (!input || !hidden || input.dataset.autocompleteReady) return;
                const wrap = input.closest('[data-autocomplete-wrap]');
                const panel = wrap?.querySelector('[data-autocomplete-panel]');
                if (!panel) return;
                input.dataset.autocompleteReady = 'true';

                function renderList() {
                    const query = input.value.trim().toLowerCase();
                    const results = query
                        ? items.filter(item => item.search.includes(query)).slice(0, 40)
                        : items.slice(0, 40);
                    if (results.length === 0) {
                        panel.innerHTML = '<div class="px-3 py-2 text-slate-500">Tidak ada hasil.</div>';
                    } else {
                        panel.innerHTML = results.map(item =>
                            `<button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-100" data-id="${item.id}">${escapeHtml(item.label)}</button>`
                        ).join('');
                    }
                    panel.classList.remove('hidden');
                }

                function hideList() {
                    panel.classList.add('hidden');
                }

                input.addEventListener('focus', renderList);
                input.addEventListener('input', function () {
                    renderList();
                    syncSelection(input, hidden, mapByLabel, label);
                });
                input.addEventListener('blur', function () {
                    setTimeout(hideList, 150);
                    syncSelection(input, hidden, mapByLabel, label);
                });

                panel.addEventListener('mousedown', function (event) {
                    const target = event.target.closest('[data-id]');
                    if (!target) return;
                    const picked = items.find(item => item.id === target.dataset.id);
                    if (!picked) return;
                    input.value = picked.label;
                    hidden.value = picked.id;
                    input.setCustomValidity('');
                    hideList();
                });

                syncSelection(input, hidden, mapByLabel, label);
            }

            function createComponentRow(index, data = {}) {
                return `
                <div class="component-row bg-white border border-slate-200 !rounded-none p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-12">
                        <div class="md:col-span-6 relative" data-autocomplete-wrap>
                            <label class="form-label mb-1 block">Bahan/Komponen</label>
                            <input type="text"
                                   class="w-full form-input !rounded-none bg-gray-50 text-sm shadow-sm"
                                   placeholder="Ketik bahan..." data-raw-input required>
                            <input type="hidden" name="components[${index}][component_product_id]" value="${data.component_product_id ?? ''}" data-raw-id>
                            <div class="autocomplete-panel absolute z-30 mt-1 w-full max-h-64 overflow-auto !rounded-none border border-slate-200 bg-white shadow-lg text-sm hidden"
                                data-autocomplete-panel></div>
                        </div>
                        <div class="md:col-span-3">
                            <label class="form-label mb-1 block">Jumlah</label>
                            <input type="number" name="components[${index}][quantity]"
                                   class="w-full form-input !rounded-none bg-gray-50 text-sm shadow-sm"
                                   step="0.0001" min="0.0001" value="${data.quantity ?? ''}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label mb-1 block">Satuan</label>
                            <input type="text" name="components[${index}][uom]"
                                   class="w-full form-input !rounded-none bg-gray-50 text-sm shadow-sm"
                                   placeholder="kg, pcs, liter..." value="${data.uom ?? ''}">
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 !rounded-none transition-colors duration-150 remove-component flex items-center justify-center" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `;
            }

            function bindRow(row) {
                const input = row.querySelector('[data-raw-input]');
                const hidden = row.querySelector('[data-raw-id]');
                if (!input || !hidden) return;

                if (hidden.value && !input.value) {
                    input.value = rawLabelById.get(String(hidden.value)) || '';
                }

                initAutocomplete(input, hidden, rawItems, rawIdByLabel, 'bahan');
            }

            addBtn?.addEventListener('click', function () {
                const newRow = createComponentRow(componentIndex);
                container.insertAdjacentHTML('beforeend', newRow);
                const rows = container.querySelectorAll('.component-row');
                const lastRow = rows[rows.length - 1];
                if (lastRow) bindRow(lastRow);
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

            container?.querySelectorAll('.component-row').forEach(bindRow);

            form?.addEventListener('submit', function (e) {
                let firstInvalid = null;
                if (finishedInput && finishedHidden) {
                    syncSelection(finishedInput, finishedHidden, finishedIdByLabel, 'produk');
                }
                if (finishedInput && !finishedInput.checkValidity()) {
                    firstInvalid = finishedInput;
                }
                container?.querySelectorAll('[data-raw-input]').forEach(input => {
                    const hidden = input.closest('.component-row')?.querySelector('[data-raw-id]');
                    if (hidden) {
                        syncSelection(input, hidden, rawIdByLabel, 'bahan');
                    }
                    if (!input.checkValidity() && !firstInvalid) {
                        firstInvalid = input;
                    }
                });
                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.reportValidity();
                    firstInvalid.focus();
                }
            });

            if (finishedInput && finishedHidden) {
                if (finishedHidden.value && !finishedInput.value) {
                    finishedInput.value = finishedLabelById.get(String(finishedHidden.value)) || '';
                }
                initAutocomplete(finishedInput, finishedHidden, finishedItems, finishedIdByLabel, 'produk');
            }
        });
    </script>
@endpush
