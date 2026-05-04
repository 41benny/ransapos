@extends('layouts.admin')

@section('title', 'Preview Import Backdate')
@section('page-title', 'Preview Import Backdate')
@section('page-subtitle', 'Periksa hasil validasi sebelum transaksi disimpan')

@section('content')
<div class="page-fullwidth space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <a href="{{ route('admin.backdate-sales.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Kembali
        </a>

        @if(empty($preview['errors']))
            <form method="POST" action="{{ route('admin.backdate-sales.import.process') }}">
                @csrf
                <button type="submit" class="ui-btn ui-btn-primary rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Proses Import
                </button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs uppercase tracking-widest text-slate-500">Transaksi Valid</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($preview['transaction_count']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs uppercase tracking-widest text-slate-500">Item Dibaca</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($preview['item_count']) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-xs uppercase tracking-widest text-slate-500">Subtotal Item</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($preview['total'], 0, ',', '.') }}</p>
        </div>
    </div>

    @if(!empty($preview['errors']))
        <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800">
            <p class="font-semibold mb-3">Import belum bisa diproses. Perbaiki error berikut:</p>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($preview['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="ui-card bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-base font-semibold text-slate-900">Ringkasan File</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="ui-table w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Kode Manual</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Outlet</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Metode Bayar</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500">Item</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500">Subtotal</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($preview['groups'] as $group)
                        <tr>
                            <td class="px-5 py-3 font-mono text-sm text-slate-900">{{ $group['manual_reference'] }}</td>
                            <td class="px-5 py-3 text-sm text-slate-700">{{ $group['sale_date'] ?: '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-700">{{ $group['outlet'] ?: '-' }}</td>
                            <td class="px-5 py-3 text-sm text-slate-700">{{ $group['payment_method'] ?: '-' }}</td>
                            <td class="px-5 py-3 text-right text-sm text-slate-700">{{ number_format($group['item_count']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold text-slate-900">Rp {{ number_format($group['total'], 0, ',', '.') }}</td>
                            <td class="px-5 py-3 text-sm">
                                @if(empty($group['errors']))
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Valid</span>
                                @else
                                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">{{ count($group['errors']) }} error</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
