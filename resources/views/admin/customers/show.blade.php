@extends('layouts.admin')

@section('title', 'Customer Detail')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md mb-6 p-6">
        <div class="flex justify-between items-center">
            <div>
                <h5 class="text-xl font-bold text-gray-800 mb-1">{{ $customer->name }}</h5>
                <p class="text-sm text-gray-500">{{ $customer->customer_code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <a href="{{ route('admin.customers.edit', $customer) }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm font-medium rounded-lg transition-all duration-150">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Total Spending</p>
                    <h6 class="text-lg font-bold">Rp {{ number_format($stats['total_spending']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-shopping-cart text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Total Transactions</p>
                    <h6 class="text-lg font-bold">{{ number_format($stats['total_transactions']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Avg Transaction</p>
                    <h6 class="text-lg font-bold">Rp {{ number_format($stats['average_transaction']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-star text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Loyalty Points</p>
                    <h6 class="text-lg font-bold">{{ number_format($stats['loyalty_points']) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Information -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-md h-full">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h6 class="text-lg font-semibold text-gray-800">Customer Information</h6>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Customer Type</p>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium text-white" style="background-color: {{ $customer->type_badge }};">
                            {{ ucfirst($customer->customer_type) }}
                        </span>
                    </div>

                    @if($customer->member_tier)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Member Tier</p>
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium text-white" style="background-color: {{ $customer->tier_badge }};">
                            {{ ucfirst($customer->member_tier) }}
                        </span>
                    </div>
                    @endif

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Phone</p>
                        <p class="text-sm text-gray-700"><i class="fas fa-phone mr-2 text-blue-500"></i>{{ $customer->phone }}</p>
                    </div>

                    @if($customer->email)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Email</p>
                        <p class="text-sm text-gray-700"><i class="fas fa-envelope mr-2 text-blue-500"></i>{{ $customer->email }}</p>
                    </div>
                    @endif

                    @if($customer->address)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Address</p>
                        <p class="text-sm text-gray-700"><i class="fas fa-map-marker-alt mr-2 text-red-500"></i>{{ $customer->address }}</p>
                    </div>
                    @endif

                    @if($customer->birth_date)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Birth Date</p>
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-birthday-cake mr-2 text-pink-500"></i>
                            {{ $customer->birth_date->format('d M Y') }}
                            @if($customer->age)
                                ({{ $customer->age }} years old)
                            @endif
                        </p>
                    </div>
                    @endif

                    @if($customer->gender)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Gender</p>
                        <p class="text-sm text-gray-700">{{ ucfirst($customer->gender) }}</p>
                    </div>
                    @endif

                    @if($customer->member_since)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Member Since</p>
                        <p class="text-sm text-gray-700">{{ $customer->member_since->format('d M Y') }}</p>
                    </div>
                    @endif

                    @if($stats['last_visit'])
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Last Visit</p>
                        <p class="text-sm text-gray-700">{{ $stats['last_visit']->format('d M Y H:i') }}</p>
                    </div>
                    @endif

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Status</p>
                        @if($customer->is_active)
                            <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Active</span>
                        @else
                            <span class="inline-block px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">Inactive</span>
                        @endif
                    </div>

                    @if($customer->notes)
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Notes</p>
                        <p class="text-sm text-gray-700">{{ $customer->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Loyalty Points & Transactions -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Loyalty Points Management -->
            <div class="bg-white rounded-xl shadow-md">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h6 class="text-lg font-semibold text-gray-800">Loyalty Points Management</h6>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-purple-600 mb-1">Current Points</p>
                            <h4 class="text-2xl font-bold text-purple-700">{{ number_format($customer->loyalty_points) }}</h4>
                        </div>
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-center">
                            <p class="text-xs text-amber-600 mb-1">Current Tier</p>
                            <h6 class="text-lg font-semibold mt-1">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium text-white" style="background-color: {{ $customer->tier_badge }};">
                                    {{ ucfirst($customer->member_tier ?? 'Bronze') }}
                                </span>
                            </h6>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <form action="{{ route('admin.customers.add-points', $customer) }}" method="POST">
                            @csrf
                            <div class="flex gap-2">
                                <input type="number" name="points" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Points to add" min="1" required>
                                <button type="submit" class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-150 flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Add
                                </button>
                            </div>
                        </form>
                        <form action="{{ route('admin.customers.redeem-points', $customer) }}" method="POST">
                            @csrf
                            <div class="flex gap-2">
                                <input type="number" name="points" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="Points to redeem" min="1" required>
                                <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors duration-150 flex items-center">
                                    <i class="fas fa-gift mr-2"></i>Redeem
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-xl shadow-md">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h6 class="text-lg font-semibold text-gray-800">Recent Transactions</h6>
                </div>
                <div class="p-6">
                    @if($customer->sales->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outlet</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($customer->sales as $sale)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-4 py-3 text-sm text-blue-600 hover:text-blue-800 font-medium">{{ $sale->invoice_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->outlet->name }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Rp {{ number_format($sale->total_amount) }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="inline-block px-2 py-1 bg-cyan-100 text-cyan-700 rounded text-xs font-medium">+{{ number_format($sale->loyalty_points_earned ?? 0) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-sm text-gray-500 text-center py-8">No transactions yet</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
