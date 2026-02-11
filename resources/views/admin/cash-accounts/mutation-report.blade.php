@extends('layouts.admin')

@section('title', 'Laporan Mutasi - ' . $account->name)

@section('content')
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                <a href="{{ route('admin.cash-accounts.index') }}" class="hover:text-indigo-600">Kas & Bank</a>
                <span>/</span>
                <a href="{{ route('admin.cash-accounts.show', $account) }}"
                    class="hover:text-indigo-600">{{ $account->code }}</a>
                <span>/</span>
                <span class="text-gray-900">Laporan Mutasi</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi Kas/Bank</h1>
            <p class="text-gray-600 mt-1">{{ $account->name }} ({{ $account->code }})</p>
        </div>

        <!-- Filter -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <form method="GET">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ $date_from }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ $date_to }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="flex items-end">
                        <button type="submit"
                            class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                            Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Summary Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Saldo Awal</p>
                    <p class="text-xl font-bold text-gray-900">Rp {{ number_format($beginning_balance, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Masuk</p>
                    <p class="text-xl font-bold text-green-600">+ Rp {{ number_format($total_in, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Keluar</p>
                    <p class="text-xl font-bold text-red-600">- Rp {{ number_format($total_out, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Saldo Akhir</p>
                    <p class="text-2xl font-bold text-indigo-600">Rp {{ number_format($ending_balance, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Transactions -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Rincian Mutasi</h2>
                <button onclick="window.print()"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print</span>
                </button>
            </div>

            @if($transactions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nomor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo Awal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Masuk</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Keluar</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo Akhir</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($transactions as $transaction)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->transaction_date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $transaction->transaction_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>{{ $transaction->description }}</div>
                                        @if($transaction->reference_type)
                                            <div class="text-xs text-gray-500 mt-1">Ref: {{ ucfirst($transaction->reference_type) }}
                                                #{{ $transaction->reference_id }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                        {{ number_format($transaction->balance_before, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">
                                        {{ $transaction->type === 'in' ? number_format($transaction->amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">
                                        {{ $transaction->type === 'out' ? number_format($transaction->amount, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                                        {{ number_format($transaction->balance_after, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm text-gray-900">Total:</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-green-600">
                                    {{ number_format($total_in, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-red-600">
                                    {{ number_format($total_out, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-indigo-600">
                                    {{ number_format($ending_balance, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada transaksi</h3>
                    <p class="mt-1 text-sm text-gray-500">Tidak ada transaksi dalam periode yang dipilih.</p>
                </div>
            @endif>
        </div>
    </div>

    @section('styles')
        <style>
            @media print {
                @page {
                    size: A4;
                    margin: 1cm;
                }

                .no-print,
                header,
                aside,
                .sidebar,
                .navbar,
                button,
                .footer,
                .imperial-sidebar {
                    display: none !important;
                }

                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    background-color: white !important;
                    color: black !important;
                    margin: 0;
                    padding: 0;
                }

                .container {
                    max-width: 100% !important;
                    width: 100% !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .bg-white,
                .page-card-fill,
                .t6-card {
                    box-shadow: none !important;
                    border: none !important;
                    background-color: white !important;
                }

                table {
                    width: 100% !important;
                    border-collapse: collapse !important;
                    font-size: 11px;
                }

                th,
                td {
                    border: 1px solid #ddd !important;
                    padding: 6px 8px !important;
                }

                th {
                    background-color: #f3f4f6 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                /* Summary Card Print Layout */
                .grid {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 1rem;
                    border: 1px solid #ddd;
                    padding: 1rem;
                    margin-bottom: 1rem;
                }

                .grid>div {
                    flex: 1;
                    border-right: 1px solid #eee;
                    padding-right: 1rem;
                }

                .grid>div:last-child {
                    border-right: none;
                }

                h1 {
                    font-size: 18px;
                    margin-bottom: 2px;
                }

                .text-2xl {
                    font-size: 14px !important;
                }

                .text-xl {
                    font-size: 14px !important;
                }
            }
        </style>
    @endsection
@endsection