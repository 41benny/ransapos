@extends('layouts.admin')

@section('title', 'Tambah Akun Kas/Bank')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.cash-accounts.index') }}" class="hover:text-indigo-600">Kas & Bank</a>
            <span>/</span>
            <span class="text-gray-900">Tambah Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Tambah Akun Kas/Bank Baru</h1>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('admin.cash-accounts.store') }}" method="POST">
            @csrf

            <div class="p-6 space-y-6">
                <!-- Nama Akun -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           placeholder="Contoh: Kas Toko, Bank BCA, Bank BRI"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kode Akun -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                        Kode Akun <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="code" 
                           name="code" 
                           value="{{ old('code') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('code') border-red-500 @enderror"
                           placeholder="Contoh: KAS-001, BANK-BCA"
                           required>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Kode unik per outlet untuk identifikasi akun</p>
                </div>

                <!-- Outlet -->
                <div>
                    <label for="outlet_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Outlet <span class="text-red-500">*</span>
                    </label>
                    <select id="outlet_id" name="outlet_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('outlet_id') border-red-500 @enderror"
                            required>
                        <option value="">-- Pilih Outlet --</option>
                        @foreach(\App\Models\Outlet::active()->orderBy('name')->get() as $outlet)
                            <option value="{{ $outlet->id }}" {{ old('outlet_id') == $outlet->id ? 'selected' : '' }}>
                                {{ $outlet->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('outlet_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Akun -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Akun <span class="text-red-500">*</span>
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                            required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="cash" {{ old('type') == 'cash' ? 'selected' : '' }}>Kas Tunai</option>
                        <option value="bank" {{ old('type') == 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tipe Penggunaan Akun -->
                <div>
                    <label for="usage_type" class="block text-sm font-medium text-gray-700 mb-2">
                        Tipe Penggunaan <span class="text-red-500">*</span>
                    </label>
                    <select id="usage_type"
                            name="usage_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('usage_type') border-red-500 @enderror"
                            required>
                        <option value="operational" {{ old('usage_type', 'operational') == 'operational' ? 'selected' : '' }}>Operasional Umum</option>
                        <option value="petty_cash" {{ old('usage_type') == 'petty_cash' ? 'selected' : '' }}>Petty Cash Outlet</option>
                    </select>
                    @error('usage_type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Pilih <strong>Petty Cash Outlet</strong> jika akun ini dipakai untuk pengeluaran kas kecil POS.</p>
                </div>

                <!-- Bank Details (Conditional) -->
                <div id="bank-details" style="display:none;" class="space-y-4 pl-4 border-l-2 border-indigo-200">
                    <div>
                        <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Nama Bank</label>
                        <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                               placeholder="Contoh: BCA, Mandiri, BRI">
                    </div>
                    <div>
                        <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Nomor Rekening</label>
                        <input type="text" id="account_number" name="account_number" value="{{ old('account_number') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="account_holder" class="block text-sm font-medium text-gray-700 mb-2">Nama Pemegang Rekening</label>
                        <input type="text" id="account_holder" name="account_holder" value="{{ old('account_holder') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Cabang Bank (Opsional)</label>
                        <input type="text" id="branch" name="branch" value="{{ old('branch') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>

                <!-- Saldo Awal -->
                <div>
                    <label for="opening_balance" class="block text-sm font-medium text-gray-700 mb-2">
                        Saldo Awal <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                        <input type="number" 
                               id="opening_balance" 
                               name="opening_balance" 
                               value="{{ old('opening_balance', 0) }}"
                               step="0.01"
                               min="0"
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('opening_balance') border-red-500 @enderror"
                               required>
                    </div>
                    @error('opening_balance')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Saldo awal akun saat dibuat</p>
                </div>

                <!-- Status Aktif -->
                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Akun Aktif</span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500 ml-6">Centang jika akun ini aktif dan bisa digunakan</p>
                </div>

                <!-- Catatan -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                        Catatan
                    </label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('notes') border-red-500 @enderror"
                              placeholder="Catatan tambahan tentang akun ini (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                <a href="{{ route('admin.cash-accounts.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                    Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const bankDetails = document.getElementById('bank-details');
    if (this.value === 'bank') {
        bankDetails.style.display = 'block';
    } else {
        bankDetails.style.display = 'none';
    }
});

// Trigger on page load untuk old() values
if (document.getElementById('type').value === 'bank') {
    document.getElementById('bank-details').style.display = 'block';
}
</script>
@endsection

