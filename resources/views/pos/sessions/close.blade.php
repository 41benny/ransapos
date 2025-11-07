@extends('layouts.pos')

@section('title', 'Tutup Shift Kasir')
@section('page-title', 'Tutup Shift Kasir')

@section('content')
<div class="h-full overflow-y-auto p-6">
    <div class="max-w-4xl mx-auto">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            
            <!-- Session Info -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Informasi Shift</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-400">Nomor Session</p>
                        <p class="text-white font-medium">{{ $activeSession->session_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Outlet</p>
                        <p class="text-white font-medium">{{ $activeSession->outlet->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Kasir</p>
                        <p class="text-white font-medium">{{ $activeSession->user->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Dibuka</p>
                        <p class="text-white font-medium">{{ $activeSession->opened_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Sales Summary -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Ringkasan Penjualan</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Transaksi</span>
                        <span class="text-white font-semibold">{{ $activeSession->sales->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Total Penjualan</span>
                        <span class="text-white font-semibold">Rp {{ number_format($activeSession->total_sales, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Cash</span>
                        <span class="text-white font-semibold">Rp {{ number_format($activeSession->total_cash, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Non-Cash</span>
                        <span class="text-white font-semibold">Rp {{ number_format($activeSession->total_non_cash, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Calculation -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-white mb-6">Perhitungan Kas</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <p class="text-sm text-gray-400 mb-1">Saldo Awal</p>
                    <p class="text-2xl font-bold text-white">Rp {{ number_format($activeSession->opening_balance, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-400 mb-1">Cash Masuk</p>
                    <p class="text-2xl font-bold text-green-400">+ Rp {{ number_format($activeSession->total_cash, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-400 mb-1">Kas yang Seharusnya</p>
                    <p class="text-2xl font-bold text-indigo-400">Rp {{ number_format($activeSession->expected_balance, 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Form Close -->
            <form action="{{ route('pos.sessions.close.store', $activeSession) }}" method="POST" id="closeForm">
                @csrf
                
                <div class="border-t border-gray-700 pt-6">
                    <div class="mb-6">
                        <label for="actual_balance" class="block text-sm font-medium text-gray-300 mb-2">
                            Kas Fisik Aktual <span class="text-red-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-400">Rp</span>
                            <input type="number" 
                                   name="actual_balance" 
                                   id="actual_balance" 
                                   value="{{ old('actual_balance', $activeSession->expected_balance) }}"
                                   step="1000"
                                   min="0"
                                   required
                                   class="w-full pl-12 pr-4 py-3 bg-gray-700 text-white text-xl font-bold rounded-lg border border-gray-600 focus:border-indigo-500 focus:outline-none @error('actual_balance') border-red-500 @enderror">
                        </div>
                        @error('actual_balance')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-gray-400">Hitung dan masukkan jumlah uang fisik di laci kasir saat ini</p>
                    </div>

                    <!-- Difference Preview -->
                    <div id="differencePreview" class="hidden mb-6 p-4 rounded-lg">
                        <p class="text-sm font-medium mb-1">Selisih:</p>
                        <p id="differenceText" class="text-2xl font-bold"></p>
                    </div>

                    <div class="mb-8">
                        <label for="notes" class="block text-sm font-medium text-gray-300 mb-2">
                            Catatan Penutupan (Opsional)
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3"
                                  placeholder="Catatan tambahan saat tutup shift..."
                                  class="w-full px-4 py-3 bg-gray-700 text-white rounded-lg border border-gray-600 focus:border-indigo-500 focus:outline-none @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex space-x-3">
                        <a href="{{ route('pos.dashboard') }}" 
                           class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg font-semibold text-center transition">
                            Batal
                        </a>
                        <button type="submit" 
                                class="flex-1 px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition">
                            Tutup Shift
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Recent Transactions -->
        @if($activeSession->sales->count() > 0)
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Transaksi Terakhir</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-300">Invoice</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-300">Waktu</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-300">Pembayaran</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-300">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        @foreach($activeSession->sales->take(10) as $sale)
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-white">{{ $sale->invoice_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-300">{{ $sale->created_at->format('H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-300">
                                {{ $sale->payments->first()->paymentMethod->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-white">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
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
    
    if (difference !== 0) {
        preview.classList.remove('hidden');
        
        if (difference > 0) {
            preview.classList.remove('bg-red-900', 'border-red-700');
            preview.classList.add('bg-green-900', 'border-green-700', 'border');
            text.classList.remove('text-red-400');
            text.classList.add('text-green-400');
            text.textContent = '+ Rp ' + Math.abs(difference).toLocaleString('id-ID') + ' (Lebih)';
        } else {
            preview.classList.remove('bg-green-900', 'border-green-700');
            preview.classList.add('bg-red-900', 'border-red-700', 'border');
            text.classList.remove('text-green-400');
            text.classList.add('text-red-400');
            text.textContent = '- Rp ' + Math.abs(difference).toLocaleString('id-ID') + ' (Kurang)';
        }
    } else {
        preview.classList.add('hidden');
    }
});

// Confirmation before closing
document.getElementById('closeForm').addEventListener('submit', function(e) {
    if (!confirm('Apakah Anda yakin ingin menutup shift? Aksi ini tidak bisa dibatalkan.')) {
        e.preventDefault();
    }
});
</script>
@endsection

