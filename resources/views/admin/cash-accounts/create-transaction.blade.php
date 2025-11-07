@extends('layouts.admin')

@section('title', 'Catat Transaksi Kas/Bank')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center space-x-2 text-sm text-gray-600 mb-2">
            <a href="{{ route('admin.cash-transactions.index') }}" class="hover:text-indigo-600">Transaksi</a>
            <span>/</span>
            <span class="text-gray-900">Catat Baru</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Catat Transaksi Kas/Bank</h1>
        <p class="text-gray-600 mt-1">Catat transaksi kas masuk atau keluar</p>
    </div>

    <!-- Alert Messages -->
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white rounded-lg shadow max-w-2xl">
        <form action="{{ route('admin.cash-transactions.store') }}" method="POST">
            @csrf

            <div class="p-6 space-y-6">
                <!-- Akun Kas/Bank -->
                <div>
                    <label for="cash_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Akun Kas/Bank <span class="text-red-500">*</span>
                    </label>
                    <select id="cash_account_id" 
                            name="cash_account_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('cash_account_id') border-red-500 @enderror"
                            required>
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" {{ old('cash_account_id') == $account->id ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->code }}) - Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </select>
                    @error('cash_account_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Transaksi -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Transaksi <span class="text-red-500">*</span>
                    </label>
                    <select id="type" 
                            name="type" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('type') border-red-500 @enderror"
                            required
                            onchange="toggleCoaField()">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>Kas Masuk</option>
                        <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Kas Keluar</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Akun COA (untuk kas keluar) -->
                <div id="coa-field" style="display: {{ old('type') == 'out' ? 'block' : 'none' }};">
                    <label for="coa_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Akun Biaya (COA) <span class="text-red-500" id="coa-required">*</span>
                    </label>
                    <select id="coa_account_id" 
                            name="coa_account_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('coa_account_id') border-red-500 @enderror">
                        <option value="">-- Pilih Akun Biaya --</option>
                        @foreach(\App\Models\CoaAccount::expense()->active()->orderBy('code')->get() as $coa)
                            <option value="{{ $coa->id }}" {{ old('coa_account_id') == $coa->id ? 'selected' : '' }}>
                                {{ $coa->code }} - {{ $coa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('coa_account_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Wajib dipilih untuk transaksi keluar (biaya operasional)</p>
                </div>

                <!-- Tanggal Transaksi -->
                <div>
                    <label for="transaction_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Tanggal Transaksi <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="transaction_date" 
                           name="transaction_date" 
                           value="{{ old('transaction_date', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('transaction_date') border-red-500 @enderror"
                           required>
                    @error('transaction_date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jumlah -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        Jumlah <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                        <input type="number" 
                               id="amount" 
                               name="amount" 
                               value="{{ old('amount') }}"
                               step="0.01"
                               min="0.01"
                               class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('amount') border-red-500 @enderror"
                               placeholder="0"
                               required>
                    </div>
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Deskripsi <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="description" 
                           name="description" 
                           value="{{ old('description') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                           placeholder="Contoh: Setoran tunai, Penarikan bank, dll"
                           required>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                              placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                <a href="{{ route('admin.cash-transactions.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                    Catat Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCoaField() {
    const type = document.getElementById('type').value;
    const coaField = document.getElementById('coa-field');
    const coaSelect = document.getElementById('coa_account_id');
    
    if (type === 'out') {
        coaField.style.display = 'block';
        coaSelect.required = true;
    } else {
        coaField.style.display = 'none';
        coaSelect.required = false;
        coaSelect.value = '';
    }
}

// Init on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCoaField();
});
</script>
@endsection

