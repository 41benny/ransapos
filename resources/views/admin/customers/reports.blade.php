@extends('layouts.admin')

@section('title', 'Customer Reports')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md mb-6 p-6">
        <div class="flex justify-between items-center">
            <div>
                <h5 class="text-xl font-bold text-gray-800">Customer Reports</h5>
                <p class="text-sm text-gray-500 mt-1">Analyze customer behavior and trends</p>
            </div>
            <a href="{{ route('admin.customers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Total Customers</p>
                    <h6 class="text-lg font-bold">{{ number_format($statistics['total_customers']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-user-plus text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">New This Month</p>
                    <h6 class="text-lg font-bold">{{ number_format($statistics['new_this_month']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-heartbeat text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Active Customers</p>
                    <h6 class="text-lg font-bold">{{ number_format($statistics['active_customers']) }}</h6>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-md p-6 text-white">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-30 rounded-full p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs opacity-90 mb-1">Total LTV</p>
                    <h6 class="text-lg font-bold">Rp {{ number_format($statistics['total_lifetime_value']) }}</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Top Customers -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="border-b border-gray-200 px-6 py-4">
                <h6 class="text-lg font-semibold text-gray-800">Top 10 Customers by Spending</h6>
            </div>
            <div class="p-6">
                <canvas id="topCustomersChart" height="300"></canvas>
            </div>
        </div>

        <!-- Customer Type Distribution -->
        <div class="bg-white rounded-xl shadow-md">
            <div class="border-b border-gray-200 px-6 py-4">
                <h6 class="text-lg font-semibold text-gray-800">Customer Type Distribution</h6>
            </div>
            <div class="p-6">
                <canvas id="typeDistributionChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Customer Acquisition Trend -->
    <div class="bg-white rounded-xl shadow-md mb-6">
        <div class="border-b border-gray-200 px-6 py-4">
            <h6 class="text-lg font-semibold text-gray-800">Customer Acquisition Trend (Last 12 Months)</h6>
        </div>
        <div class="p-6">
            <canvas id="acquisitionTrendChart" height="100"></canvas>
        </div>
    </div>

    <!-- Top Customers Table -->
    <div class="bg-white rounded-xl shadow-md">
        <div class="border-b border-gray-200 px-6 py-4">
            <h6 class="text-lg font-semibold text-gray-800">Top Customers Details</h6>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spending</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transactions</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Transaction</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($topCustomers as $index => $customer)
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                    {{ $customer->name }}
                                </a>
                                <p class="text-xs text-gray-500">{{ $customer->customer_code }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-1 rounded text-xs font-medium text-white" style="background-color: {{ $customer->type_badge }};">
                                    {{ ucfirst($customer->customer_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($customer->member_tier)
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium text-white" style="background-color: {{ $customer->tier_badge }};">
                                        {{ ucfirst($customer->member_tier) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-gray-900">Rp {{ number_format($customer->total_spending) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $customer->total_transactions }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">Rp {{ number_format($customer->average_transaction) }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-purple-700">{{ number_format($customer->loyalty_points) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Customers Chart
    const topCustomersCtx = document.getElementById('topCustomersChart').getContext('2d');
    new Chart(topCustomersCtx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($topCustomers->pluck('name')->toArray()) !!},
            datasets: [{
                label: 'Total Spending (Rp)',
                data: {!! json_encode($topCustomers->pluck('total_spending')->toArray()) !!},
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Customer Type Distribution Chart
    const typeDistributionCtx = document.getElementById('typeDistributionChart').getContext('2d');
    new Chart(typeDistributionCtx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($typeDistribution->pluck('customer_type')->map(function ($t) { return ucfirst($t); })->toArray()) !!},
            datasets: [{
                data: {!! json_encode($typeDistribution->pluck('total')->toArray()) !!},
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Customer Acquisition Trend Chart
    const acquisitionTrendCtx = document.getElementById('acquisitionTrendChart').getContext('2d');
    new Chart(acquisitionTrendCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($acquisitionTrend->pluck('month')->toArray()) !!},
            datasets: [{
                label: 'New Customers',
                data: {!! json_encode($acquisitionTrend->pluck('total')->toArray()) !!},
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>
@endpush
