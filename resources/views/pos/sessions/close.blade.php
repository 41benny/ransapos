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
                        <div class="space-y-4 mb-8">
                            <div class="flex items-center justify-between p-4 bg-gray-700/30 rounded-xl border border-gray-600/30">
                                <div>
                                    <p class="text-sm text-gray-400 mb-1">Saldo Awal (Modal)</p>
                                    <p class="text-xl font-bold text-white">Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-400 mb-1">Total Tunai Masuk</p>
                                    <p class="text-xl font-bold text-green-400">+ Rp {{ number_format($activeSession->total_cash, 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <div class="p-4 bg-indigo-900/20 rounded-xl border border-indigo-500/30 text-center">
                                <p class="text-sm text-indigo-300 mb-1 font-medium">Total Uang Tunai yang Seharusnya Ada</p>
                                <p class="text-3xl font-bold text-white tracking-tight">Rp {{ number_format($activeSession->expected_balance, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        <!-- Input Actual Cash -->
                        <div class="mb-6">
                            <label for="actual_balance" class="block text-sm font-medium text-gray-300 mb-2">
                                Kas Fisik Aktual <span class="text-red-400">*</span>
                            </label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <span class="text-gray-400 text-lg font-bold">Rp</span>
                                </div>
                                <input type="number" 
                                       name="actual_balance" 
                                       id="actual_balance" 
                                       value="{{ old('actual_balance', $activeSession->expected_balance) }}"
                                       step="100"
                                       min="0"
                                       required
                                       class="w-full pl-12 pr-4 py-4 bg-gray-900 text-white text-2xl font-bold rounded-xl border border-gray-700 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition shadow-inner placeholder-gray-600">
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
    </div>
</div>

<script>
// Calculate difference real-time
document.getElementById('actual_balance').addEventListener('input', function() {
    const expected = {{ $activeSession->expected_balance }};
    const actual = parseFloat(this.value) || 0;
    const difference = actual - expected;
    
    const preview = document.getElementById('differencePreview');
    const text = document.getElementById('differenceText');
    const desc = document.getElementById('diffDesc');
    
    if (difference !== 0) {
        preview.classList.remove('hidden');
        
        if (difference > 0) {
            preview.className = 'mb-6 p-4 rounded-xl border bg-green-900/20 border-green-500/30 text-green-400';
            text.textContent = '+ Rp ' + Math.abs(difference).toLocaleString('id-ID');
            desc.textContent = 'Uang Fisik LEBIH BANYAK dari sistem.';
        } else {
            preview.className = 'mb-6 p-4 rounded-xl border bg-red-900/20 border-red-500/30 text-red-400';
            text.textContent = '- Rp ' + Math.abs(difference).toLocaleString('id-ID');
            desc.textContent = 'Uang Fisik KURANG dari sistem (Minus).';
        }
    } else {
        preview.classList.add('hidden');
    }
});

// Confirmation before closing
document.getElementById('closeForm').addEventListener('submit', function(e) {
    if (!confirm('PERINGATAN FINAL:\n\nApakah Anda yakin perhitungan kas sudah benar?\nSetelah ditutup, shift ini tidak dapat diubah lagi.')) {
        e.preventDefault();
    }
});
</script>
@endsection

