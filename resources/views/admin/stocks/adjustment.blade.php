@extends('layouts.admin')

@section('title', 'Stock Adjustment')
@section('page-title', 'Stock Adjustment')
@section('page-subtitle', 'Penyesuaian stok manual (opname) untuk sinkronisasi inventaris')

@section('content')
    <div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Stock Adjustment</h1>
                <p class="mt-1 text-sm text-slate-500">Sinkronisasi stok fisik dengan data sistem melalui
                    opname manual</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.stocks.index') }}"
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali ke Stok</span>
                </a>
            </div>
        </div>

        @if(session('error'))
            <div
                class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-start gap-3 text-rose-600 animate-in slide-in-from-top-2">
                <i class="fas fa-circle-exclamation mt-0.5"></i>
                <p class="text-sm">{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div
                class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex flex-col gap-2 text-rose-600 animate-in slide-in-from-top-2 text-sm">
                <div class="flex items-center gap-2 font-medium">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>Mohon periksa kembali formulir Anda:</span>
                </div>
                <ul class="list-disc list-inside pl-2 space-y-1 opacity-90">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.stocks.adjustment.store') }}" id="bulkAdjustmentForm" class="space-y-6">
            @csrf

            {{-- General Info Card --}}
            <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-500 text-[10px]"></i>
                        <h3 class="text-sm font-semibold text-slate-700 leading-none">Informasi
                            Penyesuaian</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1.5 md:col-span-1">
                            <label class="ui-label ml-1">Pilih Outlet
                                <span class="text-rose-500">*</span></label>
                            <select name="outlet_id" id="outlet_id" required
                                class="ui-input w-full px-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                <option value="">Pilih Outlet Tujuan</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5 md:col-span-2">
                            <label class="ui-label ml-1">Catatan /
                                Alasan <span class="text-rose-500">*</span></label>
                            <input type="text" name="notes" id="notes" required maxlength="500" value="{{ old('notes') }}"
                                placeholder="Contoh: Hasil Stock Opname Bulanan - Februari 2026"
                                class="ui-input w-full px-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                            <p class="mt-1 text-xs text-slate-400 italic">* Catatan ini akan diterapkan untuk semua item
                                yang disesuaikan dalam form ini.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Card --}}
            <div class="ui-card relative bg-white rounded-2xl shadow-sm border border-slate-200 overflow-visible mb-8">
                <div class="rounded-t-2xl px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-list text-indigo-500 text-[10px]"></i>
                        <h3 class="text-sm font-semibold text-slate-700 leading-none">Daftar
                            Item Penyesuaian</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="add-row"
                            class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                            <i class="fas fa-plus"></i>
                            <span>Tambah Baris</span>
                        </button>
                        <button type="button" id="clear-rows"
                            class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white border border-rose-100 px-4 py-2 text-sm font-medium text-rose-500 shadow-sm transition-all hover:bg-rose-50 active:scale-95">
                            <i class="fas fa-trash-alt"></i>
                            <span>Reset Semua</span>
                        </button>
                    </div>
                </div>

                <div class="relative overflow-x-auto md:overflow-visible">
                    <table class="ui-table ui-table-standard min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-500 w-12 text-center">
                                    No</th>
                                <th
                                    class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-500 min-w-[300px]">
                                    Produk</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-widest text-slate-500 w-32">
                                    Qty Sistem</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-widest text-slate-500 w-32">
                                    Qty Aktual</th>
                                <th
                                    class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-widest text-slate-500 w-32">
                                    Selisih</th>
                                <th
                                    class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-widest text-slate-500 w-16">
                                </th>
                            </tr>
                        </thead>
                        <tbody id="rows" class="divide-y divide-slate-100 bg-white">
                            {{-- Rows injected by JS --}}
                        </tbody>
                    </table>
                </div>

                <div class="relative rounded-b-2xl p-6 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-indigo-500">
                            <i class="fas fa-lightbulb text-[10px]"></i>
                        </div>
                        <p class="text-xs text-slate-400 italic">Pilih outlet
                            terlebih dahulu, lalu masukkan nama atau SKU produk.</p>
                    </div>
                    <button type="submit"
                        class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-slate-900 px-8 py-3 text-sm font-medium text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95">
                        <i class="fas fa-save text-[10px]"></i>
                        <span>Simpan Data Penyesuaian</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
                return x.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function syncSelection(input, hidden) {
                const mapped = idByLabel.get(input.value) || '';
                hidden.value = mapped;
                if (input.value && !mapped) {
                    input.setCustomValidity('Pilih produk dari daftar yang tersedia.');
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
                            `<button type="button" class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0" data-id="${p.id}">${escapeHtml(p.label)}</button>`
                        ).join('')
                        : '<div class="px-4 py-3 text-xs text-slate-400 italic">Produk tidak ditemukan.</div>';
                    panel.classList.remove('hidden');
                }

                function hideList() {
                    panel.classList.add('hidden');
                }

                input.addEventListener('focus', renderList);
                input.addEventListener('input', function () {
                    renderList();
                    syncSelection(input, hidden);
                });
                input.addEventListener('blur', function () {
                    setTimeout(hideList, 200);
                    syncSelection(input, hidden);
                });

                panel.addEventListener('mousedown', function (event) {
                    const target = event.target.closest('[data-id]');
                    if (!target) return;
                    const picked = products.find(p => p.id === target.dataset.id);
                    if (!picked) return;
                    input.value = picked.label;
                    hidden.value = picked.id;
                    input.setCustomValidity('');
                    hideList();
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            }

            async function fetchCurrentStock(productId, outletId) {
                const url = `{{ route('admin.stocks.current') }}?product_id=${encodeURIComponent(productId)}&outlet_id=${encodeURIComponent(outletId)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Gagal memuat data stok.');
                return await res.json();
            }

            function updateDiff(row) {
                const sys = Number(row.dataset.systemQty || '0');
                const actualInput = row.querySelector('[data-actual]');
                const diffEl = row.querySelector('[data-diff]');
                const actual = Number(actualInput?.value || '0');
                const diff = actual - sys;
                diffEl.textContent = (diff >= 0 ? '+' : '') + fmt(diff);

                if (diff > 0) diffEl.className = 'px-5 py-3.5 text-right text-sm text-emerald-600 tracking-tight tabular-nums';
                else if (diff < 0) diffEl.className = 'px-5 py-3.5 text-right text-sm text-rose-600 tracking-tight tabular-nums';
                else diffEl.className = 'px-5 py-3.5 text-right text-sm text-slate-400 tracking-tight tabular-nums';
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
                tr.className = 'group transition-colors hover:bg-slate-50/50';
                tr.innerHTML = `
                        <td class="px-5 py-3.5 text-center text-sm text-slate-500 tabular-nums" data-no>1</td>
                        <td class="relative px-5 py-3.5">
                            <div class="relative" data-autocomplete-wrap>
                                <input type="text"
                                    class="ui-input w-full h-10 px-4 text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm"
                                    placeholder="Cari nama atau SKU produk..." data-product-input required autocomplete="off">
                                <input type="hidden" data-product-id value="">
                                <div class="absolute left-0 top-full z-50 mt-1 w-full max-h-60 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl text-sm hidden py-1"
                                    data-panel></div>
                            </div>
                            <div class="flex items-center gap-1.5 mt-1 ml-1">
                                <i class="fas fa-tag text-[8px] text-slate-300"></i>
                                <span class="text-xs text-slate-400 uppercase tracking-widest" data-unit>-</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-right text-sm text-slate-600 tabular-nums" data-system>0,00</td>
                        <td class="px-5 py-3.5 text-right">
                            <input type="number" step="0.01" min="0"
                                class="ui-input w-32 h-10 px-4 text-right text-sm bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm tabular-nums"
                                value="0" data-actual required>
                        </td>
                        <td class="px-5 py-3.5 text-right text-sm text-slate-500 tracking-tight tabular-nums" data-diff>0,00</td>
                        <td class="px-5 py-3.5 text-center">
                            <button type="button" class="ui-btn ui-btn-ghost h-8 w-8 inline-flex items-center justify-center bg-white border border-rose-100 text-rose-400 hover:bg-rose-500 hover:text-white hover:border-rose-500 rounded-xl transition-all shadow-sm active:scale-90" data-remove
                                title="Hapus item">
                                <i class="fas fa-times text-[10px]"></i>
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

                input.addEventListener('change', async function () {
                    syncSelection(input, hidden);
                    const outletId = outletSelect.value;
                    if (!hidden.value || !outletId) {
                        sysEl.textContent = '0,00';
                        unitEl.textContent = '-';
                        return;
                    }

                    input.disabled = true;
                    input.classList.add('opacity-50');

                    try {
                        const data = await fetchCurrentStock(hidden.value, outletId);
                        if (data?.success) {
                            const sysQty = Number(data.current_stock || 0);
                            tr.dataset.systemQty = String(sysQty);
                            sysEl.textContent = fmt(sysQty);
                            unitEl.textContent = data.unit ? `Satuan: ${data.unit}` : '-';
                            updateDiff(tr);
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        input.disabled = false;
                        input.classList.remove('opacity-50');
                    }
                });

                actual.addEventListener('input', () => updateDiff(tr));

                tr.querySelector('[data-remove]')?.addEventListener('click', function () {
                    if (rowsEl.querySelectorAll('tr').length > 1) {
                        tr.remove();
                        renumber();
                    } else {
                        // Reset the only remaining row instead of deleting
                        input.value = '';
                        hidden.value = '';
                        actual.value = '0';
                        sysEl.textContent = '0,00';
                        unitEl.textContent = '-';
                        tr.dataset.systemQty = '0';
                        updateDiff(tr);
                    }
                });

                return tr;
            }

            addBtn.addEventListener('click', function () {
                rowsEl.appendChild(createRow());
                renumber();
                // Focus newly added input
                rowsEl.lastElementChild.querySelector('[data-product-input]').focus();
            });

            clearBtn.addEventListener('click', function () {
                if (confirm('Apakah Anda yakin ingin menghapus semua baris data?')) {
                    rowsEl.innerHTML = '';
                    rowsEl.appendChild(createRow());
                    renumber();
                }
            });

            outletSelect.addEventListener('change', async function () {
                const outletId = outletSelect.value;
                const rows = rowsEl.querySelectorAll('tr');

                for (const tr of rows) {
                    const productId = tr.querySelector('[data-product-id]')?.value;
                    const sysEl = tr.querySelector('[data-system]');

                    if (!productId || !outletId) {
                        tr.dataset.systemQty = '0';
                        if (sysEl) sysEl.textContent = '0,00';
                        updateDiff(tr);
                        continue;
                    }

                    try {
                        const data = await fetchCurrentStock(productId, outletId);
                        if (data?.success) {
                            const sysQty = Number(data.current_stock || 0);
                            tr.dataset.systemQty = String(sysQty);
                            if (sysEl) sysEl.textContent = fmt(sysQty);
                            updateDiff(tr);
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }
            });

            form.addEventListener('submit', function (e) {
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
