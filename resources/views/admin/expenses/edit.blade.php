@extends('layouts.admin')

@section('title', 'Edit Pengajuan')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Pengajuan</h1>
        <p class="text-gray-600 mt-1">Update pengajuan biaya - {{ $expense->expense_number }}</p>
    </div>

    @if($expense->status !== 'pending')
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle mt-0.5 mr-2"></i>
            <div>
                <p class="font-medium">Perhatian</p>
                <p class="text-sm">Pengajuan ini sudah di-{{ $expense->status }}. Perubahan terbatas.</p>
            </div>
        </div>
    </div>
    @endif

    <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Pengajuan</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Outlet -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Outlet <span class="text-red-500">*</span>
                    </label>
                    <select name="outlet_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        <option value="">-- Pilih Outlet --</option>
                        @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ old('outlet_id', $expense->outlet_id) == $outlet->id ? 'selected' : '' }}>
                            {{ $outlet->name }}
                        </option>
                        @endforeach
                    </select>
                    @if($expense->status !== 'pending')
                    <input type="hidden" name="outlet_id" value="{{ $expense->outlet_id }}">
                    @endif
                    @error('outlet_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Kategori Biaya <span class="text-red-500">*</span>
                    </label>
                    <select name="expense_category_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            {{ $expense->status !== 'pending' ? 'disabled' : '' }}>
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->full_name }}
                        </option>
                        @endforeach
                    </select>
                    @if($expense->status !== 'pending')
                    <input type="hidden" name="expense_category_id" value="{{ $expense->expense_category_id }}">
                    @endif
                    @error('expense_category_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expense Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Pengajuan <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           {{ $expense->status !== 'pending' ? 'readonly' : '' }}>
                    @error('expense_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           {{ $expense->status !== 'pending' ? 'readonly' : '' }}>
                    @error('amount')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reference No -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        No. Referensi / Invoice (opsional)
                    </label>
                    <input type="text" name="reference_no" value="{{ old('reference_no', $expense->reference_no) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('reference_no')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Keterangan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="description" rows="4" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">{{ old('description', $expense->description) }}</textarea>
                    @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Attachment -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Lampiran (Bukti/Kwitansi)
                    </label>
                    @if($expense->attachment_path)
                    <div class="mb-2">
                        <a href="{{ asset('storage/' . $expense->attachment_path) }}" target="_blank"
                           class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                            <i class="fas fa-paperclip mr-2"></i> Lihat lampiran saat ini
                        </a>
                    </div>
                    @endif
                    <input type="file" name="attachment" accept="image/*,.pdf"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Max 2MB. Format: JPG, PNG, PDF. Kosongkan jika tidak ingin mengubah.</p>
                    @error('attachment')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.expenses.show', $expense) }}"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
