@extends('layouts.admin')

@section('title', 'Expense Detail')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Expense Detail</h1>
            <p class="text-gray-600 mt-1">{{ $expense->expense_number }}</p>
        </div>
        <a href="{{ route('admin.expenses.index') }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Expense Information -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-purple-500 mr-2"></i> Expense Information
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Expense Number</p>
                        <p class="font-semibold text-gray-900">{{ $expense->expense_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Expense Date</p>
                        <p class="font-semibold text-gray-900">{{ $expense->expense_date->format('d F Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Outlet</p>
                        <p class="font-semibold text-gray-900">{{ $expense->outlet->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Category</p>
                        <p class="font-semibold text-gray-900">{{ $expense->category->full_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Amount</p>
                        <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Payment Method</p>
                        <p class="font-semibold text-gray-900 capitalize">{{ str_replace('_', ' ', $expense->payment_method) }}</p>
                    </div>
                    @if($expense->cash_account_id)
                    <div>
                        <p class="text-sm text-gray-500">Cash/Bank Account</p>
                        <p class="font-semibold text-gray-900">{{ $expense->cashAccount->name }}</p>
                    </div>
                    @endif
                    @if($expense->reference_no)
                    <div>
                        <p class="text-sm text-gray-500">Reference No.</p>
                        <p class="font-semibold text-gray-900">{{ $expense->reference_no }}</p>
                    </div>
                    @endif
                </div>

                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-2">Description</p>
                    <p class="text-gray-900">{{ $expense->description }}</p>
                </div>

                @if($expense->attachment_path)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-2">Attachment</p>
                    <a href="{{ asset('storage/' . $expense->attachment_path) }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-paperclip mr-2"></i> View Attachment
                    </a>
                </div>
                @endif
            </div>

            <!-- Approval Information -->
            @if($expense->approved_at)
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i> Approval Information
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Approved/Rejected By</p>
                        <p class="font-semibold text-gray-900">{{ $expense->approver->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Approval Date</p>
                        <p class="font-semibold text-gray-900">{{ $expense->approved_at->format('d F Y H:i') }}</p>
                    </div>
                </div>

                @if($expense->approval_notes)
                <div class="mt-4 pt-4 border-t">
                    <p class="text-sm text-gray-500 mb-2">Approval Notes</p>
                    <p class="text-gray-900">{{ $expense->approval_notes }}</p>
                </div>
                @endif
            </div>
            @endif

            <!-- Created By -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user text-blue-500 mr-2"></i> Record Information
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Created By</p>
                        <p class="font-semibold text-gray-900">{{ $expense->creator->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Created At</p>
                        <p class="font-semibold text-gray-900">{{ $expense->created_at->format('d F Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Status Card -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Status</h3>

                <div class="text-center mb-4">
                    <span class="px-4 py-2 text-sm font-semibold rounded-full
                        @if($expense->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($expense->status == 'approved') bg-green-100 text-green-800
                        @elseif($expense->status == 'rejected') bg-red-100 text-red-800
                        @elseif($expense->status == 'paid') bg-blue-100 text-blue-800
                        @endif">
                        {{ $expense->status_label }}
                    </span>
                </div>

                @if($expense->isPending())
                <!-- Approval Actions -->
                <div class="space-y-3 border-t pt-4">
                    <form action="{{ route('admin.expenses.approve', $expense) }}" method="POST">
                        @csrf
                        <textarea name="approval_notes" rows="2"
                                  class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2"
                                  placeholder="Approval notes (optional)"></textarea>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            <i class="fas fa-check mr-2"></i> Approve
                        </button>
                    </form>

                    <form action="{{ route('admin.expenses.reject', $expense) }}" method="POST">
                        @csrf
                        <textarea name="approval_notes" rows="2" required
                                  class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2"
                                  placeholder="Rejection reason (required)"></textarea>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                            <i class="fas fa-times mr-2"></i> Reject
                        </button>
                    </form>
                </div>
                @endif

                @if($expense->isApproved())
                <!-- Payment Action -->
                <div class="border-t pt-4">
                    <form action="{{ route('admin.expenses.pay', $expense) }}" method="POST">
                        @csrf
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cash/Bank Account</label>
                        <select name="cash_account_id" required
                                class="ui-input w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-2">
                            <option value="">-- Select Account --</option>
                            @php
                            $cashAccounts = \App\Models\CashAccount::active()->get();
                            @endphp
                            @foreach($cashAccounts as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->name }} (Rp {{ number_format($account->balance, 0, ',', '.') }})
                            </option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            <i class="fas fa-money-check mr-2"></i> Mark as Paid
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <!-- Actions Card -->
            @if($expense->isPending())
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Actions</h3>

                <div class="space-y-3">
                    <a href="{{ route('admin.expenses.edit', $expense) }}"
                       class="block w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-center">
                        <i class="fas fa-edit mr-2"></i> Edit Expense
                    </a>

                    <form action="{{ route('admin.expenses.destroy', $expense) }}"
                          method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this expense?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                            <i class="fas fa-trash mr-2"></i> Delete Expense
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
