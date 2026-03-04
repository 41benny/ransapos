@extends('layouts.admin')

@section('title', 'Expense Reports')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Expense Reports</h1>
            <p class="text-gray-600 mt-1">Analyze expenses by category, outlet, and period</p>
        </div>
        <a href="{{ route('admin.expenses.index') }}"
           class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i> Back to Expenses
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <form method="GET" action="{{ route('admin.expenses.reports') }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Outlet</label>
                    <select name="outlet_id" class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Outlets</option>
                        @foreach($outlets as $outlet)
                        <option value="{{ $outlet->id }}" {{ $outletId == $outlet->id ? 'selected' : '' }}>
                            {{ $outlet->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="ui-input w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600">
                        <i class="fas fa-chart-bar mr-2"></i> Generate Report
                    </button>
                </div>
            </div>
        </form>
    </div>

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

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Expense by Category Chart -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Expense by Category</h3>
            <canvas id="categoryChart"></canvas>
        </div>

        <!-- Expense Trend Chart -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Expense Trend (12 Months)</h3>
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    <!-- Expense by Category Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Expense Breakdown by Category</h3>
        </div>
        <table class="ui-table min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php $totalExpense = collect($expenseByCategory)->sum('total_amount'); @endphp
                @forelse($expenseByCategory as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $item['category'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600">
                        {{ $item['total_count'] }} transactions
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                        Rp {{ number_format($item['total_amount'], 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-600">
                        {{ $totalExpense > 0 ? number_format(($item['total_amount'] / $totalExpense) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-chart-pie text-4xl mb-3"></i>
                        <p>No expense data available for the selected period</p>
                    </td>
                </tr>
                @endforelse
                @if(count($expenseByCategory) > 0)
                <tr class="bg-gray-50 font-bold">
                    <td class="px-6 py-4 text-sm text-gray-900">TOTAL</td>
                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                        {{ collect($expenseByCategory)->sum('total_count') }} transactions
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-900">
                        Rp {{ number_format($totalExpense, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-900">100%</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Expense by Category Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryData = @json($expenseByCategory);
const categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(item => item.category),
        datasets: [{
            data: categoryData.map(item => item.total_amount),
            backgroundColor: [
                '#8B5CF6', '#EC4899', '#3B82F6', '#10B981', '#F59E0B',
                '#EF4444', '#6366F1', '#14B8A6', '#F97316', '#6B7280'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    font: { size: 12 },
                    padding: 10,
                    usePointStyle: true
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return label + ': Rp ' + value.toLocaleString('id-ID') + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Expense Trend Chart
const trendCtx = document.getElementById('trendChart').getContext('2d');
const trendData = @json($expenseTrend);
const trendChart = new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleString('id-ID', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Total Expenses',
            data: trendData.map(item => item.total_amount),
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.parsed.y || 0;
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                    }
                }
            }
        }
    }
});
</script>
@endpush
