<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class KitchenController extends Controller
{
    /**
     * Simple kitchen display for today's orders.
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $query = Sale::with(['items' => function ($q) {
                $q->orderBy('id');
            }])
            ->whereDate('sale_date', $date)
            ->where('status', 'completed')
            ->orderByDesc('created_at');

        // Filter by outlet (default: outlet kasir yang login)
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->integer('outlet_id'));
        } elseif (auth()->check() && auth()->user()->outlet_id) {
            $query->where('outlet_id', auth()->user()->outlet_id);
        }

        $sales = $query->limit(50)->get();

        return view('pos.kitchen.index', [
            'sales' => $sales,
            'date' => $date,
        ]);
    }

    /**
     * Update simple kitchen status for an order.
     */
    public function updateStatus(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'kitchen_status' => 'required|in:new,in_progress,done',
        ]);

        $sale->update([
            'kitchen_status' => $validated['kitchen_status'],
        ]);

        return back();
    }

    /**
     * Printable ticket for a single order.
     */
    public function print(Sale $sale)
    {
        $sale->load(['items', 'outlet', 'user']);

        return view('pos.kitchen.print', compact('sale'));
    }
}
