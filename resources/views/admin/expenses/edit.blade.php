@extends('layouts.admin')

@section('title', 'Edit Expense')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Expense</h1>
        <p class="text-gray-600 mt-1">Update expense information - {{ $expense->expense_number }}</p>
    </div>

    <form action="{{ route('admin.expenses.update', $expense) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Expense Information</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Outlet -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Outlet <span class="text-red-500">*</span>
                    </label>
                    <select name="outlet_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Select Outlet --</option>
                        @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ old('outlet_id', $expense->outlet_id) == $outlet->id ? 'selected' : '' }}>
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
                        Category <span class="text-red-500">*</span>
                    </label>
                    <select name="expense_category_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>
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
                        Expense Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('expense_date')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Amount -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Amount <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" value="{{ old('amount', $expense->amount) }}" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    @error('amount')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Method <span class="text-red-500">*</span>
                    </label>
                    <select name="payment_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Select Method --</option>
                        <option value="cash" {{ old('payment_method', $expense->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="transfer" {{ old('payment_method', $expense->payment_method) == 'transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="credit_card" {{ old('payment_method', $expense->payment_method) == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="debit_card" {{ old('payment_method', $expense->payment_method) == 'debit_card' ? 'selected' : '' }}>Debit Card</option>
                        <option value="other" {{ old('payment_method', $expense->payment_method) == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('payment_method')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Cash Account -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cash/Bank Account
                    </label>
                    <select name="cash_account_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">-- Select Account --</option>
                        @foreach($cashAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('cash_account_id', $expense->cash_account_id) == $account->id ? 'selected' : '' }}>
                            {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                        </option>
                        @endforeach
                    </select>
                    @error('cash_account_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reference No -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Reference No. / Invoice No.
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
                        Description <span class="text-red-500">*</span>
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
                        Attachment (Receipt/Proof)
                    </label>
                    @if($expense->attachment_path)
                    <div class="mb-2">
                        <a href="{{ asset('storage/' . $expense->attachment_path) }}" target="_blank"
                           class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                            <i class="fas fa-paperclip mr-2"></i> Current attachment
                        </a>
                    </div>
                    @endif
                    <input type="file" name="attachment" accept="image/*,.pdf"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Max 2MB. Accepted: JPG, PNG, PDF. Leave empty to keep current attachment.</p>
                    @error('attachment')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 mt-6">
                <a href="{{ route('admin.expenses.show', $expense) }}"
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700">
                    Update Expense
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
