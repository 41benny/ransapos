@extends('layouts.admin')

@section('title', 'Laporan Hutang Supplier')
@section('page-title', 'Hutang Usaha')
@section('page-subtitle', 'Monitoring saldo hutang kepada supplier')

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">Laporan Hutang Supplier</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Daftar saldo hutang terutang berdasarkan PO Received</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs font-normal text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                <i class="fas fa-print text-xs"></i>
                <span>Print Laporan</span>
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-indigo-500 text-xs"></i>
                <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Hutang</h3>
            </div>
        </div>
        <div class="p-4">
            <form method="GET" class="flex flex-col sm:flex-row items-end gap-4">
                <div class="flex-1 w-full">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1 mb-1.5 block">Status Pembayaran</label>
                    <select name="status" class="w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <option value="unpaid" {{ $statusFilter == 'unpaid' ? 'selected' : '' }}>Hanya Yang Masih Ngutang (Belum Lunas)</option>
                        <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>Tampilkan Semua Supplier</option>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1 mb-1.5 block">Cari Nama Supplier</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau kode supplier..." class="w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-slate-900 border border-slate-900 text-white hover:bg-slate-800 transition-all active:scale-95 text-xs font-normal">
                        <i class="fas fa-search mr-2 text-xs"></i>Tampilkan
                    </button>
                    <a href="{{ route('admin.reports.debts.index') }}" class="inline-flex items-center justify-center px-3 py-2.5 rounded-lg bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-100 transition-all active:scale-95">
                        <i class="fas fa-redo text-xs"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Data Section --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="p-4 border-b border-slate-100 bg-white flex justify-between items-center">
            <h3 class="text-sm font-semibold text-slate-800">Ringkasan Hutang All Supplier</h3>
            <div class="text-right">
                <span class="text-xs text-slate-500 uppercase tracking-wider">Total Hutang Berjalan:</span>
                <span class="ml-2 text-lg font-bold text-rose-600">Rp {{ number_format($suppliers->sum('remaining_debt'), 0, ',', '.') }}</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Nama Supplier</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Total Akumulasi Tagihan</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Total Telah Dibayar</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-widest text-slate-800">Sisa Hutang Berjalan</th>
                        <th class="px-5 py-3 text-center text-xs font-normal text-slate-600 no-print">Aksi / Mutasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($suppliers as $supplier)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 text-sm font-mono text-slate-500">{{ $supplier->code }}</td>
                            <td class="px-5 py-3">
                                <span class="text-sm font-medium text-slate-900">{{ $supplier->name }}</span>
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-slate-600 tabular-nums">
                                Rp {{ number_format($supplier->total_debt, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-600 tabular-nums">
                                Rp {{ number_format($supplier->total_paid, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-bold {{ $supplier->remaining_debt > 0 ? 'text-rose-600' : 'text-slate-400' }} tabular-nums">
                                Rp {{ number_format($supplier->remaining_debt, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-3 text-center no-print">
                                <a href="{{ route('admin.reports.debts.show', $supplier) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded text-xs font-medium transition-colors">
                                    <i class="fas fa-book text-[10px]"></i> Buku Mutasi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 text-sm">
                                Tidak ada data hutang ditemukan untuk filter tersebut.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
