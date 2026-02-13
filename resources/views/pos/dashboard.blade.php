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
            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="text-sm uppercase tracking-wider font-semibold text-emerald-600">Sesi Aktif</h2>
                    </div>
                    <h3 class="text-2xl md:text-3xl font-bold text-gray-900 font-mono tracking-tight mb-2">
                        {{ $activeSession->session_number }}
                    </h3>
                    <p class="text-text-muted-light flex items-center gap-1.5 text-sm">
                        <span class="material-icons-round text-base">schedule</span>
                        Dibuka: {{ $activeSession->opened_at->format('d M Y, H:i') }}
                    </p>
                </div>
                <div class="flex flex-col md:flex-row items-start md:items-center gap-8 w-full md:w-auto">
                    <div class="text-left md:text-right">
                        <p class="text-sm text-text-muted-light font-medium mb-1">Saldo Awal</p>
                        <p class="text-3xl font-bold text-gray-900">
                            Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}
                        </p>
                    </div>
                    <a href="{{ route('pos.sessions.close') }}"
                        class="w-full md:w-auto bg-primary hover:bg-red-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-red-500/20 hover:shadow-red-500/40 transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <span class="material-icons-round">lock_clock</span>
                        Tutup Shift
                    </a>
                </div>
            </div>
        </div>

        {{-- Transactions Card --}}
        <div class="bg-surface-light rounded-2xl shadow-soft min-h-[500px] flex flex-col relative overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-round text-primary">receipt_long</span>
                    Transaksi Hari Ini
                </h2>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">
                        {{ $todaySales ? $todaySales->count() : 0 }} Transaksi
                    </span>
                </div>
            </div>

            @if($todaySales && $todaySales->count() > 0)
                {{-- Has Transactions State --}}
                <div class="p-6">
                    {{-- Stats Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <p class="text-gray-500 text-sm mb-1">Total Penjualan</p>
                            <p class="text-2xl font-bold text-gray-900">Rp
                                {{ number_format($todaySales->sum('total_amount'), 0, ',', '.') }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-xl">
                            <p class="text-gray-500 text-sm mb-1">Rata-rata</p>
                            <p class="text-2xl font-bold text-gray-900">Rp
                                {{ number_format($todaySales->avg('total_amount'), 0, ',', '.') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-100">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-gray-600 font-medium text-xs uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Invoice</th>
                                    <th class="px-6 py-4">Pelanggan</th>
                                    <th class="px-6 py-4">Total</th>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($todaySales->take(10) as $sale)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-sm text-gray-900">{{ $sale->invoice_number }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $sale->customer_name ?? 'Walk-in' }}</td>
                                        <td class="px-6 py-4 text-sm font-semibold text-gray-900">Rp
                                            {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $sale->created_at->format('H:i') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2.5 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">
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
                <div class="fixed bottom-8 right-8 z-50">
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white w-14 h-14 rounded-full shadow-xl shadow-violet-500/30 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 group">
                        <span
                            class="material-icons-round text-3xl group-hover:rotate-90 transition-transform duration-300">add</span>
                    </a>
                </div>

            @else
                {{-- Empty State (Matches Reference) --}}
                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center relative z-10">
                    <div class="w-48 h-48 rounded-full bg-gray-50 flex items-center justify-center mb-8 relative">
                        <div
                            class="absolute inset-0 border border-dashed border-gray-200 rounded-full animate-[spin_10s_linear_infinite]">
                        </div>
                        <span class="material-icons-round text-8xl text-gray-300">assignment_late</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Belum ada transaksi hari ini</h3>
                    <p class="text-text-muted-light max-w-md mb-8">
                        Data penjualan akan muncul di sini setelah Anda memulai transaksi pertama. Siap melayani pelanggan?
                    </p>
                    <a href="{{ route('pos.sales.create') }}"
                        class="bg-secondary hover:bg-violet-700 text-white px-8 py-3.5 rounded-xl font-bold text-lg shadow-lg shadow-violet-500/20 hover:shadow-violet-500/40 transition-all duration-300 transform hover:scale-105 flex items-center gap-3 group">
                        <span class="material-icons-round group-hover:rotate-12 transition-transform">add_shopping_cart</span>
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