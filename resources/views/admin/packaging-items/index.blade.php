@extends('layouts.admin')

@section('title', 'Item Packaging')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Master Item Packaging</h1>
        <p class="text-gray-600 mt-1">Kelola item packaging (box, cup, dll) untuk kontrol stok per shift.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Add form --}}
    <div class="bg-white rounded-xl shadow-md p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Tambah Item</h2>
        <form action="{{ route('admin.packaging-items.store') }}" method="POST" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Nama</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none" placeholder="Box 6 / Cup">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Unit</label>
                <input type="text" name="unit" value="{{ old('unit', 'pcs') }}"
                       class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Urutan</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:outline-none">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-plus mr-1"></i> Tambah
            </button>
        </form>
    </div>

    {{-- Forms declared outside the table; rows reference them via the HTML5 form attribute --}}
    @foreach($items as $item)
    <form id="pkg-item-{{ $item->id }}" action="{{ route('admin.packaging-items.update', $item) }}" method="POST" class="hidden">
        @csrf @method('PUT')
    </form>
    @endforeach

    {{-- List --}}
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3 text-left">Urutan</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Unit</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($items as $item)
                <tr>
                    <td class="px-4 py-2">
                        <input type="number" form="pkg-item-{{ $item->id }}" name="sort_order" value="{{ $item->sort_order }}" min="0"
                               class="w-16 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" form="pkg-item-{{ $item->id }}" name="name" value="{{ $item->name }}" required
                               class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" form="pkg-item-{{ $item->id }}" name="unit" value="{{ $item->unit }}" required
                               class="w-24 px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                    </td>
                    <td class="px-4 py-2 text-center">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" form="pkg-item-{{ $item->id }}" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs {{ $item->is_active ? 'text-green-600' : 'text-gray-400' }}">Aktif</span>
                        </label>
                    </td>
                    <td class="px-4 py-2 text-right">
                        <button type="submit" form="pkg-item-{{ $item->id }}" class="px-3 py-1.5 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-xs">
                            <i class="fas fa-save mr-1"></i> Simpan
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada item packaging.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
