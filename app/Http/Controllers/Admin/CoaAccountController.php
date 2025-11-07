<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoaAccountRequest;
use App\Http\Requests\UpdateCoaAccountRequest;
use App\Models\CoaAccount;
use Illuminate\Http\Request;

class CoaAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = CoaAccount::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by group
        if ($request->filled('group')) {
            $query->where('group', $request->group);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $coaAccounts = $query->orderBy('code')->paginate(20);
        
        // Get groups untuk filter
        $groups = CoaAccount::distinct()->pluck('group');

        return view('admin.coa-accounts.index', compact('coaAccounts', 'groups'));
    }

    public function create()
    {
        return view('admin.coa-accounts.create');
    }

    public function store(StoreCoaAccountRequest $request)
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->has('is_active');
            
            CoaAccount::create($data);

            return redirect()
                ->route('admin.coa-accounts.index')
                ->with('success', 'Akun COA berhasil ditambahkan!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal menambahkan akun: ' . $e->getMessage());
        }
    }

    public function show(CoaAccount $coaAccount)
    {
        $coaAccount->load(['cashTransactions' => function($query) {
            $query->orderBy('transaction_date', 'desc')->limit(10);
        }]);

        // Summary
        $totalTransactions = $coaAccount->cashTransactions()->count();
        $totalAmount = $coaAccount->cashTransactions()->sum('amount');

        return view('admin.coa-accounts.show', compact('coaAccount', 'totalTransactions', 'totalAmount'));
    }

    public function edit(CoaAccount $coaAccount)
    {
        return view('admin.coa-accounts.edit', compact('coaAccount'));
    }

    public function update(UpdateCoaAccountRequest $request, CoaAccount $coaAccount)
    {
        try {
            $data = $request->validated();
            $data['is_active'] = $request->has('is_active');
            
            $coaAccount->update($data);

            return redirect()
                ->route('admin.coa-accounts.index')
                ->with('success', 'Akun COA berhasil diupdate!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal mengupdate akun: ' . $e->getMessage());
        }
    }

    public function destroy(CoaAccount $coaAccount)
    {
        try {
            // Validasi: tidak bisa hapus jika ada transaksi
            if ($coaAccount->cashTransactions()->count() > 0) {
                return back()->with('error', 'Tidak bisa hapus akun yang sudah memiliki transaksi!');
            }

            $coaAccount->delete();

            return redirect()
                ->route('admin.coa-accounts.index')
                ->with('success', 'Akun COA berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus akun: ' . $e->getMessage());
        }
    }
}
