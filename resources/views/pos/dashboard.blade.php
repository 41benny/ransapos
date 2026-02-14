@extends('layouts.pos_theme')

@section('content')

    {{-- Kitchen Display Link (Preserved from original) --}}
    @if(auth()->user()->hasRole(['admin', 'manager', 'kitchen']))
        <div class="flex justify-end mb-4">
            <a href="{{ route('pos.kitchen.index') }}"
                class="flex items-center gap-2 bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition-all shadow-md">
                <span class="material-icons-round text-base">restaurant</span>
                <span>Kitchen Display</span>
            </a>
        </div>
    @endif

    @if($activeSession)
        {{-- Active Session Card --}}
        <div class="bg-surface-light rounded-2xl shadow-soft overflow-hidden relative group">
            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-500"></div>
            <div class="p-4 md:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-[10px] uppercase tracking-wider font-bold text-emerald-600">Sesi Aktif</h2>
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 font-mono tracking-tight mb-0.5">
                        {{ $activeSession->session_number }}
                    </h3>
                    <p class="text-text-muted-light flex items-center gap-1.5 text-[10px] md:text-xs">
                        <span class="material-icons-round text-xs">schedule</span>
                        Dibuka: {{ $activeSession->opened_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full md:w-auto">
                    <div class="text-left md:text-right">
                        <p class="text-[10px] text-text-muted-light font-medium mb-0.5">Saldo Awal</p>
                        <p class="text-xl md:text-2xl font-bold text-gray-900">
                            Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}
                        </p>
                    </div>
                    <a href="{{ route('pos.sessions.close') }}"
                        class="w-full md:w-auto bg-primary hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2 text-xs">
                        <span class="material-icons-round text-sm">lock_clock</span>
                        Tutup Shift
                    </a>
                </div>
            </div>
        </div>

        {{-- Quick Actions Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 my-4">
            <a href="{{ route('pos.sessions.print', $activeSession->id) }}" target="_blank"
                class="bg-white p-4 rounded-xl shadow-sm border border-rose-100 hover:border-rose-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">print</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-rose-700">Rekap Shift</span>
            </a>

            <a href="{{ route('pos.sales.history') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-blue-100 hover:border-blue-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">history</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-blue-700">Riwayat</span>
            </a>

            <a href="{{ route('pos.attendance.index') }}"
                class="bg-white p-4 rounded-xl shadow-sm border border-violet-100 hover:border-violet-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24">
                <div class="w-10 h-10 rounded-full bg-violet-50 text-violet-600 flex items-center justify-center group-hover:scale-110 transition">
                    <span class="material-icons-round">badge</span>
                </div>
                <span class="text-xs font-bold text-gray-700 group-hover:text-violet-700">Absensi</span>
            </a>

            <form action="{{ route('logout') }}" method="POST" class="contents">
                @csrf
                <button type="submit"
                    class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 hover:border-red-300 hover:shadow-md transition group flex flex-col items-center justify-center gap-2 text-center h-24 w-full">
                    <div class="w-10 h-10 rounded-full bg-gray-50 text-gray-500 group-hover:bg-red-50 group-hover:text-red-600 flex items-center justify-center group-hover:scale-110 transition">
                        <span class="material-icons-round">logout</span>
                    </div>
                    <span class="text-xs font-bold text-gray-700 group-hover:text-red-700">Logout</span>
                </button>
            </form>
        </div>

        {{-- Transactions Card --}}
        <div class="bg-surface-light rounded-2xl shadow-soft flex flex-col relative overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-round text-primary text-base">receipt_long</span>
                    Transaksi Hari Ini
                </h2>
                <div class="flex gap-2">
                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded-full text-[10px] font-semibold uppercase tracking-wide">
                        {{ $todaySales ? $todaySales->count() : 0 }} Transaksi
                    </span>
                </div>
            </div>

            @if($todaySales && $todaySales->count() > 0)
                {{-- Has Transactions State --}}
                <div class="p-4">
                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-[10px] mb-0.5">Total Penjualan</p>
                            <p class="text-lg font-bold text-gray-900">Rp
                                {{ number_format($todaySales->sum('total_amount'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-gray-500 text-[10px] mb-0.5">Rata-rata</p>
                            <p class="text-lg font-bold text-gray-900">Rp
                                {{ number_format($todaySales->avg('total_amount'), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 font-medium text-[10px] uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-2">Invoice</th>
                                    <th class="px-4 py-2">Pelanggan</th>
                                    <th class="px-4 py-2">Total</th>
                                    <th class="px-4 py-2">Waktu</th>
                                    <th class="px-4 py-2">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs">
                                @foreach($todaySales->take(5) as $sale)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-4 py-2 font-mono text-gray-900">{{ $sale->invoice_number }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                        <td class="px-4 py-2 font-semibold text-gray-900">Rp
                                            {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 text-gray-500">{{ $sale->created_at->format('H:i') }}</td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-0.5 text-[10px] font-semibold bg-green-100 text-green-700 rounded-full">
                                                {{ ucfirst($sale->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Floating Action Button for easy access when list is long --}}
                <div class="fixed bottom-6 right-6 z-50">
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white w-12 h-12 rounded-full shadow-xl shadow-violet-500/30 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 group">
                        <span
                            class="material-icons-round text-2xl group-hover:rotate-90 transition-transform duration-300">add</span>
                    </a>
                </div>

            @else
                {{-- Empty State (Compact) --}}
                <div class="flex-1 flex flex-col items-center justify-center p-6 md:py-8 text-center relative z-10">
                    <div class="w-20 h-20 rounded-full bg-gray-50 flex items-center justify-center mb-4 relative">
                        <div
                            class="absolute inset-0 border border-dashed border-gray-200 rounded-full animate-[spin_10s_linear_infinite]">
                        </div>
                        <span class="material-icons-round text-5xl text-gray-300">assignment_late</span>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 mb-1">Belum ada transaksi</h3>
                    <p class="text-text-muted-light max-w-xs mb-6 text-xs leading-relaxed">
                        Data penjualan akan muncul di sini setelah Anda memulai transaksi pertama.
                    </p>
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center gap-2 group">
                        <span class="material-icons-round text-lg group-hover:rotate-12 transition-transform">add_shopping_cart</span>
                        Mulai Transaksi
                    </a>
                </div>
                <div class="absolute inset-0 opacity-[0.03] pointer-events-none"
                    style="background-image: radial-gradient(#6B7280 1px, transparent 1px); background-size: 24px 24px;"></div>
            @endif
        </div>

    @else
        {{-- No Active Session State --}}
        <div class="flex items-center justify-center min-h-[80vh]">
            <div class="bg-surface-light p-8 md:p-12 rounded-2xl shadow-soft max-w-lg w-full text-center">
                <div class="w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="material-icons-round text-5xl text-primary">point_of_sale</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Sesi Kasir Belum Dibuka</h2>
                <p class="text-text-muted-light mb-8">
                    Anda perlu membuka shift kasir terlebih dahulu sebelum dapat melakukan transaksi penjualan.
                </p>
                <a href="{{ route('pos.sessions.open') }}"
                    class="w-full inline-flex justify-center items-center gap-2 bg-primary hover:bg-red-700 text-white px-8 py-3.5 rounded-xl font-bold text-lg shadow-lg shadow-red-500/20 hover:shadow-red-500/40 transition-all duration-300">
                    <span class="material-icons-round">login</span>
                    Buka Shift Kasir
                </a>
            </div>
        </div>
    @endif

@endsection