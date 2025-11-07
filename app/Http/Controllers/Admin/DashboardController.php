<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard admin
     */
    public function index()
    {
        $stats = [
            'total_products' => Product::where('is_active', true)->count(),
            'total_outlets' => Outlet::where('is_active', true)->count(),
            'total_sales_today' => Sale::whereDate('sale_date', today())
                ->where('status', 'completed')
                ->sum('total_amount'),
            'low_stock_items' => Stock::whereColumn('quantity', '<=', 'products.min_stock')
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
