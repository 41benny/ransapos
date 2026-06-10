<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $paymentMethodsQuery = PaymentMethod::query()
            ->withCount('payments')
            ->orderByDesc('is_active')
            ->orderBy('name');

        if ($search !== '') {
            $paymentMethodsQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $paymentMethods = $paymentMethodsQuery
            ->paginate(20)
            ->withQueryString();

        return view('admin.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeCode((string) $request->input('code', '')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:payment_methods,code'],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'is_online_only' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_online_only'] = $request->boolean('is_online_only');

        PaymentMethod::create($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', compact('paymentMethod'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeCode((string) $request->input('code', '')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:payment_methods,code,' . $paymentMethod->id],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'is_online_only' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_online_only'] = $request->boolean('is_online_only');

        if ($paymentMethod->code === 'CASH' && $validated['code'] !== 'CASH') {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode CASH tidak bisa diubah karena dipakai sebagai acuan sistem.']);
        }

        if ($paymentMethod->code === 'CASH' && !$validated['is_active']) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => 'Metode CASH wajib aktif.']);
        }

        if (!$validated['is_active']) {
            $otherActiveCount = PaymentMethod::query()
                ->where('is_active', true)
                ->whereKeyNot($paymentMethod->id)
                ->count();

            if ($otherActiveCount === 0) {
                return back()
                    ->withInput()
                    ->withErrors(['is_active' => 'Minimal harus ada satu metode pembayaran aktif.']);
            }
        }

        $paymentMethod->update($validated);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        if ($paymentMethod->code === 'CASH') {
            return back()->with('error', 'Metode CASH tidak dapat dihapus karena wajib untuk alur kas.');
        }

        if ($paymentMethod->payments()->exists()) {
            return back()->with('error', 'Metode pembayaran tidak bisa dihapus karena sudah dipakai transaksi.');
        }

        if ($paymentMethod->is_active) {
            $otherActiveCount = PaymentMethod::query()
                ->where('is_active', true)
                ->whereKeyNot($paymentMethod->id)
                ->count();

            if ($otherActiveCount === 0) {
                return back()->with('error', 'Metode ini tidak bisa dihapus karena akan membuat semua metode menjadi nonaktif.');
            }
        }

        $paymentMethod->delete();

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Metode pembayaran berhasil dihapus.');
    }

    private function normalizeCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }
}
