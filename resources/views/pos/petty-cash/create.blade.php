@extends('layouts.pos_theme')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-surface-light rounded-2xl shadow-soft overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Input Petty Cash Outlet</h2>
                <p class="text-sm text-gray-500 mt-0.5">Kas kecil terpisah dari kas sales POS</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pos.petty-cash.index') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 font-medium transition">
                    <span class="material-icons-round text-base">list_alt</span>
                    Riwayat
                </a>
                <a href="{{ route('pos.dashboard') }}"
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm text-gray-700 font-medium transition">
                    <span class="material-icons-round text-base">arrow_back</span>
                    Dashboard
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

            @if($errors->any())
                <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Akun Kas Otomatis</p>
                    @if($pettyCashAccount)
                        <p class="text-sm font-semibold text-gray-900">{{ $pettyCashAccount->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $pettyCashAccount->code }}</p>
                        <p class="text-xs text-amber-700 mt-2 font-medium">
                            Saldo: Rp {{ number_format($pettyCashAccount->current_balance, 0, ',', '.') }}
                        </p>
                    @else
                        <p class="text-sm font-semibold text-rose-700">Belum disetting</p>
                        <p class="text-xs text-rose-600 mt-1">Admin harus set akun kas dengan tipe penggunaan "Petty Cash Outlet".</p>
                    @endif
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">Akun Expense Otomatis</p>
                    @if($defaultExpenseAccount)
                        <p class="text-sm font-semibold text-gray-900">{{ $defaultExpenseAccount->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $defaultExpenseAccount->code }}</p>
                    @else
                        <p class="text-sm font-semibold text-rose-700">Belum disetting</p>
                        <p class="text-xs text-rose-600 mt-1">Akun "Keperluan Outlet Lainnya" belum tersedia.</p>
                    @endif
                </div>
            </div>

            <form action="{{ route('pos.petty-cash.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Transaksi <span class="text-rose-600">*</span>
                    </label>
                    <input type="date"
                           name="transaction_date"
                           id="transaction_date"
                           value="{{ old('transaction_date', now()->format('Y-m-d')) }}"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <div>
                    <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Penerima <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           name="recipient_name"
                           id="recipient_name"
                           value="{{ old('recipient_name') }}"
                           maxlength="60"
                           placeholder="Contoh: Budi / Toko Sumber Rejeki"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi <span class="text-rose-600">*</span>
                    </label>
                    <input type="text"
                           name="description"
                           id="description"
                           value="{{ old('description') }}"
                           maxlength="150"
                           placeholder="Contoh: Pembelian tisu outlet"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah (Rp) <span class="text-rose-600">*</span>
                    </label>
                    <input type="number"
                           name="amount"
                           id="amount"
                           value="{{ old('amount') }}"
                           min="0.01"
                           step="0.01"
                           placeholder="0"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                </div>

                <div class="pt-2 flex items-center justify-end gap-3">
                    <a href="{{ route('pos.petty-cash.index') }}"
                       class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium">
                        Batal
                    </a>
                    <button type="submit"
                            {{ (!$pettyCashAccount || !$defaultExpenseAccount) ? 'disabled' : '' }}
                            class="px-4 py-2 rounded-lg bg-primary hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold">
                        Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
