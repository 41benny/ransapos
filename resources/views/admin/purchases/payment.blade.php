@extends('layouts.admin')

@section('title', 'Pembayaran Purchase - ' . $purchase->purchase_number)
@section('page-title', 'Catat Pembayaran')
@section('page-subtitle', 'Proses pelunasan tagihan pembelian ke supplier')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('admin.purchases.index') }}"
                        class="text-[10px] font-normal text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">Pembelian</a>
                    <i class="fas fa-chevron-right text-[8px] text-slate-300"></i>
                    <a href="{{ route('admin.purchases.show', $purchase) }}"
                        class="text-[10px] font-normal text-slate-400 hover:text-indigo-600 uppercase tracking-widest transition-colors">{{ $purchase->purchase_number }}</a>
                </div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Catat Pembayaran</h1>
                <p class="text-xs font-normal text-slate-500 mt-0.5">Lakukan pembayaran untuk menyisihkan kewajiban hutang
                    supplier</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.purchases.show', $purchase) }}"
                    class="ui-btn ui-btn-ghost inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        @if(session('error'))
            <div
                class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-3 text-rose-600 animate-in slide-in-from-top-2">
                <i class="fas fa-exclamation-circle"></i>
                <p class="text-xs font-normal">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            {{-- Side info: Stats & History --}}
            <div class="md:col-span-4 space-y-6">
                {{-- Summary Stats --}}
                <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 space-y-6">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">Sisa
                                Tagihan</span>
                            <span class="text-2xl font-normal text-rose-600 tracking-tight tabular-nums">Rp
                                {{ number_format($remaining, 0, ',', '.') }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                            <div class="flex flex-col gap-0.5">
                                <span
                                    class="text-[9px] font-normal text-slate-400 uppercase tracking-widest leading-none">Total
                                    Tagihan</span>
                                <span class="text-[12px] font-normal text-slate-700 tabular-nums">Rp
                                    {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span
                                    class="text-[9px] font-normal text-slate-400 uppercase tracking-widest leading-none">Sudah
                                    Dibayar</span>
                                <span class="text-[12px] font-normal text-emerald-600 tabular-nums">Rp
                                    {{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- History List --}}
                @if($purchase->cashTransactions && $purchase->cashTransactions->count() > 0)
                    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-history text-indigo-500 text-[10px]"></i>
                                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">
                                    Riwayat Pembayaran</h3>
                            </div>
                        </div>
                        <div class="divide-y divide-slate-50">
                            @foreach($purchase->cashTransactions as $payment)
                                <div class="p-4 hover:bg-slate-50/50 transition-colors">
                                    <div class="flex justify-between items-start mb-1">
                                        <span
                                            class="text-[11.5px] font-normal text-slate-700">{{ $payment->transaction_number }}</span>
                                        <span class="text-[11.5px] font-normal text-slate-800 tabular-nums">Rp
                                            {{ number_format($payment->amount, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                        <span class="font-normal">{{ $payment->transaction_date->format('d M Y') }}</span>
                                        <span>•</span>
                                        <span class="font-normal">{{ $payment->cashAccount->name }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

                {{-- Form Area --}}
            <div class="md:col-span-8">
                @if($remaining > 0)
                    <div class="ui-card bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-plus-circle text-indigo-500 text-[10px]"></i>
                                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Entri
                                    Pembayaran Baru</h3>
                            </div>
                        </div>

                        <form action="{{ route('admin.purchases.payment.store', $purchase) }}" method="POST" id="paymentForm">
                            @csrf
                            <div class="p-8 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {{-- Account --}}
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Bayar
                                            Dari Akun <span class="text-rose-500">*</span></label>
                                        <select name="cash_account_id" required
                                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                                            <option value="">-- Pilih Akun Kas/Bank --</option>
                                            @foreach($cashAccounts as $account)
                                                <option value="{{ $account->id }}" {{ old('cash_account_id') == $account->id ? 'selected' : '' }}>
                                                    {{ $account->name }} (Rp
                                                    {{ number_format($account->current_balance, 0, ',', '.') }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('cash_account_id') <p class="text-[9px] text-rose-500 mt-1 font-normal ml-1">
                                        {{ $message }}</p> @enderror
                                    </div>

                                    {{-- Date --}}
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Tanggal
                                            Bayar <span class="text-rose-500">*</span></label>
                                        <input type="date" name="transaction_date"
                                            value="{{ old('transaction_date', date('Y-m-d')) }}" required
                                            class="ui-input w-full px-4 py-2.5 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">
                                    </div>
                                </div>

                                {{-- Amount --}}
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Jumlah
                                        Pembayaran <span class="text-rose-500">*</span></label>
                                    <div class="relative group">
                                        <span
                                            class="absolute left-4 top-1/2 -translate-y-1/2 text-[11.5px] font-normal text-slate-400 group-focus-within:text-indigo-500 transition-colors">Rp</span>
                                        <input type="number" name="amount" value="{{ old('amount', $remaining) }}" step="0.01"
                                            min="0.01" max="{{ $remaining }}" required
                                            class="ui-input w-full pl-10 pr-4 py-3 text-[14px] font-normal bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all tabular-nums">
                                    </div>
                                    <p class="text-[9px] text-slate-400 italic font-normal ml-1">Nilai maksimal: Rp
                                        {{ number_format($remaining, 0, ',', '.') }}</p>
                                    @error('amount') <p class="text-[9px] text-rose-500 mt-1 font-normal ml-1">{{ $message }}
                                    </p> @enderror
                                </div>

                                {{-- Notes --}}
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Catatan
                                        Tambahan</label>
                                    <textarea name="notes" rows="3" placeholder="Contoh: Pembayaran cicilan ke-2..."
                                        class="ui-input w-full px-4 py-3 text-xs font-normal bg-white border border-slate-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all shadow-sm">{{ old('notes') }}</textarea>
                                </div>
                            </div>

                            <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end gap-3">
                                <a href="{{ route('admin.purchases.show', $purchase) }}"
                                    class="ui-btn ui-btn-ghost text-[11px] font-normal text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest mr-2">Batalkan</a>
                                <button type="submit"
                                    class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-slate-900 px-8 py-3 text-xs font-normal text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95 uppercase tracking-widest">
                                    <i class="fas fa-check-circle text-[10px]"></i>
                                    <span>Simpan Pembayaran</span>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div
                        class="bg-emerald-50 border border-emerald-100 rounded-3xl p-10 flex flex-col items-center text-center">
                        <div class="h-16 w-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mb-6">
                            <i class="fas fa-check-double text-emerald-500 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-normal text-slate-800 tracking-tight mb-2">Tagihan Sudah Lunas</h3>
                        <p class="text-xs font-normal text-slate-500 max-w-sm leading-relaxed mb-8">Pembelian ini telah dibayar
                            penuh. Tidak ada sisa tagihan yang perlu diselesaikan.</p>
                        <a href="{{ route('admin.purchases.show', $purchase) }}"
                            class="ui-btn ui-btn-primary inline-flex items-center gap-2 rounded-xl bg-slate-900 px-8 py-3 text-xs font-normal text-white shadow-lg transition-all hover:bg-slate-800 active:scale-95 uppercase tracking-widest">
                            <span>Kembali ke Detail</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
