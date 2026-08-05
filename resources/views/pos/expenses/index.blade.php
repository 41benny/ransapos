@extends('layouts.pos')

@section('title', 'Pengeluaran Kios')
@section('page-title', 'Pengeluaran Kios')

@section('content')
<div class="h-full overflow-y-auto bg-gray-900">
    <div class="max-w-5xl mx-auto p-6 space-y-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-white">Pengeluaran Kios</h2>
                <p class="text-gray-400 text-sm mt-1">
                    Uang tunai yang keluar dari laci pada shift {{ $session->session_number }}.
                </p>
            </div>
            <a href="{{ route('pos.expenses.create') }}"
               class="px-5 py-3 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-500 hover:to-amber-600 text-white rounded-xl font-semibold shadow-lg shadow-amber-600/30 transition">
                + Input Pengeluaran
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-xl bg-green-900/30 border border-green-500/40 text-green-300">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-xl bg-red-900/30 border border-red-500/40 text-red-300">{{ session('error') }}</div>
        @endif

        {{-- Ringkasan laci --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 bg-gray-800/80 rounded-2xl border border-gray-700/50">
                <p class="text-xs text-gray-400 mb-1">Sisa Uang di Laci</p>
                <p class="text-2xl font-bold text-white">Rp {{ number_format($onHand, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Modal + penerimaan tunai &minus; pengeluaran</p>
            </div>
            <div class="p-5 bg-gray-800/80 rounded-2xl border border-gray-700/50">
                <p class="text-xs text-gray-400 mb-1">Pengeluaran Tunai</p>
                <p class="text-2xl font-bold text-red-400">Rp {{ number_format($totalCashExpense, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Mengurangi saldo laci</p>
            </div>
            <div class="p-5 bg-gray-800/80 rounded-2xl border border-gray-700/50">
                <p class="text-xs text-gray-400 mb-1">Pengeluaran Non-Tunai</p>
                <p class="text-2xl font-bold text-sky-400">Rp {{ number_format($totalNoncashExpense, 0, ',', '.') }}</p>
                <p class="text-[11px] text-gray-500 mt-1">Tidak mengurangi laci</p>
            </div>
        </div>

        {{-- Daftar baris --}}
        <div class="bg-gray-800/80 rounded-2xl border border-gray-700/50 overflow-hidden">
            @if($expenses->isEmpty())
                <div class="p-10 text-center text-gray-500">
                    Belum ada pengeluaran pada shift ini.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-900/60 text-gray-400 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Waktu</th>
                                <th class="px-4 py-3 text-left font-medium">Keperluan</th>
                                <th class="px-4 py-3 text-left font-medium">Kategori</th>
                                <th class="px-4 py-3 text-left font-medium">Sumber</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                                <th class="px-4 py-3 text-center font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700/50">
                            @foreach($expenses as $expense)
                                <tr class="{{ $expense->isVoided() ? 'bg-gray-900/40 opacity-60' : 'bg-gray-800/30' }}">
                                    <td class="px-4 py-3 text-gray-300 whitespace-nowrap">
                                        {{ $expense->created_at->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-white font-medium {{ $expense->isVoided() ? 'line-through' : '' }}">
                                            {{ $expense->description }}
                                        </p>
                                        <p class="text-xs text-gray-400">Penerima: {{ $expense->recipient_name }}</p>
                                        @if($expense->isVoided())
                                            <p class="text-xs text-red-400 mt-1">
                                                Dibatalkan: {{ $expense->void_reason }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-300">
                                        {{ $expense->coaAccount->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs">
                                        {{ \App\Models\CashSessionExpense::paymentSourceLabels()[$expense->payment_source] ?? $expense->payment_source }}
                                        @if($expense->sourceCashAccount)
                                            <span class="block text-gray-500">{{ $expense->sourceCashAccount->name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-mono font-semibold {{ $expense->isVoided() ? 'text-gray-500 line-through' : 'text-white' }}">
                                        Rp {{ number_format((float) $expense->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if(! $expense->isVoided())
                                            <form action="{{ route('pos.expenses.void', $expense) }}" method="POST"
                                                  onsubmit="return promptVoidReason(this)">
                                                @csrf
                                                <input type="hidden" name="void_reason" value="">
                                                <button type="submit"
                                                        class="px-3 py-1.5 text-xs bg-red-900/40 hover:bg-red-800/60 text-red-300 border border-red-700/50 rounded-lg transition">
                                                    Batalkan
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-600">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-500 leading-relaxed">
            Pembatalan tidak menghapus baris aslinya. Nominal yang pernah tercatat tetap
            tersimpan sebagai jejak audit, karena untuk uang tunai angka yang pernah
            dicatat adalah fakta yang harus bisa ditelusuri.
        </p>

        <a href="{{ route('pos.dashboard') }}" class="inline-block text-gray-400 hover:text-white text-sm">&larr; Kembali ke Dashboard</a>
    </div>
</div>

<script>
function promptVoidReason(form) {
    const reason = window.prompt('Alasan pembatalan (wajib diisi):');
    if (reason === null) return false;

    if (reason.trim() === '') {
        alert('Alasan pembatalan wajib diisi.');
        return false;
    }

    form.querySelector('input[name="void_reason"]').value = reason.trim();
    return true;
}
</script>
@endsection
