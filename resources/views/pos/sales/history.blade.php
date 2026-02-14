@extends('layouts.pos_theme')

@section('content')
    <div class="bg-surface-light rounded-2xl shadow-soft flex flex-col relative overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span class="material-icons-round text-primary text-xl">history</span>
                    Riwayat Penjualan
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">Transaksi sesi ini</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pos.dashboard') }}"
                    class="flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-all text-sm">
                    <span class="material-icons-round text-base">arrow_back</span>
                    Kembali
                </a>
            </div>
        </div>

        <div class="p-0">
            @if(count($sales) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-600 font-medium text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3 border-b border-gray-100">Invoice</th>
                                <th class="px-6 py-3 border-b border-gray-100">Waktu</th>
                                <th class="px-6 py-3 border-b border-gray-100">Pelanggan</th>
                                <th class="px-6 py-3 border-b border-gray-100">Metode Bayar</th>
                                <th class="px-6 py-3 border-b border-gray-100 text-right">Total</th>
                                <th class="px-6 py-3 border-b border-gray-100 text-center">Status</th>
                                <th class="px-6 py-3 border-b border-gray-100 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @foreach($sales as $sale)
                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                    <td class="px-6 py-4 font-mono font-medium text-gray-900">
                                        {{ $sale->invoice_number }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $sale->created_at->format('d M H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-900">
                                        {{ $sale->customer_name ?? 'Walk-in' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        @foreach($sale->payments as $payment)
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded bg-gray-100 text-xs">
                                                {{ $payment->paymentMethod->name ?? '-' }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-900 text-right">
                                        Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($sale->status == 'completed')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                Selesai
                                            </span>
                                        @elseif($sale->status == 'cancelled')
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-100">
                                                Void
                                            </span>
                                        @else
                                            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                {{ ucfirst($sale->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2 opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity">
                                            <a href="{{ route('pos.sales.print', $sale->id) }}" target="_blank"
                                                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Print Struk">
                                                <span class="material-icons-round text-xl">print</span>
                                            </a>
                                            <!-- Validasi void bisa ditambahkan di sini -->
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-400">
                        <span class="material-icons-round text-3xl">history_toggle_off</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Belum ada riwayat</h3>
                    <p class="text-gray-500 text-sm max-w-sm mt-1">Transaksi yang Anda lakukan pada sesi ini akan muncul di sini.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
