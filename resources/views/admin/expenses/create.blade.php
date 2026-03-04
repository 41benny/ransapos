@extends('layouts.admin')

@section('title', 'Request Expense')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Request Expense</h1>
        <p class="text-gray-600 mt-1">Buat pengajuan biaya baru untuk approval</p>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle mt-0.5 mr-2"></i>
            <div>
                <p class="font-medium">Catatan Flow:</p>
                <p class="text-sm">Pengajuan akan menunggu approval. Setelah di-approve, pelunasan dilakukan via menu <strong>Kas & Bank → Transaksi</strong>.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.expenses.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Pengajuan</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Outlet -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Outlet <span class="text-red-500">*</span>
                            </label>
                            <select name="outlet_id" required
                                    class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Pilih Outlet --</option>
                                @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                    {{ $outlet->name }}
                                </option>
                                @endforeach
                            </select>
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
                                    class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('expense_category_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Expense Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tanggal Pengajuan <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required
                                   class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            @error('expense_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Jumlah (Rp) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="amount" value="{{ old('amount') }}" required min="0" step="0.01"
                                   class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="0">
                            @error('amount')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reference No -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                No. Referensi / Invoice (opsional)
                            </label>
                            <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                                   class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   placeholder="Contoh: INV-2024-001">
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
                                      class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                      placeholder="Jelaskan keperluan biaya...">{{ old('description') }}</textarea>
                            @error('description')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Attachment -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Lampiran (Bukti/Kwitansi)
                            </label>
                            <input type="file" name="attachment" accept="image/*,.pdf"
                                   class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Max 2MB. Format: JPG, PNG, PDF</p>
                            @error('attachment')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Status</h3>

                    <!-- Status Info -->
                    <div class="mb-6">
                        <div class="flex items-center gap-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Pending
                            </span>
                            <span class="text-sm text-yellow-700">Menunggu approval</span>
                        </div>
                        <input type="hidden" name="status" value="pending">
                    </div>

                    <div class="border-t pt-4">
                        <p class="text-xs text-gray-500 mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Pengajuan akan dikirim ke atasan untuk approval. Setelah di-approve, lakukan pelunasan via Kas & Bank.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-xl shadow-md p-6 mt-6">
                    <div class="space-y-3">
                        <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 font-semibold">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Pengajuan
                        </button>
                        <a href="{{ route('admin.expenses.index') }}"
                           class="block w-full px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-center">
                            Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
