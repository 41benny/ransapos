<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\SalesType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $query = SalesType::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $salesTypes = $query->paginate(20)->withQueryString();

        return view('admin.sales-types.index', [
            'salesTypes' => $salesTypes,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.sales-types.create', [
            'paymentMethods' => $this->paymentMethodOptions(),
        ]);
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
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:sales_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'channel_type' => ['required', 'in:offline,online'],
            'default_payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['default_payment_method_id'] = $validated['default_payment_method_id'] ?: null;

        SalesType::create($validated);

        return redirect()
            ->route('admin.sales-types.index')
            ->with('success', 'Metode penjualan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesType $salesType): View
    {
        return view('admin.sales-types.edit', [
            'salesType' => $salesType,
            'paymentMethods' => $this->paymentMethodOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SalesType $salesType): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeCode((string) $request->input('code', '')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:sales_types,code,' . $salesType->id],
            'name' => ['required', 'string', 'max:100'],
            'channel_type' => ['required', 'in:offline,online'],
            'default_payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['default_payment_method_id'] = $validated['default_payment_method_id'] ?: null;

        // REGULAR adalah acuan offline sistem, paksa tetap offline
        if (strtoupper($salesType->code) === 'REGULAR') {
            $validated['channel_type'] = 'offline';
        }

        // Kode REGULAR tidak boleh diubah
        if (strtoupper($salesType->code) === 'REGULAR' && $validated['code'] !== 'REGULAR') {
            return back()
                ->withInput()
                ->withErrors(['code' => 'Kode REGULAR tidak bisa diubah karena dipakai sebagai acuan sistem.']);
        }

        // REGULAR wajib aktif
        if (strtoupper($salesType->code) === 'REGULAR' && !$validated['is_active']) {
            return back()
                ->withInput()
                ->withErrors(['is_active' => 'Metode penjualan REGULAR wajib aktif.']);
        }

        // Minimal 1 tipe aktif
        if (!$validated['is_active']) {
            $otherActiveCount = SalesType::query()
                ->where('is_active', true)
                ->whereKeyNot($salesType->id)
                ->count();

            if ($otherActiveCount === 0) {
                return back()
                    ->withInput()
                    ->withErrors(['is_active' => 'Minimal harus ada satu metode penjualan aktif.']);
            }
        }

        $salesType->update($validated);

        return redirect()
            ->route('admin.sales-types.index')
            ->with('success', 'Metode penjualan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesType $salesType): RedirectResponse
    {
        // REGULAR tidak boleh dihapus
        if (strtoupper($salesType->code) === 'REGULAR') {
            return back()->with('error', 'Metode penjualan REGULAR tidak dapat dihapus karena wajib untuk sistem.');
        }

        // Cek apakah sudah dipakai di transaksi
        $usedCount = DB::table('sales')
            ->where('sales_type', $salesType->code)
            ->count();

        if ($usedCount > 0) {
            return back()->with('error', "Metode penjualan tidak bisa dihapus karena sudah dipakai di {$usedCount} transaksi.");
        }

        // Minimal 1 tipe aktif
        if ($salesType->is_active) {
            $otherActiveCount = SalesType::query()
                ->where('is_active', true)
                ->whereKeyNot($salesType->id)
                ->count();

            if ($otherActiveCount === 0) {
                return back()->with('error', 'Metode ini tidak bisa dihapus karena akan membuat semua metode menjadi nonaktif.');
            }
        }

        $salesType->delete();

        return redirect()
            ->route('admin.sales-types.index')
            ->with('success', 'Metode penjualan berhasil dihapus.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, PaymentMethod>
     */
    private function paymentMethodOptions()
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    private function normalizeCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }
}
