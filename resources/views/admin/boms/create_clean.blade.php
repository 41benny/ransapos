@extends('layouts.admin')

@section('title', 'Tambah BOM')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Atur komposisi produk jadi</p>
                <h1 class="text-2xl font-semibold text-slate-900">Bill of Materials</h1>
            </div>
            <a href="{{ route('admin.boms.index') }}" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
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

            <div class="bg-white rounded-2xl shadow-premium border border-slate-200 p-6 space-y-6">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label for="product_id" class="block text-sm font-medium text-slate-700 mb-1">
                                Produk Utama <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" list="finished-products-list" id="product_id"
                                class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors"
                                placeholder="Ketik produk..." value="{{ $selectedFinishedLabel }}" data-finished-input required>
                            <input type="hidden" name="product_id" value="{{ old('product_id') }}" data-finished-id>
                        </div>

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama BOM</label>
                            <input type="text" name="name" id="name"
                                class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors"
                                placeholder="Contoh: Resep Nasi Goreng Spesial" value="{{ old('name') }}">
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">Catatan</label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors"
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
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-plus"></i> Tambah Komponen
                            </button>
                        </div>

                        <div id="components-container" class="space-y-3">
                            @if(old('components'))
                                @foreach(old('components') as $index => $component)
                                    <div class="component-row bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                                        <div class="grid gap-3 md:grid-cols-12">
                                            <div class="md:col-span-6">
                                                <label class="text-sm font-medium text-slate-700 mb-1 block">Bahan/Komponen</label>
                                                @php
                                                    $selectedRaw = $rawMap->get($component['component_product_id'] ?? null);
                                                    $selectedLabel = $selectedRaw ? ($selectedRaw->name . ' (' . $selectedRaw->sku . ')') : '';
                                                @endphp
                                                <input type="text" list="raw-materials-list"
                                                    class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                    placeholder="Ketik bahan..." value="{{ $selectedLabel }}" data-raw-input required>
                                                <input type="hidden" name="components[{{ $index }}][component_product_id]"
                                                    value="{{ $component['component_product_id'] ?? '' }}" data-raw-id>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="text-sm font-medium text-slate-700 mb-1 block">Jumlah</label>
                                                <input type="number" name="components[{{ $index }}][quantity]"
                                                    class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                    step="0.0001" min="0.0001" value="{{ $component['quantity'] }}" required>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="text-sm font-medium text-slate-700 mb-1 block">Satuan</label>
                                                <input type="text" name="components[{{ $index }}][uom]"
                                                    class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                    placeholder="kg, pcs, liter..." value="{{ $component['uom'] ?? '' }}">
                                            </div>
                                            <div class="md:col-span-1 flex items-end">
                                                <button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component flex items-center justify-center gap-2">
                                                    <i class="fas fa-trash"></i> Hapus
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
                                            <input type="text" list="raw-materials-list"
                                                class="w-full rounded-lg border-slate-300 focus:ring-amber-500 focus:border-amber-500"
                                                placeholder="Ketik bahan..." data-raw-input required>
                                            <input type="hidden" name="components[0][component_product_id]" value="" data-raw-id>
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
                                            <button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component flex items-center justify-center gap-2">
                                                <i class="fas fa-trash"></i> Hapus
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
                        class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg font-medium transition-colors">Batal</a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md transition-all flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan BOM
                    </button>
                </div>
            </div>
        </form>
    </div>

    <datalist id="finished-products-list">
        @foreach($finishedProducts as $product)
            <option value="{{ $product->name }} ({{ $product->sku }})" data-id="{{ $product->id }}"></option>
        @endforeach
    </datalist>

    <datalist id="raw-materials-list">
        @foreach($rawMaterials as $raw)
            <option value="{{ $raw->name }} ({{ $raw->sku }})" data-id="{{ $raw->id }}"></option>
        @endforeach
    </datalist>

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
            const rawIdByLabel = new Map(rawMaterials.map(raw => [`${raw.name} (${raw.sku})`, String(raw.id)]));
            const rawLabelById = new Map(rawMaterials.map(raw => [String(raw.id), `${raw.name} (${raw.sku})`]));
            const finishedProducts = @json($finishedProducts->map(fn($product) => ['id' => $product->id, 'name' => $product->name, 'sku' => $product->sku]));
            const finishedIdByLabel = new Map(finishedProducts.map(product => [`${product.name} (${product.sku})`, String(product.id)]));
            const finishedLabelById = new Map(finishedProducts.map(product => [String(product.id), `${product.name} (${product.sku})`]));
            let componentIndex = parseInt(document.getElementById('component-index-data').textContent || '1', 10);
            const container = document.getElementById('components-container');
            const addBtn = document.getElementById('add-component');
            const form = container?.closest('form');
            const finishedInput = document.querySelector('[data-finished-input]');
            const finishedHidden = document.querySelector('[data-finished-id]');

            function syncRawSelection(input) {
                const row = input.closest('.component-row');
                if (!row) return;
                const hidden = row.querySelector('[data-raw-id]');
                const mapped = rawIdByLabel.get(input.value) || '';
                if (hidden) hidden.value = mapped;
                if (input.value && !mapped) {
                    input.setCustomValidity('Pilih bahan dari daftar.');
                } else {
                    input.setCustomValidity('');
                }
            }

            function syncFinishedSelection() {
                if (!finishedInput || !finishedHidden) return;
                const mapped = finishedIdByLabel.get(finishedInput.value) || '';
                finishedHidden.value = mapped;
                if (finishedInput.value && !mapped) {
                    finishedInput.setCustomValidity('Pilih produk dari daftar.');
                } else {
                    finishedInput.setCustomValidity('');
                }
            }

            function createComponentRow(index, data = {}) {
                return `
                <div class="component-row bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="text-sm font-medium text-slate-700 mb-1 block">Bahan/Komponen</label>
                            <input type="text" list="raw-materials-list"
                                   class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm"
                                   placeholder="Ketik bahan..." data-raw-input required>
                            <input type="hidden" name="components[${index}][component_product_id]" value="${data.component_product_id ?? ''}" data-raw-id>
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-sm font-medium text-slate-700 mb-1 block">Jumlah</label>
                            <input type="number" name="components[${index}][quantity]"
                                   class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm"
                                   step="0.0001" min="0.0001" value="${data.quantity ?? ''}" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-medium text-slate-700 mb-1 block">Satuan</label>
                            <input type="text" name="components[${index}][uom]"
                                   class="w-full rounded-lg border-gray-400 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm"
                                   placeholder="kg, pcs, liter..." value="${data.uom ?? ''}">
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="button" class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-150 remove-component flex items-center justify-center gap-2"><i class="fas fa-trash"></i> Hapus</button>
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

                const handler = () => syncRawSelection(input);
                input.addEventListener('input', handler);
                input.addEventListener('change', handler);
                handler();
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
                syncFinishedSelection();
                if (finishedInput && !finishedInput.checkValidity()) {
                    firstInvalid = finishedInput;
                }
                container?.querySelectorAll('[data-raw-input]').forEach(input => {
                    syncRawSelection(input);
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
                finishedInput.addEventListener('input', syncFinishedSelection);
                finishedInput.addEventListener('change', syncFinishedSelection);
                syncFinishedSelection();
            }
        });
    </script>
@endpush
