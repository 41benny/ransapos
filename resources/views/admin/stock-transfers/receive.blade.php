@extends('layouts.admin')

@section('title', 'Terima Transfer Stok')

@section('content')
<div class="ui-page-shell container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-normal text-gray-900">Terima Transfer Stok</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $stockTransfer->transfer_number }}</p>
        </div>

        <!-- Alert Info -->
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Verifikasi jumlah barang yang diterima. Jika ada selisih (kurang/lebih), masukkan jumlah aktual yang diterima.
                        Selisih akan tercatat jelas sebagai adjustment. Shortage otomatis dikembalikan ke stok outlet pengirim.
                    </p>
                </div>
            </div>
        </div>

        <!-- Transfer Info -->
        <div class="ui-card bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Dari Outlet</p>
                    <p class="font-semibold text-gray-900">{{ $stockTransfer->fromOutlet->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Ke Outlet</p>
                    <p class="font-semibold text-gray-900">{{ $stockTransfer->toOutlet->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tanggal Transfer</p>
                    <p class="text-gray-900">{{ $stockTransfer->transfer_date->format('d F Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Dikirim Oleh</p>
                    <p class="text-gray-900">{{ $stockTransfer->sender->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $stockTransfer->sent_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Receive Form -->
        <form method="POST" action="{{ route('admin.stock-transfers.receive', $stockTransfer->id) }}" id="receiveForm">
            @csrf
            <div class="ui-card bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold text-gray-900">Verifikasi Produk yang Diterima</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="ui-table min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Produk</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty Dikirim</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty Diterima <span class="text-red-500">*</span></th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stockTransfer->items as $item)
                                <tr class="item-row">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item->product->sku ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900">
                                        <span class="sent-qty">{{ number_format($item->quantity, 2) }}</span> {{ $item->product->unit ?? 'pcs' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="number"
                                               name="items[{{ $item->id }}]"
                                               step="0.01"
                                               min="0"
                                               value="{{ old('items.' . $item->id, $item->quantity) }}"
                                               class="received-qty ui-input w-full border-gray-300 rounded-lg text-center focus:border-indigo-500 focus:ring-indigo-500"
                                               data-sent="{{ $item->quantity }}"
                                               required>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="difference-value text-gray-600 font-semibold">0.00</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="px-6 py-4 bg-gray-50 border-t">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3">
                        <div>
                            <p class="text-sm text-gray-600">Total Items</p>
                            <p class="text-xl font-bold text-gray-900">{{ $stockTransfer->items->count() }} produk</p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                            <div class="rounded-lg bg-white border px-3 py-2">
                                <p class="text-gray-500">Total Kirim</p>
                                <p class="font-semibold text-gray-900" id="totalSentQty">0.00</p>
                            </div>
                            <div class="rounded-lg bg-white border px-3 py-2">
                                <p class="text-gray-500">Total Terima</p>
                                <p class="font-semibold text-gray-900" id="totalReceivedQty">0.00</p>
                            </div>
                            <div class="rounded-lg bg-white border px-3 py-2">
                                <p class="text-gray-500">Total Kurang</p>
                                <p class="font-semibold text-rose-600" id="totalShortageQty">0.00</p>
                            </div>
                            <div class="rounded-lg bg-white border px-3 py-2">
                                <p class="text-gray-500">Total Lebih</p>
                                <p class="font-semibold text-emerald-600" id="totalExcessQty">0.00</p>
                            </div>
                        </div>
                        <div id="warningMessage" class="hidden">
                            <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-2 rounded-lg">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Ada selisih quantity. Sistem akan mencatat detail selisih secara otomatis.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 py-4 border-t flex gap-3">
                    <button type="submit" class="ui-btn ui-btn-primary flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition font-semibold">
                        <i class="fas fa-check mr-2"></i>Terima & Simpan ke Stok
                    </button>
                    <a href="{{ route('admin.stock-transfers.show', $stockTransfer->id) }}"
                       class="ui-btn ui-btn-ghost bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition font-semibold">
                        Batal
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const receivedInputs = document.querySelectorAll('.received-qty');
    const warningMessage = document.getElementById('warningMessage');
    const totalSentQtyEl = document.getElementById('totalSentQty');
    const totalReceivedQtyEl = document.getElementById('totalReceivedQty');
    const totalShortageQtyEl = document.getElementById('totalShortageQty');
    const totalExcessQtyEl = document.getElementById('totalExcessQty');
    let hasDifference = false;

    receivedInputs.forEach(input => {
        input.addEventListener('input', function() {
            calculateDifference(this);
            refreshSummary();
        });

        // Calculate on page load
        calculateDifference(input);
    });
    refreshSummary();

    function calculateDifference(input) {
        const row = input.closest('.item-row');
        const sentQty = parseFloat(input.dataset.sent);
        const receivedQty = parseFloat(input.value) || 0;
        const difference = receivedQty - sentQty;
        const differenceSpan = row.querySelector('.difference-value');

        if (difference === 0) {
            differenceSpan.textContent = '-';
            differenceSpan.className = 'difference-value text-gray-600 font-semibold';
        } else if (difference < 0) {
            differenceSpan.textContent = difference.toFixed(2);
            differenceSpan.className = 'difference-value text-red-600 font-semibold';
        } else {
            differenceSpan.textContent = '+' + difference.toFixed(2);
            differenceSpan.className = 'difference-value text-green-600 font-semibold';
        }
    }

    function checkForDifferences() {
        hasDifference = false;
        let totalSentQty = 0;
        let totalReceivedQty = 0;
        let totalShortageQty = 0;
        let totalExcessQty = 0;

        receivedInputs.forEach(input => {
            const sentQty = parseFloat(input.dataset.sent);
            const receivedQty = parseFloat(input.value) || 0;
            totalSentQty += sentQty;
            totalReceivedQty += receivedQty;

            const diff = receivedQty - sentQty;
            if (diff < 0) {
                totalShortageQty += Math.abs(diff);
            } else if (diff > 0) {
                totalExcessQty += diff;
            }

            if (sentQty !== receivedQty) {
                hasDifference = true;
            }
        });

        totalSentQtyEl.textContent = totalSentQty.toFixed(2);
        totalReceivedQtyEl.textContent = totalReceivedQty.toFixed(2);
        totalShortageQtyEl.textContent = totalShortageQty.toFixed(2);
        totalExcessQtyEl.textContent = totalExcessQty.toFixed(2);

        if (hasDifference) {
            warningMessage.classList.remove('hidden');
        } else {
            warningMessage.classList.add('hidden');
        }
    }

    function refreshSummary() {
        checkForDifferences();
    }

    // Form submission confirmation
    document.getElementById('receiveForm').addEventListener('submit', function(e) {
        if (hasDifference) {
            if (!confirm('Ada selisih quantity antara yang dikirim dan diterima. Yakin ingin melanjutkan?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
@endpush
@endsection
