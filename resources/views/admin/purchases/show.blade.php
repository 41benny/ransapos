@extends('layouts.admin')

@section('title', 'Detail Pembelian')
@section('page-title', 'Detail Pembelian')
@section('page-subtitle', 'Informasi lengkap pembelian')

@section('content')

<!-- Alert -->
@if(session('success'))
<div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('error') }}
</div>
@endif

<div class="max-w-6xl space-y-6">
    <!-- Header Info -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ $purchase->purchase_number }}</h3>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ $purchase->purchase_date->format('d M Y') }}</p>
            </div>
            <div>
                @if($purchase->status === 'draft')
                    <span class="px-4 py-2 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">Draft</span>
                @elseif($purchase->status === 'received')
                    <span class="px-4 py-2 text-sm font-medium bg-green-100 text-green-800 rounded-full">Diterima</span>
                @else
                    <span class="px-4 py-2 text-sm font-medium bg-red-100 text-red-800 rounded-full">Dibatalkan</span>
                @endif
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Outlet Tujuan</p>
                    <p class="text-sm font-medium text-gray-900">{{ $purchase->outlet->name }}</p>
                    <p class="text-xs text-gray-500">{{ $purchase->outlet->code }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Supplier</p>
                    <p class="text-sm font-medium text-gray-900">{{ $purchase->supplier->name }}</p>
                    @if($purchase->supplier->phone)
                        <p class="text-xs text-gray-500">{{ $purchase->supplier->phone }}</p>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Dibuat Oleh</p>
                    <p class="text-sm font-medium text-gray-900">{{ $purchase->creator->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $purchase->created_at->format('d M Y H:i') }}</p>
                </div>

                @if($purchase->isReceived())
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Diterima Oleh</p>
                    <p class="text-sm font-medium text-gray-900">{{ $purchase->receiver->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $purchase->received_at ? $purchase->received_at->format('d M Y H:i') : '-' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Item Pembelian</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produk</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">SKU</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Qty</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Harga Satuan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Diskon</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($purchase->items as $item)
                    <tr>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $item->product->name }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-600">{{ $item->product->sku }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-gray-900">{{ number_format($item->quantity, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-gray-900">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-red-600">Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-semibold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary -->
        <div class="p-6 border-t border-gray-100 bg-gray-50">
            <div class="max-w-md ml-auto space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal:</span>
                    <span class="font-semibold">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Pajak:</span>
                    <span class="font-semibold">Rp {{ number_format($purchase->tax_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Diskon:</span>
                    <span class="font-semibold text-red-600">Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</span>
                </div>
                <div class="border-t border-gray-300 pt-3 flex justify-between">
                    <span class="text-lg font-semibold text-gray-900">Total:</span>
                    <span class="text-xl font-bold text-indigo-600">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Status & History -->
    @if($purchase->isReceived())
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Status Pembayaran</h3>
                <p class="text-sm text-gray-500 mt-1">Informasi pembayaran purchase</p>
            </div>
            <div>
                @if($purchase->payment_status === 'paid')
                    <span class="px-4 py-2 text-sm font-medium bg-green-100 text-green-800 rounded-full">Lunas</span>
                @elseif($purchase->payment_status === 'partial')
                    <span class="px-4 py-2 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full">Dibayar Sebagian</span>
                @else
                    <span class="px-4 py-2 text-sm font-medium bg-red-100 text-red-800 rounded-full">Belum Dibayar</span>
                @endif
            </div>
        </div>

        <div class="p-6">
            <!-- Payment Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Purchase</p>
                    <p class="text-xl font-bold text-gray-900">Rp {{ number_format($purchase->total_amount, 0, ',', '.') }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Sudah Dibayar</p>
                    <p class="text-xl font-bold text-green-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Sisa Tagihan</p>
                    <p class="text-xl font-bold text-red-600">Rp {{ number_format($remaining, 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Payment History -->
            @if($purchase->cashTransactions && $purchase->cashTransactions->count() > 0)
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Riwayat Pembayaran</h4>
                <div class="space-y-2">
                    @foreach($purchase->cashTransactions as $payment)
                    <div class="flex items-center justify-between py-3 px-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $payment->transaction_number }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $payment->transaction_date->format('d M Y') }} • 
                                    {{ $payment->cashAccount->name }}
                                </p>
                                @if($payment->notes)
                                    <p class="text-xs text-gray-400 mt-1">{{ $payment->notes }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500">{{ $payment->creator->name ?? '-' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500">Belum ada pembayaran</p>
            </div>
            @endif

            <!-- Payment Action Button -->
            @if($remaining > 0)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.purchases.payment', $purchase) }}" class="w-full inline-flex justify-center items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Catat Pembayaran
                </a>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Notes -->
    @if($purchase->notes)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-2">Catatan:</h4>
        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $purchase->notes }}</p>
    </div>
    @endif

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.purchases.index') }}" class="px-5 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali
        </a>

        <div class="flex items-center space-x-3">
            @if($purchase->isDraft())
                <!-- Edit -->
                <a href="{{ route('admin.purchases.edit', $purchase) }}" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>

                <!-- Receive -->
                <form action="{{ route('admin.purchases.receive', $purchase) }}" method="POST" onsubmit="return confirm('Terima barang ini? Stok akan ditambahkan ke sistem.')">
                    @csrf
                    <button type="submit" class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Terima Barang
                    </button>
                </form>

                <!-- Cancel -->
                <button onclick="showCancelModal()" class="px-5 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Batalkan
                </button>

                <!-- Delete -->
                <form action="{{ route('admin.purchases.destroy', $purchase) }}" method="POST" onsubmit="return confirm('Hapus pembelian ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Hapus
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Modal Cancel -->
<div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Batalkan Pembelian</h3>
            <form action="{{ route('admin.purchases.cancel', $purchase) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Pembatalan</label>
                    <textarea name="reason" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Masukkan alasan pembatalan..."></textarea>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" onclick="hideCancelModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">
                        Batalkan Pembelian
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
}

function hideCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}
</script>
@endsection
