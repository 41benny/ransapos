@extends('layouts.admin')

@section('title', 'Transfer Bank')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Transfer Bank Antar Rekening</h1>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow">
            <form action="{{ route('admin.bank-transfers.store') }}" method="POST">
                @csrf

                <div class="p-6 space-y-6">
                    <!-- Transfer Date -->
                    <div>
                        <label for="transfer_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Transfer <span class="text-red-500">*</span>
                        </label>
                        <input type="date" id="transfer_date" name="transfer_date"
                            value="{{ old('transfer_date', date('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <!-- From Account -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Dari Rekening <span class="text-red-500">*</span>
                        </label>
                        <select name="from_cash_account_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                            required>
                            <option value="">-- Pilih Rekening Sumber --</option>
                            @foreach($accounts->groupBy('outlet_id') as $outletId => $outletAccounts)
                                <optgroup label="{{ $outletAccounts->first()->outlet->name ?? 'Tanpa Outlet' }}">
                                    @foreach($outletAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->name }} ({{ $account->bank_name ?? 'Kas' }})
                                            - Saldo: Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- To Account -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Ke Rekening <span class="text-red-500">*</span>
                        </label>
                        <select name="to_cash_account_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                            required>
                            <option value="">-- Pilih Rekening Tujuan --</option>
                            @foreach($accounts->groupBy('outlet_id') as $outletId => $outletAccounts)
                                <optgroup label="{{ $outletAccounts->first()->outlet->name ?? 'Tanpa Outlet' }}">
                                    @foreach($outletAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->name }} ({{ $account->bank_name ?? 'Kas' }})
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Jumlah Transfer <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">Rp</span>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg" required>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="description" maxlength="500"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Deskripsi transfer..."
                            required>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan</label>
                        <textarea name="notes" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-3 rounded-b-lg">
                    <a href="{{ route('admin.bank-transfers.index') }}"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                        Proses Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection