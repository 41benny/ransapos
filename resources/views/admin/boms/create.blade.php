@extends('layouts.admin')

@section('title', 'Tambah BOM')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Tambah Bill of Materials</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.boms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <form action="{{ route('admin.boms.store') }}" method="POST">
                    @csrf
                    
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="product_id">Produk Utama <span class="text-danger">*</span></label>
                                    <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                        <option value="">Pilih Produk...</option>
                                        @foreach($finishedProducts as $product)
                                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                {{ $product->name }} ({{ $product->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('product_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="name">Nama BOM</label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" placeholder="Contoh: Resep Nasi Goreng Special">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" 
                                               {{ old('is_active', 1) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Aktif</label>
                                    </div>
                                    <small class="text-muted">BOM hanya digunakan jika dalam status aktif</small>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Catatan</label>
                                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" 
                                              rows="3" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5>Komponen BOM <span class="text-danger">*</span></h5>
                                <div id="components-container">
                                    @if(old('components'))
                                        @foreach(old('components') as $index => $component)
                                            <div class="component-row border p-3 mb-2">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label>Bahan/Komponen</label>
                                                        <select name="components[{{ $index }}][component_product_id]" class="form-control" required>
                                                            <option value="">Pilih Bahan...</option>
                                                            @foreach($rawMaterials as $raw)
                                                                <option value="{{ $raw->id }}" {{ $component['component_product_id'] == $raw->id ? 'selected' : '' }}>
                                                                    {{ $raw->name }} ({{ $raw->sku }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label>Jumlah</label>
                                                        <input type="number" name="components[{{ $index }}][quantity]" 
                                                               class="form-control" step="0.0001" min="0.0001" 
                                                               value="{{ $component['quantity'] }}" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>&nbsp;</label>
                                                        <button type="button" class="btn btn-danger btn-block remove-component">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row mt-2">
                                                    <div class="col-md-4">
                                                        <label>Satuan</label>
                                                        <input type="text" name="components[{{ $index }}][uom]" 
                                                               class="form-control" placeholder="kg, pcs, liter..." 
                                                               value="{{ $component['uom'] ?? '' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <!-- Default first component row -->
                                        <div class="component-row border p-3 mb-2">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Bahan/Komponen</label>
                                                    <select name="components[0][component_product_id]" class="form-control" required>
                                                        <option value="">Pilih Bahan...</option>
                                                        @foreach($rawMaterials as $raw)
                                                            <option value="{{ $raw->id }}">{{ $raw->name }} ({{ $raw->sku }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label>Jumlah</label>
                                                    <input type="number" name="components[0][quantity]" class="form-control" 
                                                           step="0.0001" min="0.0001" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label>&nbsp;</label>
                                                    <button type="button" class="btn btn-danger btn-block remove-component">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row mt-2">
                                                <div class="col-md-4">
                                                    <label>Satuan</label>
                                                    <input type="text" name="components[0][uom]" class="form-control" 
                                                           placeholder="kg, pcs, liter...">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" id="add-component" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> Tambah Komponen
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan BOM
                        </button>
                        <a href="{{ route('admin.boms.index') }}" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Data for JavaScript (without mixing Blade syntax in script tags) -->
<div id="app-data" style="display: none;" 
     data-component-index="{{ old('components') ? count(old('components')) : 1 }}"
     data-raw-materials="{{ json_encode($rawMaterials->map(fn($raw) => ['id' => $raw->id, 'name' => $raw->name, 'sku' => $raw->sku])) }}">
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get data from data attributes (clean approach)
    var appData = document.getElementById('app-data');
    var componentIndex = parseInt(appData.getAttribute('data-component-index'));
    var rawMaterials = JSON.parse(appData.getAttribute('data-raw-materials'));
    
    // Build options HTML
    var rawMaterialsOptions = '';
    rawMaterials.forEach(function(raw) {
        rawMaterialsOptions += '<option value="' + raw.id + '">' + raw.name + ' (' + raw.sku + ')</option>';
    });
    
    // Add component
    document.getElementById('add-component').addEventListener('click', function() {
        var container = document.getElementById('components-container');
        var newRow = createComponentRow(componentIndex);
        container.insertAdjacentHTML('beforeend', newRow);
        componentIndex++;
    });
    
    // Remove component  
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-component') || e.target.closest('.remove-component')) {
            var row = e.target.closest('.component-row');
            if (document.querySelectorAll('.component-row').length > 1) {
                row.remove();
            } else {
                alert('Minimal harus ada 1 komponen');
            }
        }
    });
    
    function createComponentRow(index) {
        return '<div class="component-row border p-3 mb-2">' +
                    '<div class="row">' +
                        '<div class="col-md-6">' +
                            '<label>Bahan/Komponen</label>' +
                            '<select name="components[' + index + '][component_product_id]" class="form-control" required>' +
                                '<option value="">Pilih Bahan...</option>' +
                                rawMaterialsOptions +
                            '</select>' +
                        '</div>' +
                        '<div class="col-md-4">' +
                            '<label>Jumlah</label>' +
                            '<input type="number" name="components[' + index + '][quantity]" class="form-control" step="0.0001" min="0.0001" required>' +
                        '</div>' +
                        '<div class="col-md-2">' +
                            '<label>&nbsp;</label>' +
                            '<button type="button" class="btn btn-danger btn-block remove-component"><i class="fas fa-trash"></i></button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="row mt-2">' +
                        '<div class="col-md-4">' +
                            '<label>Satuan</label>' +
                            '<input type="text" name="components[' + index + '][uom]" class="form-control" placeholder="kg, pcs, liter...">' +
                        '</div>' +
                    '</div>' +
                '</div>';
    }
});
</script>
@endpush
@endsection