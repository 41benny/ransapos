@extends('layouts.admin')

@section('title', 'Daftar Resep Produk (BOM)')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Bill of Materials (BOM)</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.boms.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tambah BOM
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Produk</th>
                                    <th>Nama BOM</th>
                                    <th>Status</th>
                                    <th>Jumlah Komponen</th>
                                    <th>Dibuat</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($boms as $bom)
                                    <tr>
                                        <td>{{ $bom->id }}</td>
                                        <td>
                                            <strong>{{ $bom->product->name }}</strong><br>
                                            <small class="text-muted">{{ $bom->product->sku }}</small>
                                        </td>
                                        <td>{{ $bom->name ?? '-' }}</td>
                                        <td>
                                            @if($bom->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-secondary">Tidak Aktif</span>
                                            @endif
                                        </td>
                                        <td>{{ $bom->details_count ?? 0 }} komponen</td>
                                        <td>{{ $bom->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.boms.show', $bom) }}" class="btn btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.boms.edit', $bom) }}" class="btn btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('admin.boms.destroy', $bom) }}" method="POST" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin hapus BOM ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Belum ada data BOM</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $boms->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection