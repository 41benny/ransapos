@extends('layouts.admin')

@section('title', 'Detail Transfer Stok')
@section('page-title', 'Detail Transfer Stok')
@section('page-subtitle', 'Informasi lengkap dan status pengiriman barang')

@section('content')
<div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Detail Transfer</h1>
            <p class="text-[11px] font-mono text-slate-500 mt-1 uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded w-fit inline-block border border-slate-200">{{ $stockTransfer->transfer_number }}</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="{{ route('admin.stock-transfers.print', $stockTransfer->id) }}" target="_blank"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">
                <i class="fas fa-print text-[10px]"></i>
                <span>Cetak Pengiriman</span>
            </a>
            <a href="{{ route('admin.stock-transfers.index') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    {{-- Main Activity Card (Status & Global Actions) --}}
    <div class="bg-indigo-900 rounded-2xl shadow-lg border border-indigo-800 p-6 mb-8 text-white relative overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 -mr-20 -mt-20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-400/10 -ml-10 -mb-10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-white/10 flex items-center justify-center text-white text-2xl border border-white/20">
                    @if($stockTransfer->status == 'pending') <i class="fas fa-clock"></i>
                    @elseif($stockTransfer->status == 'in_transit') <i class="fas fa-truck-loading"></i>
                    @elseif($stockTransfer->status == 'received') <i class="fas fa-check-double"></i>
                    @elseif($stockTransfer->status == 'cancelled') <i class="fas fa-ban"></i>
                    @endif
                </div>
                <div>
                    <h3 class="text-[10px] font-normal uppercase tracking-[0.3em] text-indigo-200 mb-1">Status Transfer Saat Ini</h3>
                    <div class="flex items-center gap-2">
                        @php
                            $statusLabels = [
                                'pending' => 'Menunggu Pengiriman',
                                'in_transit' => 'Dalam Perjalanan',
                                'received' => 'Sudah Diterima',
                                'cancelled' => 'Dibatalkan',
                            ];
                        @endphp
                        <span class="text-xl font-normal tracking-tight">{{ $statusLabels[$stockTransfer->status] ?? strtoupper($stockTransfer->status) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                @if($stockTransfer->canBeSent())
                    <form method="POST" action="{{ route('admin.stock-transfers.send', $stockTransfer->id) }}"
                          onsubmit="return confirm('Kirim transfer ini? Stok akan segera dikurangi dari outlet pengirim.')" class="w-full md:w-auto">
                        @csrf
                        <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-2.5 text-xs font-normal text-indigo-900 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                            <i class="fas fa-paper-plane text-[10px]"></i>
                            <span>KIRIM SEKARANG</span>
                        </button>
                    </form>
                @endif

                @if($stockTransfer->canBeReceived())
                    <a href="{{ route('admin.stock-transfers.receive-form', $stockTransfer->id) }}"
                       class="w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-500 px-6 py-2.5 text-xs font-normal text-white shadow-sm transition-all hover:bg-emerald-600 active:scale-95">
                        <i class="fas fa-box-open text-[10px]"></i>
                        <span>TERIMA BARANG</span>
                    </a>
                @endif

                @if($stockTransfer->canBeCancelled())
                    <button type="button" onclick="showCancelModal()"
                            class="w-full md:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-rose-500/20 px-6 py-2.5 text-xs font-normal text-white border border-rose-500/30 shadow-sm transition-all hover:bg-rose-500/30 active:scale-95">
                        <i class="fas fa-times-circle text-[10px]"></i>
                        <span>BATALKAN</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Info Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        {{-- General Info --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-info-circle text-indigo-500 text-[10px]"></i>
                        <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Informasi Utama</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Asal Pengiriman</span>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-slate-400"></div>
                                    <span class="text-[13px] font-normal text-slate-800">{{ $stockTransfer->fromOutlet->name }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Tujuan Penerimaan</span>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                                    <span class="text-[13px] font-normal text-slate-800">{{ $stockTransfer->toOutlet->name }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Tanggal Transfer</span>
                                <span class="text-[13px] font-normal text-slate-800">{{ $stockTransfer->transfer_date->format('d F Y') }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Nilai Transfer (HPP)</span>
                                <span class="text-[13px] font-semibold text-slate-800">
                                    Rp {{ number_format((float) ($transferNominalTotal ?? 0), 0, ',', '.') }}
                                </span>
                                <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">
                                    Sumber: {{ ($valuationSource ?? 'actual') === 'estimated' ? 'Estimasi HPP' : (($valuationSource ?? 'actual') === 'mixed' ? 'Campuran (Aktual + Estimasi)' : 'Aktual') }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Catatan Internal</span>
                                <p class="text-[11.5px] font-normal text-slate-500 italic leading-relaxed">{{ $stockTransfer->notes ?? 'Tidak ada catatan.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-box text-indigo-500 text-[10px]"></i>
                        <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Produk Terlampir</h3>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">Item Produk</th>
                                <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">Qty Kirim</th>
                                @if($stockTransfer->isReceived())
                                    <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">Qty Terima</th>
                                    <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">Selisih</th>
                                @endif
                                <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">HPP/Unit</th>
                                <th class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">Nominal HPP</th>
                                <th class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($stockTransfer->items as $item)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    @php
                                        $hppData = $itemHppMap[$item->product_id] ?? null;
                                        $unitHpp = (float) ($hppData['unit_hpp'] ?? 0);
                                        $nominalHpp = $unitHpp > 0 ? ((float) $item->quantity * $unitHpp) : null;
                                    @endphp
                                    <td class="px-5 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="text-[11.5px] font-normal text-slate-700 leading-tight">{{ $item->product->name }}</span>
                                            <span class="text-[9px] font-mono text-slate-400 mt-1 uppercase tracking-tighter bg-slate-100 px-1 rounded w-fit">{{ $item->product->sku ?? 'NO-SKU' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <span class="text-[11.5px] font-normal text-slate-800 tracking-tight tabular-nums">{{ number_format($item->quantity, 2, ',', '.') }}</span>
                                        <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-0.5">{{ $item->product->unit ?? 'pcs' }}</span>
                                    </td>
                                    @if($stockTransfer->isReceived())
                                        <td class="px-5 py-3.5 text-right">
                                            <span class="text-[11.5px] font-normal text-slate-800 tracking-tight tabular-nums">{{ number_format($item->received_quantity, 2, ',', '.') }}</span>
                                            <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest ml-0.5">{{ $item->product->unit ?? 'pcs' }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            @php
                                                $diff = $item->received_quantity - $item->quantity;
                                            @endphp
                                            @if($diff == 0)
                                                <span class="text-[11px] font-normal text-slate-400">-</span>
                                            @else
                                                <span class="text-[11.5px] font-normal tabular-nums {{ $diff < 0 ? 'text-rose-500' : 'text-emerald-500' }}">
                                                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-5 py-3.5 text-right">
                                        @if($unitHpp > 0)
                                            <span class="text-[11px] font-normal text-slate-700">
                                                Rp {{ number_format($unitHpp, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-normal text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        @if(!is_null($nominalHpp))
                                            <span class="text-[11px] font-semibold text-slate-800">
                                                Rp {{ number_format($nominalHpp, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-normal text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-[10px] font-normal text-slate-400 italic">{{ $item->notes ?? '-' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sidebar Info (Timeline & Author) --}}
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-stream text-indigo-500 text-[10px]"></i>
                        <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Jehjak Aktivitas</h3>
                    </div>
                </div>
                <div class="p-6 relative">
                    {{-- Timeline Line --}}
                    <div class="absolute left-8 top-8 bottom-8 w-[1px] bg-slate-100"></div>

                    <div class="space-y-8 relative z-10 pl-2">
                        {{-- Created --}}
                        <div class="flex items-start gap-4">
                            <div class="h-5 w-5 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[8px] text-slate-400 shadow-sm shrink-0">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div>
                                <h4 class="text-[11px] font-normal text-slate-700 leading-none">Draft Dibuat</h4>
                                <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest">{{ $stockTransfer->created_at->format('d/m/Y H:i') }} WIB</p>
                                <p class="text-[9px] text-indigo-500 mt-1 uppercase tracking-wider font-normal">OLEH: {{ $stockTransfer->creator->name ?? '-' }}</p>
                            </div>
                        </div>

                        {{-- Sent --}}
                        @if($stockTransfer->sent_at)
                            <div class="flex items-start gap-4">
                                <div class="h-5 w-5 rounded-full bg-blue-50 border-2 border-white flex items-center justify-center text-[8px] text-blue-500 shadow-sm shrink-0 ring-2 ring-blue-100">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div>
                                    <h4 class="text-[11px] font-normal text-slate-700 leading-none">Baris Dikirim</h4>
                                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest">{{ $stockTransfer->sent_at->format('d/m/Y H:i') }} WIB</p>
                                    <p class="text-[9px] text-blue-500 mt-1 uppercase tracking-wider font-normal">OLEH: {{ $stockTransfer->sender->name ?? '-' }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Received --}}
                        @if($stockTransfer->received_at)
                            <div class="flex items-start gap-4">
                                <div class="h-5 w-5 rounded-full bg-emerald-50 border-2 border-white flex items-center justify-center text-[8px] text-emerald-500 shadow-sm shrink-0 ring-2 ring-emerald-100">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div>
                                    <h4 class="text-[11px] font-normal text-slate-700 leading-none">Baris Diterima</h4>
                                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest">{{ $stockTransfer->received_at->format('d/m/Y H:i') }} WIB</p>
                                    <p class="text-[9px] text-emerald-500 mt-1 uppercase tracking-wider font-normal">OLEH: {{ $stockTransfer->receiver->name ?? '-' }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- Cancelled --}}
                        @if($stockTransfer->cancelled_at)
                            <div class="flex items-start gap-4">
                                <div class="h-5 w-5 rounded-full bg-rose-50 border-2 border-white flex items-center justify-center text-[8px] text-rose-500 shadow-sm shrink-0 ring-2 ring-rose-100">
                                    <i class="fas fa-ban"></i>
                                </div>
                                <div>
                                    <h4 class="text-[11px] font-normal text-rose-600 leading-none">Dibatalkan</h4>
                                    <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-widest">{{ $stockTransfer->cancelled_at->format('d/m/Y H:i') }} WIB</p>
                                    <p class="text-[9px] text-rose-500 mt-1 uppercase tracking-wider font-normal">OLEH: {{ $stockTransfer->canceller->name ?? '-' }}</p>
                                    @if($stockTransfer->cancel_reason)
                                        <div class="mt-2 text-[9px] font-normal text-rose-400 bg-rose-50 p-2 rounded border border-rose-100 italic">
                                            "{{ $stockTransfer->cancel_reason }}"
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div id="cancelModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4 animate-in fade-in duration-300">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full animate-in zoom-in-95 duration-200 border border-slate-200">
        <div class="flex items-center gap-3 mb-4">
            <div class="h-10 w-10 rounded-xl bg-rose-100 text-rose-500 flex items-center justify-center text-lg shadow-inner">
                <i class="fas fa-times-circle"></i>
            </div>
            <div>
                <h3 class="text-base font-normal text-slate-800">Batalkan Transfer</h3>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest">Konfirmasi Pembatalan</p>
            </div>
        </div>
        
        <form method="POST" action="{{ route('admin.stock-transfers.cancel', $stockTransfer->id) }}">
            @csrf
            <div class="mb-6">
                <label class="block text-[10px] font-normal text-slate-500 uppercase tracking-wider mb-2 ml-1">Alasan Pembatalan <span class="text-rose-500">*</span></label>
                <textarea name="cancel_reason" rows="3" required
                          class="w-full px-4 py-3 text-[11.5px] font-normal bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm"
                          placeholder="Mohon sebutkan alasan pembatalan..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="hideCancelModal()" class="flex-1 px-4 py-2.5 text-xs font-normal text-slate-400 hover:text-slate-600 transition-colors uppercase tracking-widest border border-slate-100 rounded-xl hover:bg-slate-50">
                    KEMBALI
                </button>
                <button type="submit" class="flex-1 bg-rose-600 text-white px-4 py-2.5 rounded-xl hover:bg-rose-700 shadow-lg shadow-rose-200 transition-all font-normal text-xs active:scale-95 uppercase tracking-widest">
                    YA, BATALKAN
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function hideCancelModal() {
    const modal = document.getElementById('cancelModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Close on click outside
window.onclick = function(event) {
    const modal = document.getElementById('cancelModal');
    if (event.target == modal) {
        hideCancelModal();
    }
}
</script>
@endpush
