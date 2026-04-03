@extends('layouts.admin')

@section('title', 'Buku Hutang: ' . $supplier->name)
@section('page-title', 'Buku Hutang Supplier')
@section('page-subtitle', 'Rincian mutasi hutang untuk: ' . $supplier->name)

@section('content')
<div class="w-full animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-900 tracking-tight">{{ $supplier->name }}</h1>
            <p class="text-xs font-normal text-slate-700 mt-0.5">Kode: {{ $supplier->code }} | Mutasi Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.reports.debts.index') }}" class="ui-btn ui-btn-ghost inline-flex items-center justify-center gap-2 rounded-lg bg-white border border-slate-200 px-4 py-2 text-xs font-normal text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                <i class="fas fa-arrow-left text-xs"></i>
                <span>Kembali</span>
            </a>
            <button onclick="window.print()" class="ui-btn ui-btn-primary inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                <i class="fas fa-print text-xs"></i>
                <span>Print Mutasi</span>
            </button>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 no-print">
        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-alt text-indigo-500 text-xs"></i>
                <h3 class="text-xs font-normal text-slate-400 uppercase tracking-widest leading-none">Filter Tanggal</h3>
            </div>
        </div>
        <div class="p-4">
            <form method="GET" class="flex flex-col sm:flex-row items-end gap-4">
                <div class="flex-1 w-full relative">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1 mb-1.5 block">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="ui-input w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                <div class="flex-1 w-full relative">
                    <label class="text-xs font-normal text-slate-600 uppercase tracking-wider ml-1 mb-1.5 block">Hingga Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="ui-input w-full px-4 py-2.5 text-sm font-normal bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-slate-900 border border-slate-900 text-white hover:bg-slate-800 transition-all active:scale-95 text-xs font-normal">
                        <i class="fas fa-search mr-2 text-xs"></i>Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-indigo-50/50 rounded-2xl border border-indigo-100 p-5 flex flex-col justify-between">
            <span class="text-xs font-medium text-indigo-600 uppercase tracking-wider mb-2">Total Akumulasi Tagihan (All Time)</span>
            <span class="text-xl font-bold text-slate-900 tabular-nums">Rp {{ number_format($allTimeDebt, 0, ',', '.') }}</span>
        </div>
        <div class="bg-emerald-50/50 rounded-2xl border border-emerald-100 p-5 flex flex-col justify-between">
            <span class="text-xs font-medium text-emerald-600 uppercase tracking-wider mb-2">Total Telah Dibayar (All Time)</span>
            <span class="text-xl font-bold text-slate-900 tabular-nums">Rp {{ number_format($allTimePaid, 0, ',', '.') }}</span>
        </div>
        <div class="bg-rose-50/50 rounded-2xl border border-rose-100 p-5 flex flex-col justify-between md:col-span-2">
            <span class="text-xs font-medium text-rose-600 uppercase tracking-wider mb-2">Hutang Berjalan Saat Ini (Sisa Akhir)</span>
            <div class="flex items-end justify-between">
                <span class="text-3xl font-bold text-rose-700 tabular-nums">Rp {{ number_format($endingBalanceAllTime, 0, ',', '.') }}</span>
                @if($endingBalanceAllTime <= 0)
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-full font-bold uppercase">Lunas</span>
                @else
                    <span class="px-2.5 py-1 bg-rose-100 text-rose-700 text-xs rounded-full font-bold uppercase">Ada Tunggakan</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Data Section --}}
    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
        <div class="p-4 border-b border-slate-100 bg-white flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Mutasi Buku Hutang / History (Berdasarkan Periode)</h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    @if($mutations->total() > 0)
                        Menampilkan {{ $mutations->firstItem() }}-{{ $mutations->lastItem() }} dari {{ $mutations->total() }} mutasi
                    @else
                        Tidak ada data
                    @endif
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="ui-table min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 backdrop-blur-sm sticky top-0 z-10">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Tanggal</th>
                        <th class="px-5 py-3 text-left text-xs font-normal uppercase tracking-widest text-slate-600">Keterangan / Referensi</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Terima Barang (Penambahan Hutang)</th>
                        <th class="px-5 py-3 text-right text-xs font-normal uppercase tracking-widest text-slate-600">Pembayaran (Pengurangan Hutang)</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-widest text-slate-800">Saldo Hutang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    {{-- Saldo Awal --}}
                    <tr class="bg-slate-50">
                        <td class="px-5 py-3 text-sm font-medium text-slate-700 italic" colspan="4">
                            Saldo Hutang Awal (Sebelum {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }})
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-slate-800 tabular-nums">
                            Rp {{ number_format($openingBalance, 0, ',', '.') }}
                        </td>
                    </tr>

                    {{-- Loop Mutasi --}}
                    @forelse($mutations as $mut)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 text-sm font-mono text-slate-500 whitespace-nowrap">
                                {{ $mut['date']->format('d M Y') }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-slate-900">{{ $mut['type'] }}</span>
                                    <span class="text-xs text-slate-500">{{ $mut['description'] }} - <code class="bg-slate-100 px-1 rounded">{{ $mut['reference'] }}</code></span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-rose-600 tabular-nums">
                                {{ $mut['credit'] > 0 ? 'Rp ' . number_format($mut['credit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm text-emerald-600 tabular-nums">
                                {{ $mut['debit'] > 0 ? 'Rp ' . number_format($mut['debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-5 py-3 text-right text-sm font-bold {{ $mut['balance'] > 0 ? 'text-rose-600' : 'text-slate-800' }} tabular-nums">
                                Rp {{ number_format($mut['balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                                Tidak ada mutasi transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse

                    {{-- Total Sum --}}
                    <tr class="bg-indigo-50/30 border-t-2 border-slate-200">
                        <td class="px-5 py-3 text-sm font-bold text-slate-800 text-right uppercase tracking-wider" colspan="2">
                            Total Mutasi Periode Ini
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-rose-700 tabular-nums border-x border-slate-200/50">
                            Rp {{ number_format($totalCredit, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-bold text-emerald-700 tabular-nums">
                            Rp {{ number_format($totalDebit, 0, ',', '.') }}
                        </td>
                        <td class="px-5 py-3 text-right text-sm font-bold {{ $currentBalance > 0 ? 'text-rose-700' : 'text-slate-800' }} tabular-nums border-l border-slate-200/50">
                            Rp {{ number_format($currentBalance, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        @if($mutations->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 bg-white no-print">
                {{ $mutations->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
    
    <div class="mt-4 text-xs text-slate-400 italic text-center no-print">
        * "Terima Barang" (Credit) menambah hutang, "Pembayaran" (Debit) mengurangi hutang.
    </div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        .no-print { display: none !important; }
        .animate-in { animation: none !important; opacity: 1 !important; transform: none !important; }
        .page-title { visibility: visible; }
        #app, main, .w-full, .bg-white { visibility: visible; }
        .shadow-sm { box-shadow: none !important; }
        .border-slate-200 { border-color: #e2e8f0 !important; }
        table { border-collapse: collapse; width: 100%; }
        th, td { visibility: visible; border: 1px solid #cbd5e1 !important; padding: 0.5rem !important; }
        thead th { background-color: #f8fafc !important; -webkit-print-color-adjust: exact; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    }
</style>
@endsection
