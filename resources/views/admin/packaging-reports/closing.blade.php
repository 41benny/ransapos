@extends('layouts.admin')

@section('title', 'Laporan Closing Packaging')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Laporan Closing Packaging</h1>
            <p class="text-gray-600 mt-1">Pemakaian aktual vs estimasi sales per shift per item.</p>
        </div>
        <a href="{{ route('admin.packaging-reports.unmapped') }}" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 text-sm">
            <i class="fas fa-triangle-exclamation mr-1"></i> Produk Belum Mapping
        </a>
    </div>

    <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Outlet</label>
            <select name="outlet_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Semua</option>
                @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>{{ $outlet->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Dari</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Sampai</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="only_diff" value="1" {{ request('only_diff') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
            Hanya yang selisih
        </label>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm">Filter</button>
        <a href="{{ route('admin.packaging-reports.closing') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
    </form>

    @if(($dimsumSummary['total_pieces'] ?? 0) > 0)
    {{--
        Total pcs dimsum yang laku pada seluruh rentang & outlet terfilter
        (bukan hanya halaman yang sedang dibuka — dihitung lewat query agregat
        terpisah di controller). Isi per box diambil dari nama box itu sendiri:
        "Box 4" = 4 pcs, "Box 32" = 32 pcs.
    --}}
    <div class="bg-white rounded-xl shadow-md p-5 mb-5 border-l-4 border-orange-500">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">Total Pcs Dimsum Laku</p>
                <p class="text-xs text-gray-400 mt-0.5">Berdasarkan estimasi penjualan (bukan hitung fisik box), sesuai filter di atas</p>
                @if(request('only_diff'))
                {{--
                    Filter "hanya yang selisih" ikut membatasi angka ini, jadi yang
                    tampil BUKAN total dimsum laku melainkan hanya pcs dari box yang
                    bermasalah. Tanpa keterangan ini angkanya sangat mudah salah baca.
                --}}
                <p class="text-xs text-orange-600 font-semibold mt-1">
                    <i class="fas fa-filter mr-1"></i>Difilter "hanya yang selisih" — ini bukan total laku keseluruhan
                </p>
                @endif
            </div>
            <p class="text-3xl font-bold text-orange-600">
                {{ number_format($dimsumSummary['total_pieces'], 0, ',', '.') }} <span class="text-base font-semibold text-gray-500">pcs</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($dimsumSummary['breakdown'] as $b)
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 border border-orange-200 text-xs">
                <span class="font-semibold text-gray-700">{{ $b['item_name'] }}</span>
                <span class="text-gray-400">·</span>
                <span class="text-gray-600 font-mono">{{ rtrim(rtrim(number_format($b['boxes'], 2, ',', '.'), '0'), ',') }} box × {{ $b['pieces_per_box'] }}</span>
                <span class="text-gray-400">=</span>
                <span class="font-bold text-orange-700 font-mono">{{ number_format($b['pieces'], 0, ',', '.') }} pcs</span>
            </span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-3 py-3 text-left">Tanggal</th>
                    <th class="px-3 py-3 text-left">Outlet</th>
                    <th class="px-3 py-3 text-left">Shift</th>
                    <th class="px-3 py-3 text-left">Kasir</th>
                    <th class="px-3 py-3 text-left">Item</th>
                    <th class="px-3 py-3 text-right">Awal</th>
                    <th class="px-3 py-3 text-right">Msk(A)</th>
                    <th class="px-3 py-3 text-right">Klr(A)</th>
                    <th class="px-3 py-3 text-right">Pending</th>
                    <th class="px-3 py-3 text-right">Fisik</th>
                    <th class="px-3 py-3 text-right">Aktual</th>
                    <th class="px-3 py-3 text-right">Estimasi</th>
                    <th class="px-3 py-3 text-right">Selisih</th>
                    <th class="px-3 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $r)
                @php
                    $pendingNet = (float) $r->pending_adjustment_in_qty - (float) $r->pending_adjustment_out_qty;
                    $diff = (float) $r->difference_qty;
                    $hasPending = ((float) $r->pending_adjustment_in_qty > 0 || (float) $r->pending_adjustment_out_qty > 0);
                @endphp
                <tr>
                    <td class="px-3 py-2 text-gray-500">{{ $r->closed_at ? \Illuminate\Support\Carbon::parse($r->closed_at)->format('d M Y') : '-' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $r->outlet_name ?? '-' }}</td>
                    <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $r->session_number }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $r->kasir_name ?? '-' }}</td>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $r->item_name }}</td>
                    <td class="px-3 py-2 text-right font-mono">{{ (float) $r->opening_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono text-green-600">{{ (float) $r->approved_adjustment_in_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono text-red-600">{{ (float) $r->approved_adjustment_out_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono {{ $pendingNet != 0 ? 'text-amber-600' : 'text-gray-300' }}">{{ $pendingNet > 0 ? '+' : '' }}{{ $pendingNet }}</td>
                    <td class="px-3 py-2 text-right font-mono">{{ (float) $r->closing_physical_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono font-semibold">{{ (float) $r->actual_used_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono text-indigo-600">{{ (float) $r->estimated_sales_used_qty }}</td>
                    <td class="px-3 py-2 text-right font-mono font-bold {{ $diff > 0 ? 'text-orange-600' : ($diff < 0 ? 'text-sky-600' : 'text-gray-400') }}">
                        {{ $diff > 0 ? '+' : '' }}{{ $diff }}
                    </td>
                    <td class="px-3 py-2">
                        @if($hasPending)
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-700">With Pending</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-500">Normal</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" class="px-4 py-8 text-center text-gray-400">Belum ada data closing packaging.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
