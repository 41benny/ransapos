@extends('layouts.pos_theme')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <div class="bg-surface-light rounded-2xl shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Petty Cash Outlet</h2>
                <p class="text-sm text-gray-500 mt-0.5">Riwayat transaksi hari ini</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.dashboard') }}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 font-medium transition">
                    <span class="material-icons-round text-base">arrow_back</span>
                    Dashboard
                </a>
                <a href="{{ route('pos.petty-cash.create') }}"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary hover:bg-red-700 text-sm text-white font-semibold transition">
                    <span class="material-icons-round text-base">add</span>
                    Input Baru
                </a>
            </div>
        </div>

        <div class="p-6 space-y-5">
            @if(session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($pettyCashAccount)
                <div class="rounded-xl border {{ (float) $pettyCashAccount->current_balance < 0 ? 'border-rose-200 bg-rose-50/70' : 'border-amber-200 bg-amber-50/60' }} px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">Saldo Saat Ini</p>
                            <p class="text-base font-semibold {{ (float) $pettyCashAccount->current_balance < 0 ? 'text-rose-700' : 'text-gray-900' }}">
                                Rp {{ number_format((float) $pettyCashAccount->current_balance, 0, ',', '.') }}
                            </p>
                        </div>
                        <p class="text-xs {{ (float) $pettyCashAccount->current_balance < 0 ? 'text-rose-700' : 'text-amber-700' }}">
                            Saldo petty cash boleh minus untuk mencatat kondisi aktual outlet.
                        </p>
                    </div>
                </div>
            @endif

            @if(!$pettyCashAccount)
                <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    Akun petty cash outlet belum disetting. Hubungi admin agar kasir bisa input dan memantau petty cash.
                </div>
            @elseif(!$transactions || $transactions->count() === 0)
                <div class="rounded-xl border border-gray-200 bg-white py-12 text-center">
                    <p class="text-sm text-gray-500">Belum ada transaksi petty cash untuk hari ini.</p>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                    <table class="min-w-full text-xs md:text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold whitespace-nowrap">Tanggal</th>
                                <th class="px-3 py-2 text-left font-semibold">Deskripsi</th>
                                <th class="px-3 py-2 text-right font-semibold whitespace-nowrap">Jumlah</th>
                                <th class="px-3 py-2 text-right font-semibold whitespace-nowrap">Saldo</th>
                                <th class="px-3 py-2 text-center font-semibold whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transactions as $transaction)
                                @php
                                    $itemDescription = $transaction->description;
                                    if (preg_match('/^Penerima:\s*(.*?)\s*\|\s*(.*)$/u', (string) $transaction->description, $matches)) {
                                        $recipientName = trim((string) ($matches[1] ?? '-'));
                                        $itemDescription = trim((string) ($matches[2] ?? ''));
                                        $itemDescription = $recipientName . ' - ' . $itemDescription;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50/70">
                                    <td class="px-3 py-2 whitespace-nowrap text-gray-700">
                                        {{ $transaction->transaction_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-700">
                                        {{ $itemDescription }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right font-semibold text-rose-700">
                                        Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right {{ (float) $transaction->balance_after < 0 ? 'text-rose-700 font-semibold' : 'text-gray-800' }}">
                                        Rp {{ number_format((float) $transaction->balance_after, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <a href="{{ route('pos.petty-cash.edit', $transaction) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50"
                                            title="Edit">
                                            <span class="material-icons-round text-base">edit</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
