@extends('layouts.admin')

@section('title', 'Detail Transaksi - ' . $cashTransaction->transaction_number)

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                    <a href="{{ route('admin.cash-transactions.index', request()->query()) }}" class="hover:text-indigo-600">Transaksi</a>
                    <span>/</span>
                    <span class="text-gray-900">Detail</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Detail Transaksi</h1>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.cash-transactions.edit', array_merge(['cashTransaction' => $cashTransaction], request()->query())) }}"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg flex items-center space-x-2">
                    <i class="fas fa-edit"></i>
                    <span>Edit</span>
                </a>
                <a href="{{ route('admin.cash-transactions.print', $cashTransaction) }}" target="_blank"
                    class="ui-btn ui-btn-primary px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center space-x-2">
                    <i class="fas fa-print"></i>
                    <span>Cetak Voucher</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informasi Transaksi</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Umum</h3>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Nomor Transaksi</span>
                                <span class="font-medium text-gray-900">{{ $cashTransaction->transaction_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tanggal</span>
                                <span
                                    class="font-medium text-gray-900">{{ $cashTransaction->transaction_date->format('d M Y') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Jenis</span>
                                <span
                                    class="px-2 py-0.5 rounded text-xs {{ $cashTransaction->type == 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $cashTransaction->type == 'in' ? 'Masuk' : 'Keluar' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status</span>
                                <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">Posted</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Keuangan</h3>
                        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Jumlah</span>
                                <span
                                    class="font-bold text-lg {{ $cashTransaction->type == 'in' ? 'text-green-600' : 'text-red-600' }}">
                                    Rp {{ number_format($cashTransaction->amount, 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Saldo Sebelum</span>
                                <span class="text-gray-900">Rp
                                    {{ number_format($cashTransaction->balance_before, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Saldo Sesudah</span>
                                <span class="font-medium text-gray-900">Rp
                                    {{ number_format($cashTransaction->balance_after, 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Detail Akun</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="block text-xs text-gray-500">Akun Kas/Bank</span>
                                    <span class="font-medium text-gray-900">{{ $cashTransaction->cashAccount->name }}
                                        ({{ $cashTransaction->cashAccount->code }})</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500">Akun Lawan (COA)</span>
                                    <span class="font-medium text-gray-900">
                                        @if($cashTransaction->coaAccount)
                                            {{ $cashTransaction->coaAccount->code }} - {{ $cashTransaction->coaAccount->name }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Keterangan</h3>
                            <div class="space-y-2">
                                <div>
                                    <span class="block text-xs text-gray-500">Deskripsi</span>
                                    <p class="text-gray-900">{{ $cashTransaction->description }}</p>
                                </div>
                                @if($cashTransaction->notes)
                                    <div>
                                        <span class="block text-xs text-gray-500">Catatan</span>
                                        <p class="text-gray-900 italic">{{ $cashTransaction->notes }}</p>
                                    </div>
                                @endif
                                @if($cashTransaction->reference_type)
                                    <div>
                                        <span class="block text-xs text-gray-500">Referensi</span>
                                        <p class="text-gray-900">{{ ucfirst($cashTransaction->reference_type) }}
                                            #{{ $cashTransaction->reference_id }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100 flex justify-between items-center text-sm text-gray-500">
                    <div>
                        Dibuat pada: {{ $cashTransaction->created_at->format('d M Y H:i') }}
                        @if($cashTransaction->creator)
                            oleh {{ $cashTransaction->creator->name }}
                        @endif
                    </div>
                    <div>
                        Terakhir diupdate: {{ $cashTransaction->updated_at->format('d M Y H:i') }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <form action="{{ route('admin.cash-transactions.destroy', $cashTransaction) }}" method="POST"
                        onsubmit="return confirm('PERINGATAN: Menghapus transaksi ini akan memicu perhitungan ulang saldo untuk semua transaksi setelah tanggal ini. Apakah Anda yakin?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="text-red-600 hover:text-red-800 font-medium text-sm flex items-center space-x-1">
                            <i class="fas fa-trash-alt"></i>
                            <span>Hapus Transaksi Permanen</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
