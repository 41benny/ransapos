@extends('layouts.admin')

@section('title', $report['title'])
@section('page-title', $report['title'])
@section('page-subtitle', 'Detail laporan dari katalog')

@section('content')
<div class="mx-auto w-full max-w-7xl space-y-5">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-sm text-slate-500">Kode laporan: {{ $slug }}</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $report['title'] }}</div>
            </div>
            <a href="{{ route('admin.reports.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Kembali ke Katalog
            </a>
        </div>

        <form method="GET" class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Tanggal Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Outlet</label>
                <select name="outlet_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Semua Outlet</option>
                    @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" @selected((string) $outletId === (string) $outlet->id)>
                            {{ $outlet->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex h-10 items-center rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white hover:bg-indigo-800">
                    Tampilkan
                </button>
                @if(!empty($report['existing_route']) && Route::has($report['existing_route']))
                    <a href="{{ route($report['existing_route']) }}"
                        class="inline-flex h-10 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        Versi Lama
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($report['implemented'] ?? false)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Transaksi</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary['total_transactions'] ?? 0) }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Nilai</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">Rp {{ number_format($summary['total_amount'] ?? 0, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Metode Pembayaran</th>
                            <th class="px-4 py-3 text-right">Total Transaksi</th>
                            <th class="px-4 py-3 text-right">Total Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $row->payment_method_name }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ number_format($row->total_transactions) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">Rp {{ number_format($row->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada data untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="text-sm font-semibold text-amber-900">Halaman laporan sudah dibuat.</div>
            <div class="mt-1 text-sm text-amber-800">
                Query data untuk laporan ini belum diimplementasikan. Halaman ini siap dipakai untuk tahap integrasi data berikutnya.
            </div>
        </div>
    @endif
</div>
@endsection
