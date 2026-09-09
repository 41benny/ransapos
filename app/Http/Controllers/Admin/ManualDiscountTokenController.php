<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ManualDiscountToken;
use App\Models\Outlet;
use Illuminate\Http\Request;

class ManualDiscountTokenController extends Controller
{
    public function index()
    {
        $tokens = ManualDiscountToken::with(['generator', 'usedBy', 'sale', 'outlet'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $outlets = Outlet::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.manual-discount-tokens.index', compact('tokens', 'outlets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
        ]);

        $outlet = Outlet::query()
            ->whereKey($validated['outlet_id'])
            ->where('is_active', true)
            ->firstOrFail();

        do {
            $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (ManualDiscountToken::query()->where('token', $token)->exists());

        ManualDiscountToken::create([
            'token' => $token,
            'outlet_id' => $outlet->id,
            'generated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.manual-discount-tokens.index')
            ->with('success', "Token promo dPOS baru ({$token}) berhasil dibuat untuk {$outlet->name}. Token hanya bisa dipakai satu kali.");
    }
}
