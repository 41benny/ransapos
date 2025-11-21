@extends('layouts.admin')

@section('title', 'Detail Transfer Stok')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Detail Transfer Stok</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $stockTransfer->transfer_number }}</p>
            </div>
            <a href="{{ route('admin.stock-transfers.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>

        <!-- Status Badge -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-2">Status Transfer</p>
                    @if($stockTransfer->status == 'pending')
                        <span class="px-4 py-2 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-2"></i>Pending (Belum Dikirim)
                        </span>
                    @elseif($stockTransfer->status == 'in_transit')
                        <span class="px-4 py-2 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-truck mr-2"></i>In Transit (Dalam Perjalanan)
                        </span>
                    @elseif($stockTransfer->status == 'received')
                        <span class="px-4 py-2 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>Received (Sudah Diterima)
                        </span>
                    @elseif($stockTransfer->status == 'cancelled')
                        <span class="px-4 py-2 text-sm font-semibold rounded-full bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-2"></i>Cancelled (Dibatalkan)
                        </span>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    @if($stockTransfer->canBeSent())
                        <form method="POST" action="{{ route('admin.stock-transfers.send', $stockTransfer->id) }}"
                              onsubmit="return confirm('Kirim transfer ini? Stok akan dikurangi dari outlet pengirim.')">
                            @csrf
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Transfer
                            </button>
                        </form>
                    @endif

                    @if($stockTransfer->canBeReceived())
                        <a href="{{ route('admin.stock-transfers.receive-form', $stockTransfer->id) }}"
                           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-check mr-2"></i>Terima Barang
                        </a>
                    @endif

                    @if($stockTransfer->canBeCancelled())
                        <button type="button" onclick="showCancelModal()"
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-ban mr-2"></i>Batalkan
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transfer Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- From/To Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Transfer</h3>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <div class="w-32 text-sm text-gray-600">Dari Outlet</div>
                        <div class="font-semibold text-gray-900">{{ $stockTransfer->fromOutlet->name }}</div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-32 text-sm text-gray-600">Ke Outlet</div>
                        <div class="font-semibold text-gray-900">{{ $stockTransfer->toOutlet->name }}</div>
                    </div>
                    <div class="flex items-center">
                        <div class="w-32 text-sm text-gray-600">Tanggal</div>
                        <div class="text-gray-900">{{ $stockTransfer->transfer_date->format('d F Y') }}</div>
                    </div>
                    <div class="flex items-start">
                        <div class="w-32 text-sm text-gray-600">Catatan</div>
                        <div class="text-gray-900">{{ $stockTransfer->notes ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="w-2 h-2 bg-gray-400 rounded-full mt-2 mr-3"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Dibuat</p>
                            <p class="text-xs text-gray-600">{{ $stockTransfer->created_at->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-gray-500">oleh {{ $stockTransfer->creator->name ?? '-' }}</p>
                        </div>
                    </div>

                    @if($stockTransfer->sent_at)
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-3"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Dikirim</p>
                                <p class="text-xs text-gray-600">{{ $stockTransfer->sent_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-500">oleh {{ $stockTransfer->sender->name ?? '-' }}</p>
                            </div>
                        </div>
                    @endif

                    @if($stockTransfer->received_at)
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-green-500 rounded-full mt-2 mr-3"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Diterima</p>
                                <p class="text-xs text-gray-600">{{ $stockTransfer->received_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-500">oleh {{ $stockTransfer->receiver->name ?? '-' }}</p>
                            </div>
                        </div>
                    @endif

                    @if($stockTransfer->cancelled_at)
                        <div class="flex items-start">
                            <div class="w-2 h-2 bg-red-500 rounded-full mt-2 mr-3"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Dibatalkan</p>
                                <p class="text-xs text-gray-600">{{ $stockTransfer->cancelled_at->format('d/m/Y H:i') }}</p>
                                <p class="text-xs text-gray-500">oleh {{ $stockTransfer->canceller->name ?? '-' }}</p>
                                @if($stockTransfer->cancel_reason)
                                    <p class="text-xs text-gray-600 mt-1">Alasan: {{ $stockTransfer->cancel_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Produk yang Ditransfer</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Dikirim</th>
                            @if($stockTransfer->isReceived())
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Diterima</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selisih</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($stockTransfer->items as $item)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $item->product->sku ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900">
                                    {{ number_format($item->quantity, 2) }} {{ $item->product->unit ?? 'pcs' }}
                                </td>
                                @if($stockTransfer->isReceived())
                                    <td class="px-6 py-4 text-right text-gray-900">
                                        {{ number_format($item->received_quantity, 2) }} {{ $item->product->unit ?? 'pcs' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @php
                                            $diff = $item->received_quantity - $item->quantity;
                                        @endphp
                                        @if($diff == 0)
                                            <span class="text-gray-600">-</span>
                                        @elseif($diff < 0)
                                            <span class="text-red-600 font-semibold">{{ number_format($diff, 2) }}</span>
                                        @else
                                            <span class="text-green-600 font-semibold">+{{ number_format($diff, 2) }}</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $item->notes ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Batalkan Transfer</h3>
        <form method="POST" action="{{ route('admin.stock-transfers.cancel', $stockTransfer->id) }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Alasan Pembatalan <span class="text-red-500">*</span></label>
                <textarea name="cancel_reason" rows="3" required
                          class="w-full border-gray-300 rounded-lg focus:border-red-500 focus:ring-red-500"
                          placeholder="Jelaskan alasan pembatalan..."></textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                    Ya, Batalkan
                </button>
                <button type="button" onclick="hideCancelModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCancelModal() {
    document.getElementById('cancelModal').classList.remove('hidden');
    document.getElementById('cancelModal').classList.add('flex');
}

function hideCancelModal() {
    document.getElementById('cancelModal').classList.add('hidden');
    document.getElementById('cancelModal').classList.remove('flex');
}
</script>
@endpush
@endsection
