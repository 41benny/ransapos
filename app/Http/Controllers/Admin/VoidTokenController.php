<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VoidToken;
use Illuminate\Http\Request;

class VoidTokenController extends Controller
{
    public function index()
    {
        $tokens = VoidToken::with(['generator', 'usedBy', 'sale', 'outlet'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $outlets = \App\Models\Outlet::where('is_active', true)->get();

        return view('admin.void-tokens.index', compact('tokens', 'outlets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        // Generate random 6 digit numeric token
        do {
            $token = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (VoidToken::where('token', $token)->exists());

        VoidToken::create([
            'token' => $token,
            'generated_by' => auth()->id(),
            'outlet_id' => $request->outlet_id,
        ]);

        return redirect()->route('admin.void-tokens.index')
            ->with('success', "Token Void baru ({$token}) berhasil dibuat untuk outlet terpilih.");
    }
}
