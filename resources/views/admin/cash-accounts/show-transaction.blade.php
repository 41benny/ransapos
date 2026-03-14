@extends('layouts.admin')

@section('title', 'Detail Transaksi - ' . $voucherNumber)

@section('content')
    @php
        $primaryTransaction = $voucherTransactions->first() ?? $cashTransaction;
        $lastTransaction = $voucherTransactions->last() ?? $cashTransaction;
        $breakdownCount = $voucherTransactions->count();
        $hasBreakdown = $breakdownCount > 1;
    @endphp

    <div class="container mx-auto px-4 py-6">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <div class="mb-2 flex items-center space-x-2 text-sm text-gray-600">
                    <a href="{{ route('admin.cash-transactions.index', request()->query()) }}" class="hover:text-indigo-600">Transaksi</a>
                    <span>/</span>
                    <span class="text-gray-900">Detail</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Detail Transaksi</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Voucher {{ $voucherNumber }}
                    @if($hasBreakdown)
                        dengan {{ $breakdownCount }} breakdown input.
                    @else
                        tercatat sebagai 1 baris transaksi.
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.cash-transactions.edit', array_merge(['cashTransaction' => $cashTransaction], request()->query())) }}"
                    class="flex items-center space-x-2 rounded-lg bg-amber-500 px-4 py-2 text-white hover:bg-amber-600">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
                <a href="{{ route('admin.cash-transactions.print', $cashTransaction) }}" target="_blank"
                    class="ui-btn ui-btn-primary flex items-center space-x-2 rounded-lg bg-gray-600 px-4 py-2 text-white hover:bg-gray-700">
                    <i class="fas fa-print"></i>
                    <span>Cetak Voucher</span>
                </a>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Informasi Transaksi</h2>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-medium uppercase tracking-wider text-gray-500">Umum</h3>
                        <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Nomor Voucher</span>
                                <span class="font-medium text-gray-900">{{ $voucherNumber }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Tanggal</span>
                                <span class="font-medium text-gray-900">{{ $primaryTransaction->transaction_date->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Jenis</span>
                                <span class="px-2 py-0.5 text-xs rounded {{ $primaryTransaction->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $primaryTransaction->type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Status</span>
                                <span class="px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">Posted</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Jumlah Breakdown</span>
                                <span class="font-medium text-gray-900">{{ $breakdownCount }} baris</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-medium uppercase tracking-wider text-gray-500">Keuangan</h3>
                        <div class="space-y-3 rounded-lg bg-gray-50 p-4">
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Total Voucher</span>
                                <span class="text-lg font-bold {{ $primaryTransaction->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    Rp {{ number_format($totalAmount, 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Saldo Sebelum</span>
                                <span class="text-gray-900">Rp {{ number_format($primaryTransaction->balance_before, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-gray-600">Saldo Sesudah</span>
                                <span class="font-medium text-gray-900">Rp {{ number_format($lastTransaction->balance_after, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 border-t border-gray-100 pt-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-medium uppercase tracking-wider text-gray-500">Detail Akun</h3>
                        <div class="space-y-2">
                            <div>
                                <span class="block text-xs text-gray-500">Akun Kas/Bank</span>
                                <span class="font-medium text-gray-900">{{ $primaryTransaction->cashAccount->name }}
                                    ({{ $primaryTransaction->cashAccount->code }})</span>
                            </div>
                            <div>
                                <span class="block text-xs text-gray-500">COA</span>
                                @if($hasBreakdown)
                                    <span class="font-medium text-gray-900">Multi COA, lihat tabel breakdown di bawah</span>
                                @elseif($primaryTransaction->coaAccount)
                                    <span class="font-medium text-gray-900">
                                        {{ $primaryTransaction->coaAccount->code }} - {{ $primaryTransaction->coaAccount->name }}
                                    </span>
                                @else
                                    <span class="font-medium text-gray-900">-</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-medium uppercase tracking-wider text-gray-500">Keterangan</h3>
                        <div class="space-y-2">
                            <div>
                                <span class="block text-xs text-gray-500">Deskripsi</span>
                                <p class="text-gray-900">
                                    {{ $hasBreakdown ? 'Transaksi terdiri dari beberapa breakdown input. Detail tiap baris ada di tabel breakdown.' : $primaryTransaction->description }}
                                </p>
                            </div>
                            @if($primaryTransaction->notes)
                                <div>
                                    <span class="block text-xs text-gray-500">Catatan</span>
                                    <p class="text-gray-900 italic">{{ $primaryTransaction->notes }}</p>
                                </div>
                            @endif
                            @if($primaryTransaction->reference_type)
                                <div>
                                    <span class="block text-xs text-gray-500">Referensi</span>
                                    <p class="text-gray-900">{{ ucfirst($primaryTransaction->reference_type) }}
                                        #{{ $primaryTransaction->reference_id }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-6">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-medium uppercase tracking-wider text-gray-500">Breakdown Input</h3>
                            <p class="mt-1 text-sm text-gray-500">Semua baris yang dibuat dalam voucher/input yang sama ditampilkan di sini.</p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                            {{ $breakdownCount }} baris
                        </span>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">No. Transaksi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Deskripsi</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">COA</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Jumlah</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Saldo Setelah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($voucherTransactions as $index => $transaction)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $transaction->transaction_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            <div>{{ $transaction->description }}</div>
                                            @if($transaction->notes && $transaction->notes !== $primaryTransaction->notes)
                                                <div class="mt-1 text-xs italic text-gray-500">{{ $transaction->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            @if($transaction->coaAccount)
                                                <div class="font-medium text-gray-900">{{ $transaction->coaAccount->code }}</div>
                                                <div class="text-xs text-gray-500">{{ $transaction->coaAccount->name }}</div>
                                            @else
                                                <span>-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium {{ $transaction->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                            Rp {{ number_format($transaction->amount, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-900">
                                            Rp {{ number_format($transaction->balance_after, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total Voucher</td>
                                    <td class="px-4 py-3 text-right text-sm font-bold {{ $primaryTransaction->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                        Rp {{ number_format($totalAmount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex flex-col gap-2 border-t border-gray-100 pt-6 text-sm text-gray-500 md:flex-row md:items-center md:justify-between">
                    <div>
                        Dibuat pada: {{ $primaryTransaction->created_at->format('d M Y H:i') }}
                        @if($primaryTransaction->creator)
                            oleh {{ $primaryTransaction->creator->name }}
                        @endif
                    </div>
                    <div>
                        Terakhir diupdate: {{ $lastTransaction->updated_at->format('d M Y H:i') }}
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 pt-6 md:flex-row md:items-center md:justify-between">
                    @if($hasBreakdown)
                        <p class="text-sm text-amber-700">
                            Tombol hapus di bawah hanya menghapus baris transaksi yang sedang dipilih, bukan seluruh voucher.
                        </p>
                    @else
                        <div></div>
                    @endif

                    <form action="{{ route('admin.cash-transactions.destroy', $cashTransaction) }}" method="POST"
                        onsubmit="return confirm('PERINGATAN: Menghapus transaksi ini akan memicu perhitungan ulang saldo untuk semua transaksi setelah tanggal ini. Apakah Anda yakin?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex items-center space-x-1 text-sm font-medium text-red-600 hover:text-red-800">
                            <i class="fas fa-trash-alt"></i>
                            <span>{{ $hasBreakdown ? 'Hapus Baris Terpilih' : 'Hapus Transaksi Permanen' }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
