@extends('layouts.admin')

@section('title', 'Daftar Resep Produk (BOM)')

@section('content')
<div class="container-fluid page-fullwidth px-0">
    <div class="row no-gutters">
        <div class="col-12">
            <div class="bg-white rounded-none lg:rounded-2xl shadow-premium border border-slate-200 p-6 lg:p-8 page-card-fill">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-amber-600 font-semibold mb-1">Resep Produk</p>
                            <h3 class="text-xl font-semibold text-slate-900">Daftar Bill of Materials (BOM)</h3>
                        </div>
                        <a href="{{ route('admin.boms.create') }}" class="imperial-btn imperial-btn-sm">
                            <i class="fas fa-plus"></i> Tambah BOM
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                            <p class="text-sm">{{ session('success') }}</p>
                        </div>
                    @endif

                    <div class="rounded-xl border border-slate-200 overflow-hidden">
                        <div class="table-responsive">
                            <table class="imperial-table">
                                <thead>
                                    <tr>
                                        <th class="whitespace-nowrap">ID</th>
                                        <th>Produk</th>
                                        <th>Nama BOM</th>
                                        <th>Status</th>
                                        <th class="text-center whitespace-nowrap">Jumlah Komponen</th>
                                        <th>Dibuat</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($boms as $bom)
                                        <tr>
                                            <td>{{ $bom->id }}</td>
                                            <td>
                                                <div class="font-semibold text-slate-800">{{ $bom->product->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $bom->product->sku }}</div>
                                            </td>
                                            <td>{{ $bom->name ?? '-' }}</td>
                                            <td>
                                                @if($bom->is_active)
                                                    <span class="imperial-badge"><i class="fas fa-check-circle"></i> Aktif</span>
                                                @else
                                                    <span class="badge badge-gray">Tidak Aktif</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $bom->details_count ?? 0 }} komponen</td>
                                            <td>{{ $bom->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('admin.boms.show', $bom) }}" class="imperial-btn-info imperial-btn-sm" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.boms.edit', $bom) }}" class="imperial-btn-warning imperial-btn-sm" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.boms.destroy', $bom) }}" method="POST" onsubmit="return confirm('Yakin hapus BOM ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="imperial-btn-danger imperial-btn-sm" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-slate-600 py-6">Belum ada data BOM</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        {{ $boms->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
