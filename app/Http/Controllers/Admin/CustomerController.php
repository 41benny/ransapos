<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // Filter by tier
        if ($request->filled('member_tier')) {
            $query->where('member_tier', $request->member_tier);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(20);

        // Statistics
        $statistics = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::active()->count(),
            'vip_customers' => Customer::vip()->count(),
            'members' => Customer::members()->count(),
            'total_points' => Customer::sum('loyalty_points'),
        ];

        return view('admin.customers.index', compact('customers', 'statistics'));
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'customer_type' => 'required|in:regular,member,vip',
            'member_tier' => 'nullable|in:bronze,silver,gold,platinum',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Generate customer code
        $validated['customer_code'] = $this->generateCustomerCode();

        // Set member_since if member or vip
        if (in_array($validated['customer_type'], ['member', 'vip'])) {
            $validated['member_since'] = now();
        }

        $validated['is_active'] = $request->has('is_active');

        $customer = Customer::create($validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified customer
     */
    public function show(Customer $customer)
    {
        $customer->load(['sales' => function($query) {
            $query->with('outlet')->latest()->limit(10);
        }]);

        // Statistics
        $stats = [
            'total_spending' => $customer->total_spending,
            'total_transactions' => $customer->total_transactions,
            'average_transaction' => $customer->average_transaction,
            'loyalty_points' => $customer->loyalty_points,
            'last_visit' => $customer->last_visit,
        ];

        // Monthly spending trend (last 6 months)
        $monthlySpending = $customer->sales()
            ->where('created_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('admin.customers.show', compact('customer', 'stats', 'monthlySpending'));
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'customer_type' => 'required|in:regular,member,vip',
            'member_tier' => 'nullable|in:bronze,silver,gold,platinum',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Set member_since if becoming member/vip for first time
        if (in_array($validated['customer_type'], ['member', 'vip']) && !$customer->member_since) {
            $validated['member_since'] = now();
        }

        $validated['is_active'] = $request->has('is_active');

        $customer->update($validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer
     */
    public function destroy(Customer $customer)
    {
        // Check if customer has transactions
        if ($customer->sales()->count() > 0) {
            return back()->with('error', 'Cannot delete customer with transaction history.');
        }

        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Add loyalty points
     */
    public function addPoints(Request $request, Customer $customer)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $customer->addPoints($request->points);

        return back()->with('success', "Added {$request->points} points successfully.");
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(Request $request, Customer $customer)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($customer->redeemPoints($request->points)) {
            return back()->with('success', "Redeemed {$request->points} points successfully.");
        } else {
            return back()->with('error', 'Insufficient points for redemption.');
        }
    }

    /**
     * Customer reports
     */
    public function reports(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Top customers by spending
        $topCustomers = Customer::where('total_spending', '>', 0)
            ->orderBy('total_spending', 'desc')
            ->limit(10)
            ->get();

        // Customer acquisition trend (last 12 months)
        $acquisitionTrend = Customer::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as total')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Customer type distribution
        $typeDistribution = Customer::selectRaw('customer_type, COUNT(*) as total')
            ->groupBy('customer_type')
            ->get();

        // Statistics
        $statistics = [
            'total_customers' => Customer::count(),
            'new_this_month' => Customer::whereMonth('created_at', now()->month)->count(),
            'active_customers' => Customer::where('last_visit', '>=', now()->subDays(30))->count(),
            'total_lifetime_value' => Customer::sum('total_spending'),
        ];

        return view('admin.customers.reports', compact(
            'topCustomers',
            'acquisitionTrend',
            'typeDistribution',
            'statistics',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Generate unique customer code
     */
    private function generateCustomerCode(): string
    {
        $lastCustomer = Customer::orderBy('id', 'desc')->first();
        $number = $lastCustomer ? $lastCustomer->id + 1 : 1;

        return 'CUST-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
