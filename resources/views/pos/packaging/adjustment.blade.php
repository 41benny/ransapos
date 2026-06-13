@extends('layouts.pos')

@section('title', 'Adjustment Packaging')
@section('page-title', 'Adjustment Packaging')

@section('content')
<div class="h-full overflow-y-auto relative bg-gray-900">
    <div class="max-w-6xl mx-auto p-6 md:p-8 relative z-10">

        <div class="flex items-center justify-between mb-8 flex-wrap gap-3">
            <div>
                <h2 class="text-3xl font-bold text-white tracking-tight">Adjustment Packaging</h2>
                <p class="text-gray-400 mt-1">Catat packaging masuk/keluar saat shift berjalan. Perlu approval backoffice.</p>
            </div>
            <a href="{{ route('pos.sessions.close') }}"
               class="px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-white rounded-xl border border-gray-700 transition">
                Ke Tutup Shift
            </a>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-900/30 border border-green-500/30 text-green-300 text-sm">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-red-900/30 border border-red-500/30 text-red-300 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            {{-- Form --}}
            <div class="lg:col-span-2">
                <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl">
                    <h3 class="text-lg font-semibold text-white mb-4">Form Adjustment</h3>
                    <form action="{{ route('pos.packaging.adjustment.store') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tipe</label>
                            <input type="hidden" name="type" id="typeInput" value="{{ old('type', 'in') }}">
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" data-type="in" data-active="bg-green-900/40 border-green-500 text-green-300"
                                        class="type-btn text-center py-3 rounded-xl border border-gray-600 text-gray-300 transition">Masuk</button>
                                <button type="button" data-type="out" data-active="bg-red-900/40 border-red-500 text-red-300"
                                        class="type-btn text-center py-3 rounded-xl border border-gray-600 text-gray-300 transition">Keluar</button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Item Packaging</label>
                            <select name="packaging_item_id" required
                                    class="w-full px-3 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                                <option value="">-- Pilih Item --</option>
                                @foreach($packagingItems as $item)
                                <option value="{{ $item->id }}" {{ old('packaging_item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }} ({{ $item->unit }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Jumlah</label>
                            <input type="number" name="qty" min="0" step="1" value="{{ old('qty') }}" required
                                   class="w-full px-3 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white text-right font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Alasan</label>
                            <select name="reason" required
                                    class="w-full px-3 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50">
                                @foreach(['Tambahan dari gudang', 'Rusak', 'Hilang', 'Dipakai event', 'Lainnya'] as $reason)
                                <option value="{{ $reason }}" {{ old('reason') === $reason ? 'selected' : '' }}>{{ $reason }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Catatan (Opsional)</label>
                            <textarea name="note" rows="2"
                                      class="w-full px-3 py-3 bg-gray-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full py-3.5 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-indigo-500/30 transition">
                            Kirim untuk Approval
                        </button>
                    </form>
                </div>
            </div>

            {{-- Riwayat --}}
            <div class="lg:col-span-3">
                <div class="bg-gray-800/80 backdrop-blur-xl rounded-2xl p-6 border border-gray-700/50 shadow-2xl">
                    <h3 class="text-lg font-semibold text-white mb-4">Riwayat Shift Ini ({{ $adjustments->count() }})</h3>
                    @if($adjustments->count() > 0)
                    <div class="overflow-x-auto rounded-xl border border-gray-700/50">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead>
                                <tr class="bg-gray-900/50 text-gray-400 text-[11px] uppercase tracking-wider">
                                    <th class="px-3 py-3 text-left font-medium">Waktu</th>
                                    <th class="px-3 py-3 text-left font-medium">Item</th>
                                    <th class="px-3 py-3 text-center font-medium">Tipe</th>
                                    <th class="px-3 py-3 text-right font-medium">Qty</th>
                                    <th class="px-3 py-3 text-left font-medium">Alasan</th>
                                    <th class="px-3 py-3 text-center font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700/50">
                                @foreach($adjustments as $adj)
                                <tr class="bg-gray-800/30">
                                    <td class="px-3 py-2.5 text-gray-400">{{ $adj->created_at->format('d M H:i') }}</td>
                                    <td class="px-3 py-2.5 text-white">{{ $adj->packagingItem->name ?? '-' }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] {{ $adj->type === 'in' ? 'bg-green-900/40 text-green-300' : 'bg-red-900/40 text-red-300' }}">
                                            {{ $adj->type === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-right font-mono text-white">{{ (float) $adj->qty }}</td>
                                    <td class="px-3 py-2.5 text-gray-300">{{ $adj->reason }}</td>
                                    <td class="px-3 py-2.5 text-center">
                                        @php
                                            $badge = [
                                                'pending' => 'bg-amber-900/40 text-amber-300',
                                                'approved' => 'bg-green-900/40 text-green-300',
                                                'rejected' => 'bg-red-900/40 text-red-300',
                                            ][$adj->status] ?? 'bg-gray-700 text-gray-300';
                                        @endphp
                                        <span class="px-2 py-0.5 rounded-full text-[11px] {{ $badge }}">{{ ucfirst($adj->status) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-10 text-gray-500 bg-gray-900/30 rounded-xl border border-dashed border-gray-700">
                        Belum ada adjustment pada shift ini.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const input = document.getElementById('typeInput');
    const buttons = document.querySelectorAll('.type-btn');

    function setType(val) {
        input.value = val;
        buttons.forEach(function (btn) {
            const active = btn.dataset.active.split(' ');
            if (btn.dataset.type === val) {
                btn.classList.remove('border-gray-600', 'text-gray-300');
                btn.classList.add.apply(btn.classList, active);
            } else {
                btn.classList.remove.apply(btn.classList, active);
                btn.classList.add('border-gray-600', 'text-gray-300');
            }
        });
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () { setType(btn.dataset.type); });
    });

    setType(input.value || 'in');
})();
</script>
@endsection
