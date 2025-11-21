@extends('layouts.pos')

@section('title', 'Kitchen Display')
@section('page-title', 'Dapur / Kitchen Display')

@section('content')
<div class="h-full bg-slate-900 text-slate-50 overflow-y-auto">
    <div class="max-w-6xl mx-auto px-4 py-4 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Pesanan Hari Ini</h1>
                <p class="text-sm text-slate-400">Tampilan ringkas untuk dapur. Refresh berkala untuk update pesanan.</p>
            </div>
            <div class="flex items-center gap-3">
                <form method="GET" class="flex items-center gap-2">
                    <input type="date"
                           name="date"
                           value="{{ $date }}"
                           class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                    <button type="submit"
                            class="px-3 py-2 rounded-lg text-xs font-semibold bg-amber-500 text-slate-900 hover:bg-amber-400">
                        Ganti Tanggal
                    </button>
                </form>
                <button type="button"
                        id="manual-refresh"
                        class="hidden md:inline-flex items-center gap-1 px-3 py-2 rounded-lg text-xs font-semibold border border-slate-600 text-slate-200 hover:bg-slate-800">
                    <span>Refresh</span>
                </button>
                <label class="inline-flex items-center gap-2 text-xs text-slate-300">
                    <input type="checkbox" id="auto-refresh" class="rounded border-slate-500 bg-slate-800">
                    <span>Auto refresh 10 detik</span>
                </label>
            </div>
        </div>

        @if($sales->isEmpty())
            <div class="mt-8 flex flex-col items-center justify-center py-16 border-2 border-dashed border-slate-700 rounded-2xl">
                <svg class="w-16 h-16 text-slate-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18M9 3v18m6-18v18M6 8h.01M6 12h.01M6 16h.01" />
                </svg>
                <p class="text-slate-400">Belum ada pesanan untuk tanggal ini.</p>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($sales as $sale)
                    <div class="rounded-2xl border border-slate-700 bg-slate-800/80 p-4 shadow-lg">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-amber-400 font-semibold">Order</p>
                                <p class="text-sm font-semibold text-slate-50">{{ $sale->invoice_number }}</p>
                            </div>
                            <div class="text-right space-y-1">
                                <div class="text-xs text-slate-400">
                                    <p>{{ $sale->created_at?->format('H:i') ?? '-' }}</p>
                                    @if($sale->customer_name)
                                        <p class="mt-1 text-amber-300 font-medium">{{ $sale->customer_name }}</p>
                                    @endif
                                </div>
                                <div>
                                    @php
                                        $kitchenStatus = $sale->kitchen_status ?? 'new';
                                    @endphp
                                    @if($kitchenStatus === 'new')
                                        <span class="inline-flex items-center rounded-full bg-amber-500/20 text-amber-300 text-[11px] font-semibold px-2 py-1">
                                            Baru
                                        </span>
                                    @elseif($kitchenStatus === 'in_progress')
                                        <span class="inline-flex items-center rounded-full bg-sky-500/20 text-sky-300 text-[11px] font-semibold px-2 py-1">
                                            Diproses
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-semibold px-2 py-1">
                                            Selesai
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($sale->notes)
                            <div class="mb-3 rounded-lg bg-slate-900/60 border border-amber-500/60 px-3 py-2 text-xs text-amber-100">
                                <span class="font-semibold">Catatan Order:</span>
                                <span>{{ $sale->notes }}</span>
                            </div>
                        @endif

                        <div class="space-y-2 mb-3">
                            @foreach($sale->items as $item)
                                <div class="rounded-lg bg-slate-900/70 px-3 py-2">
                                    <div class="flex justify-between items-baseline gap-3">
                                        <div class="flex items-baseline gap-2">
                                            <span class="inline-flex items-center justify-center rounded-md bg-amber-500/20 px-2 py-1 text-xs font-semibold text-amber-300 min-w-[2.5rem] text-center">
                                                x{{ rtrim(rtrim(number_format($item->quantity, 2, ',', '.'), '0'), ',') }}
                                            </span>
                                            <p class="text-sm font-medium text-slate-50">{{ $item->product_name }}</p>
                                        </div>
                                    </div>
                                    @if(!empty($item->notes))
                                        <p class="mt-1 text-xs text-amber-300">• {{ $item->notes }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="flex justify-between items-center gap-2 mt-1">
                            <a href="{{ route('pos.kitchen.print', $sale) }}"
                               target="_blank"
                               class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-slate-700 text-slate-100 hover:bg-slate-600">
                                Print
                            </a>
                            <div class="flex justify-end gap-2">
                            @if(($sale->kitchen_status ?? 'new') === 'new')
                                <form method="POST" action="{{ route('pos.kitchen.update-status', $sale) }}">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="in_progress">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-sky-500 text-slate-900 hover:bg-sky-400">
                                        Mulai
                                    </button>
                                </form>
                            @elseif(($sale->kitchen_status ?? 'new') === 'in_progress')
                                <form method="POST" action="{{ route('pos.kitchen.update-status', $sale) }}">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="done">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-emerald-500 text-slate-900 hover:bg-emerald-400">
                                        Selesai
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('pos.kitchen.update-status', $sale) }}">
                                    @csrf
                                    <input type="hidden" name="kitchen_status" value="in_progress">
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-slate-700 text-slate-100 hover:bg-slate-600">
                                        Kembalikan ke Proses
                                    </button>
                                </form>
                            @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    const autoCheckbox = document.getElementById('auto-refresh');
    const manualRefresh = document.getElementById('manual-refresh');
    let timer = null;

    if (manualRefresh) {
        manualRefresh.addEventListener('click', function () {
            window.location.reload();
        });
    }

    if (autoCheckbox) {
        autoCheckbox.addEventListener('change', function () {
            if (this.checked) {
                timer = setInterval(function () {
                    window.location.reload();
                }, 10000);
            } else if (timer) {
                clearInterval(timer);
                timer = null;
            }
        });
    }
});
</script>
