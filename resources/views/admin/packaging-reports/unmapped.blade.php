@extends('layouts.admin')

@section('title', 'Produk Belum Mapping Packaging')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Produk Belum Mapping Packaging</h1>
            <p class="text-gray-600 mt-1">Produk terjual yang belum punya mapping, sehingga estimasi packaging-nya belum dihitung.</p>
        </div>
        <a href="{{ route('admin.packaging-mappings.index', ['filter' => 'unmapped']) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
            <i class="fas fa-link mr-1"></i> Lengkapi Mapping
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
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm">Filter</button>
        <a href="{{ route('admin.packaging-reports.unmapped') }}" class="px-4 py-2 text-gray-600 hover:text-gray-900 text-sm">Reset</a>
    </form>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Outlet</th>
                    <th class="px-4 py-3 text-left">Shift</th>
                    <th class="px-4 py-3 text-left">Kasir</th>
                    <th class="px-4 py-3 text-left">Produk</th>
                    <th class="px-4 py-3 text-left">SKU</th>
                    <th class="px-4 py-3 text-left">Kategori</th>
                    <th class="px-4 py-3 text-right">Qty Terjual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $r)
                <tr>
                    <td class="px-4 py-2.5 text-gray-500">{{ $r->business_date ? \Illuminate\Support\Carbon::parse($r->business_date)->format('d M Y') : '-' }}</td>
                    <td class="px-4 py-2.5 text-gray-700">{{ $r->outlet_name ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-gray-500 font-mono text-xs">{{ $r->session_number }}</td>
                    <td class="px-4 py-2.5 text-gray-700">{{ $r->kasir_name ?? '-' }}</td>
                    <td class="px-4 py-2.5 font-medium text-gray-800">{{ $r->product_name }}</td>
                    <td class="px-4 py-2.5 text-gray-500">{{ $r->product_sku ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-gray-600">{{ $r->category ?? '-' }}</td>
                    <td class="px-4 py-2.5 text-right font-mono">{{ (float) $r->qty_sold }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Semua produk terjual sudah dimapping. 🎉</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
@endsection
