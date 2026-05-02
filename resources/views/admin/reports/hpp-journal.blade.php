@extends('layouts.admin')

@section('title', 'Export Jurnal Bulanan')
@section('page-title', 'Export Jurnal Bulanan')
@section('page-subtitle', 'Jurnal penjualan, HPP, mutasi persediaan, dan pembelian per outlet')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-800">Filter Export</h2>
            <p class="text-sm text-slate-500 mt-1">Pilih bulan dan outlet, lalu klik export untuk mengunduh jurnal penjualan, HPP, mutasi persediaan, dan pembelian dalam satu file.</p>
        </div>

        @if($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('admin.reports.hpp-journal.export') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="month" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Bulan</label>
                <input
                    type="month"
                    id="month"
                    name="month"
                    value="{{ $month }}"
                    class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Outlet (Mapping Jurnal Aktif)</label>
                <div class="rounded-xl border border-slate-300 p-3">
                    @if($outlets->isEmpty())
                        <div class="text-sm text-slate-500">Belum ada outlet dengan mapping jurnal bulanan.</div>
                    @else
                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700 pb-2 mb-2 border-b border-slate-100">
                            <input
                                type="checkbox"
                                id="all-mapped-outlets"
                                class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                {{ empty($selectedOutletIds ?? []) ? 'checked' : '' }}
                            >
                            Pilih Semua Mapping
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1">
                            @foreach($outlets as $outlet)
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        name="outlet_ids[]"
                                        value="{{ $outlet->id }}"
                                        class="mapped-outlet-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        {{ empty($selectedOutletIds ?? []) || in_array((int) $outlet->id, $selectedOutletIds ?? [], true) ? 'checked' : '' }}
                                    >
                                    {{ $outlet->name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if(($unmappedOutlets ?? collect())->isNotEmpty())
                <div class="md:col-span-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-amber-700">Belum Ada Mapping Akun Jurnal</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        @foreach($unmappedOutlets as $outlet)
                            <label class="flex items-center gap-2 text-sm text-amber-800">
                                <input type="checkbox" disabled class="rounded border-amber-300 text-amber-500">
                                <span>{{ $outlet->name }}</span>
                                <span class="text-xs text-amber-600">butuh akun penjualan, pembayaran, diskon/meal, HPP, persediaan & pembelian</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex items-end md:col-span-3">
                <button
                    type="submit"
                    class="inline-flex w-full md:w-auto items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition"
                >
                    <i class="fas fa-file-excel mr-2"></i>
                    Export XLSX
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const allCheckbox = document.getElementById('all-mapped-outlets');
        const itemCheckboxes = Array.from(document.querySelectorAll('.mapped-outlet-checkbox'));
        if (!allCheckbox || itemCheckboxes.length === 0) return;

        const syncAllCheckbox = () => {
            const checked = itemCheckboxes.filter((item) => item.checked).length;
            allCheckbox.checked = checked === itemCheckboxes.length;
        };

        allCheckbox.addEventListener('change', () => {
            itemCheckboxes.forEach((item) => {
                item.checked = allCheckbox.checked;
            });
        });

        itemCheckboxes.forEach((item) => {
            item.addEventListener('change', syncAllCheckbox);
        });

        syncAllCheckbox();
    })();
</script>
@endpush
