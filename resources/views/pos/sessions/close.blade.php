@extends('layouts.pos')

@section('title', 'Tutup Shift Kasir')
@section('page-title', 'Tutup Shift')

@section('content')
<div class="h-full overflow-y-auto relative bg-gray-900">
    <!-- Background Decoration -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl mix-blend-screen animate-pulse"></div>
        <div class="absolute bottom-0 left-1/4 w-96 h-96 bg-purple-600/10 rounded-full blur-3xl mix-blend-screen"></div>
    </div>

    <div class="max-w-6xl mx-auto p-6 md:p-8 relative z-10">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white tracking-tight">Tutup Shift Kasir</h2>
                <p class="text-gray-400 mt-1">Verifikasi penjualan dan kas fisik sebelum menutup shift.</p>
            </div>
            
            <!-- Print Button (Preview) -->
            <button onclick="window.open('{{ route('pos.sessions.print', $activeSession->id) }}', '_blank', 'width=400,height=600')" 
                    class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-white rounded-xl border border-gray-700 flex items-center gap-2 transition shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Laporan Sementara</span>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Left Column: Summary & Payment breakdown -->
            <div class="space-y-6">
                <!-- Session Info Card -->
                <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Informasi Shift
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-900/50 rounded-xl border border-gray-700/50">
                            <p class="text-xs text-gray-400 mb-1">Nomor Session</p>
                            <p class="text-white font-mono font-medium truncate" title="{{ $activeSession->session_number }}">{{ $activeSession->session_number }}</p>
                        </div>
                        <div class="p-4 bg-gray-900/50 rounded-xl border border-gray-700/50">
                            <p class="text-xs text-gray-400 mb-1">Kasir Bertugas</p>
                            <p class="text-white font-medium">{{ $activeSession->user->name }}</p>
                        </div>
                        <div class="p-4 bg-gray-900/50 rounded-xl border border-gray-700/50">
                            <p class="text-xs text-gray-400 mb-1">Waktu Buka</p>
                            <p class="text-white font-medium">{{ $activeSession->opened_at->format('d M H:i') }}</p>
                        </div>
                        <div class="p-4 bg-gray-900/50 rounded-xl border border-gray-700/50">
                            <p class="text-xs text-gray-400 mb-1">Waktu Sekarang</p>
                            <p class="text-white font-medium">{{ now()->format('d M H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Breakdown -->
                <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Rincian Metode Pembayaran
                    </h3>
                    
                    @if(count($paymentStats) > 0)
                    <div class="overflow-hidden rounded-xl border border-gray-700/50">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-900/50 text-gray-400 text-xs uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left font-medium">Metode</th>
                                    <th class="px-4 py-3 text-center font-medium">Transaksi</th>
                                    <th class="px-4 py-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                @foreach($paymentStats as $stat)
                                <tr class="bg-gray-800/30 hover:bg-gray-700/50 transition">
                                    <td class="px-4 py-3 font-medium text-white">{{ $stat['name'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-300">{{ $stat['count'] }}</td>
                                    <td class="px-4 py-3 text-right text-white font-mono font-semibold">
                                        Rp {{ number_format($stat['total'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-indigo-900/20 border-t border-indigo-500/30">
                                <tr>
                                    <td class="px-4 py-3 font-bold text-white">Grand Total</td>
                                    <td class="px-4 py-3 text-center text-white font-bold">{{ $activeSession->sales->count() }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-300 font-mono font-bold text-base">
                                        Rp {{ number_format($activeSession->total_sales, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-500 bg-gray-900/30 rounded-xl border border-dashed border-gray-700">
                        Belum ada transaksi pada shift ini.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Cash Reconciliation & Action -->
            <div class="space-y-6">
                <form action="{{ route('pos.sessions.close.store', $activeSession) }}" method="POST" id="closeForm" class="h-full flex flex-col">
                    @csrf
                    
                    <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl flex-1">
                        <h3 class="text-lg font-semibold text-white mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Rekonsiliasi Kas
                        </h3>

                        <!-- Cash Flow Visualize -->
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-700/30 rounded-xl border border-gray-600/30">
                                <span class="text-sm text-gray-400">Modal awal</span>
                                <span class="text-lg font-bold text-white font-mono">Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-700/30 rounded-xl border border-gray-600/30">
                                <span class="text-sm text-gray-400">Penerimaan tunai</span>
                                <span class="text-lg font-bold text-green-400 font-mono">+ Rp {{ number_format($activeSession->total_cash, 0, ',', '.') }}</span>
                            </div>

                            @if($drawerEnabled)
                            {{-- Kas masuk ditampilkan sebagai baris tersendiri, bukan dilebur ke
                                 modal awal: modal awal terkunci mengikuti shift sebelumnya, dan
                                 melebur keduanya membuat angka di layar tidak cocok dengan yang
                                 tersimpan. --}}
                            @if((float) $activeSession->total_cash_in > 0)
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-700/30 rounded-xl border border-gray-600/30">
                                <span class="text-sm text-gray-400">
                                    Kas masuk / modal tambahan
                                    @if($drawerCashIns->isNotEmpty())
                                        <button type="button" onclick="document.getElementById('cashInDetail').classList.toggle('hidden')"
                                                class="ml-1 text-xs text-indigo-400 hover:text-indigo-300 underline">rincian</button>
                                    @endif
                                </span>
                                <span class="text-lg font-bold text-emerald-400 font-mono">+ Rp {{ number_format($activeSession->total_cash_in, 0, ',', '.') }}</span>
                            </div>

                            <div id="cashInDetail" class="hidden px-4 py-3 bg-gray-900/50 rounded-xl border border-gray-700/50 space-y-2">
                                @foreach($drawerCashIns as $cashIn)
                                    <div class="flex items-start justify-between gap-3 text-xs">
                                        <div class="min-w-0">
                                            <p class="text-gray-300 truncate">{{ $cashIn->description }}</p>
                                            <p class="text-gray-500">
                                                {{ $cashIn->recipient_name }} &middot;
                                                {{ $cashIn->isOwnerCapital() ? 'uang pribadi pemilik' : ($cashIn->sourceCashAccount->name ?? 'akun kas outlet') }}
                                            </p>
                                        </div>
                                        <span class="font-mono text-emerald-300 whitespace-nowrap">Rp {{ number_format((float) $cashIn->amount, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            <div class="flex items-center justify-between px-4 py-3 bg-gray-700/30 rounded-xl border border-gray-600/30">
                                <span class="text-sm text-gray-400">
                                    Pengeluaran tunai
                                    @if($drawerExpenses->isNotEmpty())
                                        <button type="button" onclick="document.getElementById('expenseDetail').classList.toggle('hidden')"
                                                class="ml-1 text-xs text-indigo-400 hover:text-indigo-300 underline">rincian</button>
                                    @endif
                                </span>
                                <span class="text-lg font-bold text-red-400 font-mono">&minus; Rp {{ number_format($activeSession->total_cash_expense, 0, ',', '.') }}</span>
                            </div>

                            <div id="expenseDetail" class="hidden px-4 py-3 bg-gray-900/50 rounded-xl border border-gray-700/50 space-y-2">
                                @foreach($drawerExpenses as $expense)
                                    <div class="flex items-start justify-between gap-3 text-xs">
                                        <div class="min-w-0">
                                            <p class="text-gray-300 truncate">{{ $expense->description }}</p>
                                            <p class="text-gray-500">{{ $expense->recipient_name }} &middot; {{ \App\Models\CashSessionExpense::paymentSourceLabels()[$expense->payment_source] ?? '' }}</p>
                                        </div>
                                        <span class="font-mono text-gray-300 whitespace-nowrap">Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}</span>
                                    </div>
                                @endforeach
                            </div>
                            @endif

                            <div class="p-4 bg-indigo-900/20 rounded-xl border border-indigo-500/30 text-center">
                                <p class="text-sm text-indigo-300 mb-1 font-medium">Saldo Laci Seharusnya</p>
                                <p class="text-3xl font-bold text-white tracking-tight">Rp {{ number_format($activeSession->expected_balance, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <!-- Input Actual Cash -->
                        <div class="mb-6">
                            <label for="actual_balance" class="block text-sm font-medium text-gray-300 mb-2">
                                Hitung Fisik <span class="text-red-400">*</span>
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-lg font-bold">Rp</span>
                                </div>
                                {{--
                                    Di-prefill dengan Saldo Laci Seharusnya atas permintaan eksplisit
                                    operasional (mengikuti perilaku lama). CATATAN RISIKO: kasir yang
                                    tidak mengubah angka ini akan submit dengan selisih otomatis Rp 0
                                    tanpa benar-benar menghitung uang fisik — rekonsiliasi jadi tidak
                                    efektif untuk kasir yang tidak disiplin mengubahnya. Sudah
                                    disampaikan ke pemilik sistem dan tetap dipilih dengan sadar.

                                    Input teks (bukan type="number") supaya bisa diberi pemisah ribuan
                                    saat diketik. Nilai mentah yang benar-benar dikirim ke server
                                    disimpan di hidden input actual_balance, disinkronkan oleh JS.
                                --}}
                                <input type="text"
                                       inputmode="numeric"
                                       id="actual_balance_display"
                                       autocomplete="off"
                                       placeholder="Hitung dulu uang di laci"
                                       class="w-full pl-12 pr-4 py-4 bg-gray-900 text-white text-2xl font-bold rounded-xl border border-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition shadow-inner placeholder-gray-600 placeholder:text-base placeholder:font-normal">
                                <input type="hidden" name="actual_balance" id="actual_balance"
                                       value="{{ old('actual_balance', (int) $activeSession->expected_balance) }}">
                            </div>
                            <p class="mt-2 text-xs text-gray-400">Hitung manual uang tunai di laci dan masukkan nominalnya di sini.</p>
                        </div>

                        <!-- Difference Preview -->
                        <div id="differencePreview" class="hidden mb-6 p-4 rounded-xl border animate-fade-in-up">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">Selisih:</span>
                                <span id="differenceText" class="text-xl font-bold"></span>
                            </div>
                            <p id="diffDesc" class="text-xs mt-1 opacity-80"></p>
                        </div>

                        @if($drawerEnabled)
                        <!-- Modal disisakan & setoran -->
                        <div class="mb-6">
                            <label for="retained_balance" class="block text-sm font-medium text-gray-300 mb-2">
                                Modal Disisakan untuk Shift Berikutnya
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 font-bold">Rp</span>
                                </div>
                                {{--
                                    Default mengikuti modal awal shift ini (bukan 0): kios biasanya
                                    menyisakan modal yang sama tiap hari, jadi kasir tidak perlu
                                    ketik ulang angka yang sama tiap closing. Tetap bisa diubah
                                    kalau memang mau menyisakan modal yang berbeda.

                                    Sama seperti Hitung Fisik: input teks bermasking + hidden input
                                    untuk nilai mentah yang dikirim ke server.
                                --}}
                                <input type="text"
                                       inputmode="numeric"
                                       id="retained_balance_display"
                                       autocomplete="off"
                                       class="w-full pl-12 pr-4 py-3 bg-gray-900 text-white text-lg font-bold rounded-xl border border-gray-700 focus:border-indigo-500 focus:outline-none">
                                <input type="hidden" name="retained_balance" id="retained_balance"
                                       value="{{ old('retained_balance', (int) $activeSession->opening_balance) }}">
                            </div>
                            <p class="mt-2 text-xs text-gray-400">Uang yang ditinggal di laci. Menjadi modal awal shift berikutnya. Default mengikuti modal awal hari ini — ubah kalau perlu.</p>
                            <p class="mt-1 text-xs text-red-400 hidden" id="retainedWarn"></p>
                        </div>

                        <div class="mb-8 p-5 bg-emerald-900/20 rounded-xl border border-emerald-500/30 text-center">
                            <p class="text-sm text-emerald-300 mb-1 font-medium">SETORAN</p>
                            <p class="text-3xl font-bold text-white tracking-tight" id="depositAmount">Rp 0</p>
                            <p class="text-[11px] text-emerald-400/70 mt-1">Hitung fisik &minus; modal disisakan</p>
                        </div>
                        @endif

                        <!-- Notes -->
                        <div class="mb-8">
                            <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                                Catatan (Opsional)
                            </label>
                            <textarea name="notes" 
                                      id="notes" 
                                      rows="3"
                                      placeholder="Catatan jika ada selisih atau kejadian khusus..."
                                      class="w-full px-4 py-3 bg-gray-900 text-white rounded-xl border border-gray-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none transition resize-none">{{ old('notes') }}</textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-4 mt-auto">
                            <a href="{{ route('pos.dashboard') }}" 
                               class="flex-1 py-3.5 bg-gray-700 hover:bg-gray-600 text-white rounded-xl font-semibold text-center transition border border-gray-600">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="flex-[2] py-3.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white rounded-xl font-semibold transition shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 group">
                                <svg class="w-5 h-5 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                TUTUP SHIFT
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ====================== CLOSING PACKAGING ====================== --}}
        @if(count($packaging['rows']) > 0)
        <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl mb-8">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Closing Packaging
                </h3>
                @if($packaging['has_pending'])
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-900/40 text-amber-300 border border-amber-500/30">
                    ⚠ Ada adjustment pending — Closing With Pending Adjustment
                </span>
                @endif
            </div>

            @if(($packaging['dimsum_pieces_sold'] ?? 0) > 0)
            {{--
                Total pcs dimsum yang laku pada shift ini (dari Estimasi Sales, bukan
                hitung fisik box) — dijumlah dari baris "Box N" di tabel di bawah, N
                diambil dari nama box itu sendiri (Box 4 = 4 pcs, Box 32 = 32 pcs, dst).
            --}}
            <div class="mb-4 p-4 rounded-xl bg-orange-900/20 border border-orange-500/30 flex items-center justify-between">
                <span class="text-sm font-medium text-orange-200">Total Pcs Dimsum Laku (dari penjualan shift ini)</span>
                <span class="text-2xl font-bold text-white">{{ number_format($packaging['dimsum_pieces_sold'], 0, ',', '.') }} pcs</span>
            </div>
            @endif

            <div class="overflow-x-auto rounded-xl border border-gray-700/50">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-900/50 text-gray-400 text-[11px] uppercase tracking-wider">
                            <th class="px-3 py-3 text-left font-medium">Item</th>
                            <th class="px-3 py-3 text-right font-medium">Awal</th>
                            <th class="px-3 py-3 text-right font-medium">Masuk<br>(Appr)</th>
                            <th class="px-3 py-3 text-right font-medium">Keluar<br>(Appr)</th>
                            <th class="px-3 py-3 text-right font-medium">Pending</th>
                            <th class="px-3 py-3 text-center font-medium">Sisa Fisik</th>
                            <th class="px-3 py-3 text-right font-medium">Terpakai<br>Aktual</th>
                            <th class="px-3 py-3 text-right font-medium">Estimasi<br>Sales</th>
                            <th class="px-3 py-3 text-right font-medium">Selisih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach($packaging['rows'] as $row)
                        @php
                            $pendingNet = $row['pending_in'] - $row['pending_out'];
                        @endphp
                        <tr class="bg-gray-800/30" data-pkg-row
                            data-available="{{ (float) $row['available'] }}"
                            data-estimated="{{ (float) $row['estimated'] }}">
                            <td class="px-3 py-2.5 font-medium text-white">{{ $row['item']->name }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-300 font-mono">{{ (float) $row['opening_qty'] }}</td>
                            <td class="px-3 py-2.5 text-right text-green-400 font-mono">{{ (float) $row['approved_in'] }}</td>
                            <td class="px-3 py-2.5 text-right text-red-400 font-mono">{{ (float) $row['approved_out'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono {{ $pendingNet != 0 ? 'text-amber-300' : 'text-gray-600' }}">
                                {{ $pendingNet > 0 ? '+' : '' }}{{ (float) $pendingNet }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <input type="number" min="0" step="1"
                                       form="closeForm"
                                       name="packaging_physical[{{ $row['item']->id }}]"
                                       value="{{ (float) $row['default_physical'] }}"
                                       class="js-pkg-physical w-20 px-2 py-1 bg-gray-900 border border-gray-700 rounded-lg text-white text-right font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                            </td>
                            <td class="px-3 py-2.5 text-right font-mono text-white js-pkg-actual">{{ (float) ($row['available'] - $row['default_physical']) }}</td>
                            <td class="px-3 py-2.5 text-right font-mono text-indigo-300">{{ (float) $row['estimated'] }}</td>
                            <td class="px-3 py-2.5 text-right font-mono font-semibold js-pkg-diff">0</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-[11px] text-gray-500">
                Terpakai Aktual = Awal + Masuk Approved − Keluar Approved − Sisa Fisik. Selisih = Terpakai Aktual − Estimasi Sales.
                Nilai Sisa Fisik diisi awal dari estimasi sistem — koreksi sesuai hitungan fisik.
            </p>

            @if($packaging['unmapped']->count() > 0)
            <div class="mt-4 p-4 rounded-xl bg-amber-900/20 border border-amber-500/30">
                <p class="text-sm font-semibold text-amber-300 mb-2">⚠ Produk Belum Mapping Packaging ({{ $packaging['unmapped']->count() }})</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($packaging['unmapped'] as $up)
                    <span class="text-[11px] px-2 py-1 rounded-lg bg-gray-900/60 text-gray-300 border border-gray-700">
                        {{ $up->product_name }} <span class="text-gray-500">×{{ (float) $up->qty_sold }}</span>
                    </span>
                    @endforeach
                </div>
                <p class="mt-2 text-[11px] text-amber-200/70">Estimasi sales untuk produk di atas belum dihitung. Lengkapi mapping di backoffice.</p>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

<script>
// ===== Packaging closing live calc =====
function recalcPackagingRow(row) {
    const available = parseFloat(row.getAttribute('data-available')) || 0;
    const estimated = parseFloat(row.getAttribute('data-estimated')) || 0;
    const physical = parseFloat(row.querySelector('.js-pkg-physical').value) || 0;
    const actual = available - physical;
    const diff = actual - estimated;

    row.querySelector('.js-pkg-actual').textContent = actual;
    const diffEl = row.querySelector('.js-pkg-diff');
    diffEl.textContent = (diff > 0 ? '+' : '') + diff;
    diffEl.className = 'px-3 py-2.5 text-right font-mono font-semibold js-pkg-diff ' +
        (diff > 0 ? 'text-orange-400' : (diff < 0 ? 'text-sky-400' : 'text-gray-400'));
}

document.querySelectorAll('[data-pkg-row]').forEach(function (row) {
    recalcPackagingRow(row);
    const input = row.querySelector('.js-pkg-physical');
    if (input) {
        input.addEventListener('input', function () { recalcPackagingRow(row); });
    }
});

// Rekonsiliasi kas real-time.
(function () {
    const expected = {{ (float) $activeSession->expected_balance }};
    const tolerance = {{ (float) ($differenceTolerance ?? 0) }};
    const drawerEnabled = @json($drawerEnabled ?? false);

    // actualInput/retainedInput tetap merujuk ke hidden input — itu yang
    // benar-benar dikirim ke server. *_display adalah yang dilihat & diketik
    // kasir, diberi pemisah ribuan, dan tidak pernah dikirim sebagai form field.
    const actualInput = document.getElementById('actual_balance');
    const actualDisplay = document.getElementById('actual_balance_display');
    const retainedInput = document.getElementById('retained_balance');
    const retainedDisplay = document.getElementById('retained_balance_display');
    const depositLabel = document.getElementById('depositAmount');
    const retainedWarn = document.getElementById('retainedWarn');

    const preview = document.getElementById('differencePreview');
    const text = document.getElementById('differenceText');
    const desc = document.getElementById('diffDesc');

    function fmt(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    // Format tampilan dengan titik ribuan (id-ID), hanya menyisakan digit.
    function formatThousands(digitsOnly) {
        if (digitsOnly === '') return '';
        return Number(digitsOnly).toLocaleString('id-ID');
    }

    // Hubungkan satu pasang display(text, bermasking) <-> hidden(angka mentah).
    function bindMaskedNumber(displayEl, hiddenEl, onChange) {
        if (!displayEl || !hiddenEl) return;

        displayEl.value = formatThousands(hiddenEl.value.replace(/\D/g, ''));

        displayEl.addEventListener('input', function () {
            const digits = displayEl.value.replace(/\D/g, '');
            hiddenEl.value = digits;
            displayEl.value = formatThousands(digits);
            onChange();
        });
    }

    function recalc() {
        const actualRaw = actualInput.value;
        const actual = parseFloat(actualRaw) || 0;
        const difference = actual - expected;

        // Selama kasir belum mengisi apa pun, jangan tampilkan selisih —
        // menampilkan "-Rp sekian" pada field kosong hanya memancing kasir
        // mengetik angka yang membuatnya nol.
        if (actualRaw === '') {
            preview.classList.add('hidden');
        } else if (difference !== 0) {
            preview.classList.remove('hidden');
            if (difference > 0) {
                preview.className = 'mb-6 p-4 rounded-xl border bg-green-900/20 border-green-500/30 text-green-400';
                text.textContent = '+ ' + fmt(Math.abs(difference));
                desc.textContent = 'Uang fisik LEBIH dari saldo seharusnya.';
            } else {
                preview.className = 'mb-6 p-4 rounded-xl border bg-red-900/20 border-red-500/30 text-red-400';
                text.textContent = '- ' + fmt(Math.abs(difference));
                desc.textContent = 'Uang fisik KURANG dari saldo seharusnya.';
            }
        } else {
            preview.classList.remove('hidden');
            preview.className = 'mb-6 p-4 rounded-xl border bg-gray-700/30 border-gray-600/40 text-gray-300';
            text.textContent = fmt(0);
            desc.textContent = 'Kas cocok dengan saldo seharusnya.';
        }

        if (!drawerEnabled || !retainedInput) return;

        const retained = parseFloat(retainedInput.value) || 0;

        // Selama Hitung Fisik belum diisi, jangan tampilkan peringatan "melebihi
        // uang fisik" — Modal Disisakan sudah punya nilai default (mengikuti
        // modal awal) sebelum Hitung Fisik sempat diketik, jadi tanpa penjagaan
        // ini peringatannya salah muncul di setiap kali halaman baru dibuka.
        if (actualRaw === '') {
            retainedWarn.classList.add('hidden');
            depositLabel.textContent = fmt(0);
            return;
        }

        // Tidak bisa menyisakan uang yang tidak ada di laci.
        if (retained > actual) {
            retainedWarn.textContent = 'Modal disisakan melebihi uang fisik (' + fmt(actual) + ').';
            retainedWarn.classList.remove('hidden');
            depositLabel.textContent = fmt(0);
            return;
        }

        retainedWarn.classList.add('hidden');
        depositLabel.textContent = fmt(actual - retained);
    }

    bindMaskedNumber(actualDisplay, actualInput, recalc);
    bindMaskedNumber(retainedDisplay, retainedInput, recalc);
    recalc();

    // Confirmation before closing
    document.getElementById('closeForm').addEventListener('submit', function (e) {
        // actual_balance sekarang hidden input tanpa atribut `required` bawaan
        // browser (hidden field tidak bisa divalidasi native secara andal),
        // jadi wajib-isi ditegakkan manual di sini.
        if (actualInput.value.trim() === '') {
            e.preventDefault();
            alert('Hitung Fisik wajib diisi.');
            if (actualDisplay) actualDisplay.focus();
            return;
        }

        const actual = parseFloat(actualInput.value) || 0;
        const difference = actual - expected;
        const notes = document.getElementById('notes');

        // Selisih di atas toleransi wajib dijelaskan. Closing tetap boleh
        // dilanjutkan — memblokirnya hanya akan membuat kasir mengarang
        // angka fisik supaya bisa pulang.
        if (tolerance > 0 && Math.abs(difference) > tolerance && notes && notes.value.trim() === '') {
            e.preventDefault();
            alert('Selisih ' + fmt(Math.abs(difference)) + ' melebihi batas toleransi ' + fmt(tolerance) + '.\n\nIsi catatan penjelasan terlebih dahulu.');
            notes.focus();
            return;
        }

        if (!confirm('PERINGATAN FINAL:\n\nApakah Anda yakin perhitungan kas sudah benar?\nSetelah ditutup, shift ini tidak dapat diubah lagi.')) {
            e.preventDefault();
        }
    });
})();
</script>
@endsection

