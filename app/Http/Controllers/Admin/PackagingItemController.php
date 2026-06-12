<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagingItem;
use Illuminate\Http\Request;

class PackagingItemController extends Controller
{
    public function index()
    {
        $items = PackagingItem::ordered()->get();

        return view('admin.packaging-items.index', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:packaging_items,name',
            'unit' => 'required|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        PackagingItem::create([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Item packaging ditambahkan.');
    }

    public function update(Request $request, PackagingItem $packagingItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:packaging_items,name,' . $packagingItem->id,
            'unit' => 'required|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $packagingItem->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Item packaging diperbarui.');
    }
}
