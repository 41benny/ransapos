@extends('layouts.admin')

@section('title', 'Stock Adjustment')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Penyesuaian stok manual (opname)</p>
                <h1 class="text-2xl font-semibold text-slate-900">Stock Adjustment</h1>
            </div>
            <a href="{{ route('admin.stocks.index') }}"
                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl p-4">
                {{ session('error') }}
            </div>
        @endif

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

        <form method="POST" action="{{ route('admin.stocks.adjustment.store') }}" id="bulkAdjustmentForm"
            class="space-y-6">
            @csrf

            <div class="bg-white rounded-2xl shadow-premium border border-slate-200 p-6 space-y-6">
                <div class="grid gap-5 md:grid-cols-3">
                    <div class="md:col-span-1">
                        <label for="outlet_id" class="block text-sm font-medium text-slate-700 mb-1">
                            Outlet <span class="text-rose-500">*</span>
                        </label>
                        <select name="outlet_id" id="outlet_id" required
                            class="w-full h-11 rounded-lg border-2 border-slate-300 bg-white px-3 text-base focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors">
                            <option value="">Pilih Outlet</option>
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1">
                            Catatan/Alasan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="notes" id="notes" required maxlength="500"
                            value="{{ old('notes') }}"
                            class="w-full h-11 rounded-lg border-2 border-slate-300 bg-white px-3 text-base focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors"
                            placeholder="Contoh: Stok awal per 2026-02-05 / hasil stock opname...">
                        <p class="text-xs text-slate-500 mt-1">Satu catatan ini akan dipakai untuk semua baris penyesuaian.</p>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-amber-600 font-semibold">Detail</p>
                        <h2 class="text-lg font-semibold text-slate-900">Daftar Penyesuaian</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="add-row"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium shadow-sm transition-all flex items-center gap-2">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                        <button type="button" id="clear-rows"
                            class="px-3 py-1.5 bg-white border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg text-sm font-medium shadow-sm transition-colors flex items-center gap-2">
                            <i class="fas fa-times"></i> Hapus Semua
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="min-w-full bg-white text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold w-12">#</th>
                                <th class="px-4 py-3 text-left font-semibold min-w-[320px]">Produk</th>
                                <th class="px-4 py-3 text-right font-semibold w-40">Qty di Sistem</th>
                                <th class="px-4 py-3 text-right font-semibold w-40">Qty Aktual</th>
                                <th class="px-4 py-3 text-right font-semibold w-40">Perubahan</th>
                                <th class="px-4 py-3 text-right font-semibold w-16"></th>
                            </tr>
                        </thead>
                        <tbody id="rows" class="divide-y divide-slate-200">
                            {{-- Rows injected by JS --}}
                        </tbody>
                    </table>
                </div>

                <p class="text-xs text-slate-500">
                    Tips: pilih outlet dulu, lalu ketik nama/SKU produk. Qty di sistem akan ter-load otomatis per baris.
                </p>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="submit"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-md transition-all flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Penyesuaian
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const outletSelect = document.getElementById('outlet_id');
            const rowsEl = document.getElementById('rows');
            const addBtn = document.getElementById('add-row');
            const clearBtn = document.getElementById('clear-rows');
            const form = document.getElementById('bulkAdjustmentForm');

            const products = @json($productsPayload);

            const idByLabel = new Map(products.map(p => [p.label, p.id]));

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function fmt(n) {
                const x = Number(n);
                if (!Number.isFinite(x)) return '0.00';
                return x.toFixed(2);
            }

            function syncSelection(input, hidden) {
                const mapped = idByLabel.get(input.value) || '';
                hidden.value = mapped;
                if (input.value && !mapped) {
                    input.setCustomValidity('Pilih produk dari daftar.');
                } else {
                    input.setCustomValidity('');
                }
            }

            function initAutocomplete(input, hidden, panel) {
                if (input.dataset.autocompleteReady) return;
                input.dataset.autocompleteReady = 'true';

                function renderList() {
                    const query = input.value.trim().toLowerCase();
                    const results = query
                        ? products.filter(p => p.search.includes(query)).slice(0, 40)
                        : products.slice(0, 40);
                    panel.innerHTML = results.length
                        ? results.map(p =>
                            `<button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-100" data-id="${p.id}">${escapeHtml(p.label)}</button>`
                        ).join('')
                        : '<div class="px-3 py-2 text-slate-500">Tidak ada hasil.</div>';
                    panel.classList.remove('hidden');
                }

                function hideList() {
                    panel.classList.add('hidden');
                }

                input.addEventListener('focus', renderList);
                input.addEventListener('input', function() {
                    renderList();
                    syncSelection(input, hidden);
                });
                input.addEventListener('blur', function() {
                    setTimeout(hideList, 150);
                    syncSelection(input, hidden);
                });

                panel.addEventListener('mousedown', function(event) {
                    const target = event.target.closest('[data-id]');
                    if (!target) return;
                    const picked = products.find(p => p.id === target.dataset.id);
                    if (!picked) return;
                    input.value = picked.label;
                    hidden.value = picked.id;
                    input.setCustomValidity('');
                    hideList();
                    input.dispatchEvent(new Event('change', {bubbles: true}));
                });
            }

            async function fetchCurrentStock(productId, outletId) {
                const url = `{{ route('admin.stocks.current') }}?product_id=${encodeURIComponent(productId)}&outlet_id=${encodeURIComponent(outletId)}`;
                const res = await fetch(url, {headers: {'Accept': 'application/json'}});
                if (!res.ok) throw new Error('Gagal load stok');
                return await res.json();
            }

            function updateDiff(row) {
                const sys = Number(row.dataset.systemQty || '0');
                const actualInput = row.querySelector('[data-actual]');
                const diffEl = row.querySelector('[data-diff]');
                const actual = Number(actualInput?.value || '0');
                const diff = actual - sys;
                diffEl.textContent = fmt(diff);
                if (diff > 0) diffEl.className = 'px-4 py-3 text-right font-semibold text-emerald-700';
                else if (diff < 0) diffEl.className = 'px-4 py-3 text-right font-semibold text-rose-700';
                else diffEl.className = 'px-4 py-3 text-right font-semibold text-slate-600';
            }

            function renumber() {
                rowsEl.querySelectorAll('tr').forEach((tr, idx) => {
                    const no = tr.querySelector('[data-no]');
                    if (no) no.textContent = String(idx + 1);
                    const hidden = tr.querySelector('[data-product-id]');
                    const actual = tr.querySelector('[data-actual]');
                    if (hidden) hidden.name = `items[${idx}][product_id]`;
                    if (actual) actual.name = `items[${idx}][new_quantity]`;
                });
            }

            function createRow() {
                const tr = document.createElement('tr');
                tr.dataset.systemQty = '0';
                tr.innerHTML = `
                    <td class="px-4 py-3 text-slate-600" data-no>1</td>
                    <td class="px-4 py-3">
                        <div class="relative" data-autocomplete-wrap>
                            <input type="text"
                                class="w-full h-11 rounded-lg border-2 border-slate-300 bg-white px-3 text-base focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                placeholder="Ketik nama/SKU..." data-product-input required>
                            <input type="hidden" data-product-id value="">
                            <div class="absolute z-30 mt-1 w-full max-h-64 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg text-sm hidden"
                                data-panel></div>
                        </div>
                        <p class="text-xs text-slate-500 mt-1" data-unit></p>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-700 tabular-nums" data-system>0.00</td>
                    <td class="px-4 py-3 text-right">
                        <input type="number" step="0.01" min="0"
                            class="w-32 h-11 text-right rounded-lg border-2 border-slate-300 bg-white px-3 text-base focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                            value="0" data-actual required>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold text-slate-600 tabular-nums" data-diff>0.00</td>
                    <td class="px-4 py-3 text-right">
                        <button type="button" class="h-10 w-10 inline-flex items-center justify-center bg-red-500 hover:bg-red-600 text-white rounded-lg shadow-sm" data-remove
                            title="Hapus baris">
                            <i class="fas fa-trash text-sm"></i>
                        </button>
                    </td>
                `;

                const input = tr.querySelector('[data-product-input]');
                const hidden = tr.querySelector('[data-product-id]');
                const panel = tr.querySelector('[data-panel]');
                const sysEl = tr.querySelector('[data-system]');
                const unitEl = tr.querySelector('[data-unit]');
                const actual = tr.querySelector('[data-actual]');

                initAutocomplete(input, hidden, panel);
                syncSelection(input, hidden);

                input.addEventListener('change', async function() {
                    syncSelection(input, hidden);
                    const outletId = outletSelect.value;
                    if (!hidden.value || !outletId) return;
                    try {
                        const data = await fetchCurrentStock(hidden.value, outletId);
                        if (data?.success) {
                            const sysQty = Number(data.current_stock || 0);
                            tr.dataset.systemQty = String(sysQty);
                            sysEl.textContent = fmt(sysQty);
                            unitEl.textContent = data.unit ? `Satuan: ${data.unit}` : '';
                            updateDiff(tr);
                        }
                    } catch (e) {
                        // keep silent, user can retry by reselecting
                    }
                });

                actual.addEventListener('input', () => updateDiff(tr));

                tr.querySelector('[data-remove]')?.addEventListener('click', function() {
                    tr.remove();
                    renumber();
                });

                return tr;
            }

            addBtn.addEventListener('click', function() {
                rowsEl.appendChild(createRow());
                renumber();
            });

            clearBtn.addEventListener('click', function() {
                rowsEl.innerHTML = '';
                rowsEl.appendChild(createRow());
                renumber();
            });

            outletSelect.addEventListener('change', async function() {
                // Reload system qty for all rows when outlet changes
                const outletId = outletSelect.value;
                rowsEl.querySelectorAll('tr').forEach(async tr => {
                    const productId = tr.querySelector('[data-product-id]')?.value;
                    const sysEl = tr.querySelector('[data-system]');
                    if (!productId || !outletId) {
                        tr.dataset.systemQty = '0';
                        if (sysEl) sysEl.textContent = '0.00';
                        updateDiff(tr);
                        return;
                    }
                    try {
                        const data = await fetchCurrentStock(productId, outletId);
                        if (data?.success) {
                            const sysQty = Number(data.current_stock || 0);
                            tr.dataset.systemQty = String(sysQty);
                            if (sysEl) sysEl.textContent = fmt(sysQty);
                            updateDiff(tr);
                        }
                    } catch (e) {}
                });
            });

            form.addEventListener('submit', function(e) {
                let firstInvalid = null;
                rowsEl.querySelectorAll('tr').forEach(tr => {
                    const input = tr.querySelector('[data-product-input]');
                    const hidden = tr.querySelector('[data-product-id]');
                    if (input && hidden) syncSelection(input, hidden);
                    if (input && !input.checkValidity() && !firstInvalid) firstInvalid = input;
                    const actual = tr.querySelector('[data-actual]');
                    if (actual && !actual.checkValidity() && !firstInvalid) firstInvalid = actual;
                });
                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.reportValidity();
                    firstInvalid.focus();
                }
            });

            // init with 1 row
            rowsEl.appendChild(createRow());
            renumber();
        });
    </script>
@endpush
