@extends('layouts.admin')

@section('title', 'Request Expense')

@section('content')
<div class="page-fullwidth px-0">
<div class="px-6 py-6 page-card-fill">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Request Expense</h1>
            <p class="text-gray-600 mt-1">Kelola pengajuan biaya</p>
        </div>
        <a href="{{ route('admin.expenses.create') }}"
           class="ui-btn ui-btn-primary px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 shadow-md transition">
            <i class="fas fa-plus mr-2"></i> Buat Pengajuan
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

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total Expenses</p>
                    <h3 class="text-2xl font-bold mt-1">Rp {{ number_format($statistics['total_expenses'], 0, ',', '.') }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">Pending</p>
                    <h3 class="text-2xl font-bold mt-1">{{ $statistics['pending_count'] }}</h3>
                    <p class="text-xs text-yellow-100 mt-1">Rp {{ number_format($statistics['total_pending'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Approved</p>
                    <h3 class="text-2xl font-bold mt-1">{{ $statistics['approved_count'] }}</h3>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Paid</p>
                    <h3 class="text-2xl font-bold mt-1">{{ $statistics['paid_count'] }}</h3>
                    <p class="text-xs text-purple-100 mt-1">Rp {{ number_format($statistics['total_paid'], 0, ',', '.') }}</p>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-3">
                    <i class="fas fa-money-check text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="ui-card bg-white rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.expenses.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Outlet</label>
                    <select name="outlet_id" class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ request('outlet_id') == $outlet->id ? 'selected' : '' }}>
                            {{ $outlet->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->full_name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-4">
                <a href="{{ route('admin.expenses.index') }}"
                   class="ui-btn ui-btn-ghost px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Reset
                </a>
                <button type="submit"
                        class="ui-btn ui-btn-primary px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Expenses Table -->
    <div class="ui-card bg-white rounded-xl shadow-md overflow-hidden">
        <table class="ui-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outlet</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($expenses as $expense)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-blue-600">{{ $expense->expense_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $expense->expense_date->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $expense->outlet->name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $expense->category->full_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ Str::limit($expense->description, 40) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                        Rp {{ number_format($expense->amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($expense->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($expense->status == 'approved') bg-green-100 text-green-800
                            @elseif($expense->status == 'rejected') bg-red-100 text-red-800
                            @elseif($expense->status == 'paid') bg-blue-100 text-blue-800
                            @endif">
                            {{ $expense->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.expenses.show', $expense) }}"
                               class="ui-action-icon ui-action-view">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($expense->isPending())
                            <a href="{{ route('admin.expenses.edit', $expense) }}"
                               class="ui-action-icon ui-action-edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.expenses.destroy', $expense) }}"
                                  method="POST" class="inline-block"
                                  onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ui-action-icon ui-action-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>No expenses found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 bg-gray-50">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
</div>
@endsection
