@extends('layouts.admin')

@section('title', 'Metode Pembayaran')
@section('page-title', 'Metode Pembayaran')
@section('page-subtitle', 'Kelola master metode pembayaran untuk POS dan laporan')

@section('content')
    <div class="w-full">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 page-card-fill">

            @if(session('success'))
                <div class="p-6">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle text-lg"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="p-6">
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle text-lg"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Daftar Metode Pembayaran</h3>
                        <p class="text-sm text-gray-500 mt-1">Total: {{ $paymentMethods->total() }} metode</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <form method="GET" action="{{ route('admin.payment-methods.index') }}" class="flex items-center gap-2">
                            <input
                                type="text"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Cari kode atau nama..."
                                class="w-56 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                            <button
                                type="submit"
                                class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm"
                            >
                                Cari
                            </button>
                            @if($search !== '')
                                <a
                                    href="{{ route('admin.payment-methods.index') }}"
                                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm"
                                >
                                    Reset
                                </a>
                            @endif
                        </form>

                        @if(auth()->user()?->hasPermission('payment-methods.create'))
                            <a
                                href="{{ route('admin.payment-methods.create') }}"
                                class="btn btn-primary inline-flex items-center justify-center"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Tambah Metode
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="imperial-table w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Dipakai Transaksi</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($paymentMethods as $method)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono font-semibold text-gray-900">{{ $method->code }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $method->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($method->is_active)
                                        <span class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">Aktif</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ number_format($method->payments_count) }} transaksi
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if(auth()->user()?->hasPermission('payment-methods.update'))
                                            <a
                                                href="{{ route('admin.payment-methods.edit', $method) }}"
                                                class="px-3 py-1.5 text-xs font-medium bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition"
                                            >
                                                Edit
                                            </a>
                                        @endif

                                        @if(auth()->user()?->hasPermission('payment-methods.delete'))
                                            <form
                                                action="{{ route('admin.payment-methods.destroy', $method) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus metode pembayaran ini?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="px-3 py-1.5 text-xs font-medium bg-rose-50 text-rose-700 rounded-lg hover:bg-rose-100 transition"
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a5 5 0 00-10 0v2m-2 0h14a2 2 0 012 2v7a2 2 0 01-2 2H5a2 2 0 01-2-2v-7a2 2 0 012-2z" />
                                        </svg>
                                        <p class="text-gray-500">Belum ada metode pembayaran.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paymentMethods->hasPages())
                <div class="p-6 border-t border-gray-100">
                    {{ $paymentMethods->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
