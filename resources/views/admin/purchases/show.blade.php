@extends('layouts.admin')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Pembelian')
@section('page-subtitle', 'Informasi lengkap pesanan pembelian (PO)')

@section('content')
    <div class="mx-auto w-full max-w-7xl animate-in fade-in slide-in-from-bottom-2 duration-500">
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-normal text-slate-800 tracking-tight">Detail Pembelian</h1>
                <p
                    class="text-[11px] font-mono text-slate-500 mt-1 uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded w-fit border border-slate-200 shadow-sm">
                    {{ $purchase->purchase_number }}</p>
            </div>
            <div class="flex items-center gap-3 no-print">
                <a href="{{ route('admin.purchases.index') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-slate-700 border border-slate-200 shadow-sm transition-all hover:bg-slate-50 active:scale-95">
                    <i class="fas fa-arrow-left text-[10px]"></i>
                    <span>Kembali</span>
                </a>
                @if($purchase->isDraft())
                    <a href="{{ route('admin.purchases.edit', $purchase) }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-amber-600 border border-amber-200 shadow-sm transition-all hover:bg-amber-50 active:scale-95">
                        <i class="fas fa-edit text-[10px]"></i>
                        <span>Edit PO</span>
                    </a>
                @endif
                <a href="{{ route('admin.purchases.print', $purchase) }}" target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-normal text-emerald-600 border border-emerald-200 shadow-sm transition-all hover:bg-emerald-50 active:scale-95">
                    <i class="fas fa-print text-[10px]"></i>
                    <span>Cetak PO</span>
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div
                class="mb-6 rounded-xl bg-emerald-50 border border-emerald-100 p-4 flex items-center gap-3 text-emerald-600 animate-in slide-in-from-top-2">
                <i class="fas fa-check-circle"></i>
                <p class="text-xs font-normal">{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div
                class="mb-6 rounded-xl bg-rose-50 border border-rose-100 p-4 flex items-center gap-3 text-rose-600 animate-in slide-in-from-top-2">
                <i class="fas fa-exclamation-circle text-[10px]"></i>
                <p class="text-xs font-normal">{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            {{-- Main Info --}}
            <div class="md:col-span-8 space-y-6">
                {{-- Purchase Status Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row justify-between gap-6">
                            <div class="flex-1 space-y-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $statusStyles = [
                                            'draft' => 'bg-amber-50 text-amber-600 ring-amber-200',
                                            'received' => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
                                            'cancelled' => 'bg-rose-50 text-rose-600 ring-rose-200',
                                        ];
                                        $style = $statusStyles[$purchase->status] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                                    @endphp
                                    <span
                                        class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-normal {{ $style }} ring-1 ring-inset uppercase tracking-[0.1em]">
                                        Status: {{ $purchase->status }}
                                    </span>
                                    <span class="text-[10px] font-normal text-slate-400 uppercase tracking-widest">•
                                        {{ $purchase->purchase_date->format('d M Y') }}</span>
                                </div>

                                <div class="grid grid-cols-2 gap-8">
                                    <div class="space-y-1">
                                        <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Outlet
                                            Tujuan</span>
                                        <h3 class="text-[13px] font-normal text-slate-800 leading-tight">
                                            {{ $purchase->outlet->name }}</h3>
                                        <p class="text-[9px] font-mono text-slate-400">#{{ $purchase->outlet->code }}</p>
                                    </div>
                                    <div class="space-y-1">
                                        <span
                                            class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Supplier</span>
                                        <h3 class="text-[13px] font-normal text-slate-800 leading-tight">
                                            {{ $purchase->supplier->name }}</h3>
                                        <p class="text-[9px] font-normal text-slate-400 italic">
                                            {{ $purchase->supplier->phone ?? 'Tidak ada telp' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="md:w-px md:bg-slate-100"></div>

                            <div class="md:w-48 flex flex-col justify-between">
                                <div class="space-y-1">
                                    <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">TOTAL
                                        TRANSAKSI</span>
                                    <div class="flex flex-col">
                                        <span class="text-xl font-normal text-indigo-600 tracking-tight tabular-nums">Rp
                                            {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                                        <span class="text-[9px] font-normal text-slate-400 italic">Netto setelah pajak &
                                            diskon</span>
                                    </div>
                                </div>

                                @if($purchase->status === 'draft')
                                    <div class="mt-6 flex flex-col gap-2">
                                        <form action="{{ route('admin.purchases.receive', $purchase) }}" method="POST"
                                            onsubmit="return confirm('Proses terima barang? Stok produk akan otomatis bertambah.')">
                                            @csrf
                                            <button type="submit"
                                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-normal text-white shadow-sm transition-all hover:bg-emerald-700 active:scale-95">
                                                <i class="fas fa-check-double text-[10px]"></i>
                                                <span>Terima Barang</span>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items Table Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-list-ul text-indigo-500 text-[10px]"></i>
                            <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Daftar
                                Item Barang</h3>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th
                                        class="px-5 py-3 text-left text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Nama Produk / SKU</th>
                                    <th
                                        class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Qty</th>
                                    <th
                                        class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Harga</th>
                                    <th
                                        class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Diskon</th>
                                    <th
                                        class="px-5 py-3 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($purchase->items as $item)
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-5 py-3.5">
                                            <div class="flex flex-col">
                                                <span
                                                    class="text-[11.5px] font-normal text-slate-700 leading-tight">{{ $item->product->name }}</span>
                                                <span
                                                    class="text-[9px] font-mono text-slate-400 mt-1 uppercase tracking-widest">{{ $item->product->sku ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3.5 text-right tabular-nums">
                                            <span
                                                class="text-[11.5px] font-normal text-slate-600">{{ number_format($item->quantity, 2, ',', '.') }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right tabular-nums">
                                            <span class="text-[11.5px] font-normal text-slate-600">Rp
                                                {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right tabular-nums">
                                            <span class="text-[11.5px] font-normal text-rose-500">Rp
                                                {{ number_format($item->discount_amount, 0, ',', '.') }}</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right tabular-nums">
                                            <span class="text-[11.5px] font-normal text-slate-800 tracking-tight">Rp
                                                {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50/50">
                                <tr>
                                    <td colspan="4"
                                        class="px-5 py-2 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                        Subtotal Items</td>
                                    <td class="px-5 py-2 text-right text-[11px] font-normal text-slate-700 tabular-nums">Rp
                                        {{ number_format($purchase->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if($purchase->tax_amount > 0)
                                    <tr>
                                        <td colspan="4"
                                            class="px-5 py-2 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                            Pajak (Tax)</td>
                                        <td class="px-5 py-2 text-right text-[11px] font-normal text-slate-700 tabular-nums">Rp
                                            {{ number_format($purchase->tax_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if($purchase->discount_amount > 0)
                                    <tr>
                                        <td colspan="4"
                                            class="px-5 py-2 text-right text-[9px] font-normal uppercase tracking-widest text-slate-400">
                                            Diskon Global</td>
                                        <td class="px-5 py-2 text-right text-[11px] font-normal text-rose-600 tabular-nums">- Rp
                                            {{ number_format($purchase->discount_amount, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Sidebar Info --}}
            <div class="md:col-span-4 space-y-6">
                {{-- Payment Card (for RECEIVED status) --}}
                @if($purchase->status === 'received')
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-wallet text-indigo-500 text-[10px]"></i>
                                <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Info
                                    Pembayaran</h3>
                            </div>
                            @php
                                $paymentStyles = [
                                    'paid' => 'bg-emerald-50 text-emerald-600 ring-emerald-200',
                                    'partial' => 'bg-amber-50 text-amber-600 ring-amber-200',
                                    'unpaid' => 'bg-slate-50 text-slate-600 ring-slate-200',
                                ];
                                $pStyle = $paymentStyles[$purchase->payment_status] ?? 'bg-slate-50 text-slate-600 ring-slate-200';
                            @endphp
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[8.5px] font-normal {{ $pStyle }} ring-1 ring-inset uppercase tracking-widest leading-tight">
                                {{ $purchase->payment_status }}
                            </span>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">Sisa
                                        Tagihan</span>
                                    <span
                                        class="text-xl font-normal {{ $remaining > 0 ? 'text-rose-600' : 'text-emerald-600' }} tracking-tight tabular-nums">
                                        Rp {{ number_format($remaining, 0, ',', '.') }}
                                    </span>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <div
                                        class="flex justify-between text-[10px] font-normal text-slate-400 uppercase tracking-wider">
                                        <span>Terbayar</span>
                                        <span class="text-emerald-600 tabular-nums">Rp
                                            {{ number_format($totalPaid, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                                        @php $percent = $purchase->total_amount > 0 ? ($totalPaid / $purchase->total_amount) * 100 : 0; @endphp
                                        <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000"
                                            style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>

                                @if($remaining > 0)
                                    <a href="{{ route('admin.purchases.payment', $purchase) }}"
                                        class="mt-4 w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-normal text-white shadow-sm transition-all hover:bg-indigo-700 active:scale-95">
                                        <i class="fas fa-plus-circle text-[10px]"></i>
                                        <span>Catat Pembayaran Baru</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Activity Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-history text-indigo-500 text-[10px]"></i>
                            <h3 class="text-[10px] font-normal text-slate-400 uppercase tracking-widest leading-none">Log
                                Aktivitas</h3>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="h-2 w-2 rounded-full bg-slate-300 ring-4 ring-slate-100 mt-1.5"></div>
                                <div class="flex-1 w-px bg-slate-100 my-1"></div>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-normal text-slate-400 uppercase tracking-widest">PO
                                    Dibuat</span>
                                <span
                                    class="text-[12px] font-normal text-slate-700 mt-1">{{ $purchase->creator->name ?? '-' }}</span>
                                <span
                                    class="text-[10px] font-normal text-slate-400 italic mt-0.5">{{ $purchase->created_at->format('d M Y H:i') }}</span>
                            </div>
                        </div>

                        @if($purchase->status === 'received')
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="h-2 w-2 rounded-full bg-emerald-500 ring-4 ring-emerald-100 mt-1.5"></div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-normal text-emerald-500 uppercase tracking-widest">Barang
                                        Diterima</span>
                                    <span
                                        class="text-[12px] font-normal text-slate-700 mt-1">{{ $purchase->receiver->name ?? '-' }}</span>
                                    <span
                                        class="text-[10px] font-normal text-slate-400 italic mt-0.5">{{ $purchase->received_at ? $purchase->received_at->format('d M Y H:i') : '-' }}</span>
                                </div>
                            </div>
                        @endif

                        @if($purchase->status === 'cancelled')
                            <div class="flex gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="h-2 w-2 rounded-full bg-rose-500 ring-4 ring-rose-100 mt-1.5"></div>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] font-normal text-rose-500 uppercase tracking-widest">Dibatalkan</span>
                                    <span
                                        class="text-[12px] font-normal text-slate-700 mt-1 italic">"{{ session('cancel_reason') ?? 'Dibatalkan oleh sistem/admin' }}"</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Notes Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <span class="text-[9px] font-normal uppercase tracking-widest text-slate-400">Catatan Internal</span>
                    <p class="text-[11.5px] font-normal text-slate-600 italic mt-2 leading-relaxed">
                        {{ $purchase->notes ?? 'Tidak ada catatan tambahan untuk transaksi ini.' }}
                    </p>
                </div>

                {{-- Danger Area --}}
                @if($purchase->isDraft())
                    <div class="pt-4 space-y-3">
                        <button onclick="showCancelModal()"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-normal text-amber-600 border border-amber-200 shadow-sm transition-all hover:bg-amber-50 active:scale-95">
                            <i class="fas fa-times-circle text-[10px]"></i>
                            <span>Batalkan Transaksi (Void)</span>
                        </button>
                        <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST"
                            onsubmit="return confirm('Hapus data PO ini secara permanen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-normal text-rose-600 border border-rose-200 shadow-sm transition-all hover:bg-rose-50 active:scale-95">
                                <i class="fas fa-trash-alt text-[10px]"></i>
                                <span>Hapus Permanen</span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Cancel --}}
    <div id="cancelModal"
        class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 animate-in fade-in duration-300">
        <div
            class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden transform animate-in zoom-in-95 duration-300">
            <div class="p-8">
                <div class="h-12 w-12 rounded-2xl bg-amber-50 flex items-center justify-center mb-6">
                    <i class="fas fa-exclamation-triangle text-amber-500"></i>
                </div>
                <h3 class="text-lg font-normal text-slate-800 tracking-tight mb-2">Batalkan Pembelian?</h3>
                <p class="text-xs font-normal text-slate-500 leading-relaxed mb-6">Informasi pesanan akan diarsipkan dan
                    tidak dapat dilanjutkan kembali menjadi transaksi stok masuk.</p>

                <form action="{{ route('admin.purchases.cancel', $purchase) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-normal text-slate-500 uppercase tracking-wider ml-1">Alasan
                            Pembatalan</label>
                        <textarea name="reason" required rows="2" placeholder="Contoh: Kesalahan input item..."
                            class="w-full px-4 py-3 text-xs font-normal bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-indigo-500 transition-all"></textarea>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" onclick="hideCancelModal()"
                            class="flex-1 px-4 py-3 rounded-2xl bg-slate-100 text-slate-600 text-[11px] font-normal transition-all hover:bg-slate-200 active:scale-95">TIDAK</button>
                        <button type="submit"
                            class="flex-1 px-4 py-3 rounded-2xl bg-slate-900 text-white text-[11px] font-normal transition-all hover:bg-slate-800 active:scale-95 shadow-lg">YA,
                            BATALKAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showCancelModal() { document.getElementById('cancelModal').classList.remove('hidden'); }
        function hideCancelModal() { document.getElementById('cancelModal').classList.add('hidden'); }
        window.onclick = function (event) {
            if (event.target == document.getElementById('cancelModal')) hideCancelModal();
        }
    </script>
@endsection