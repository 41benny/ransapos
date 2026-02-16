<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoaAccountRequest;
use App\Http\Requests\UpdateCoaAccountRequest;
use App\Models\CoaAccount;
use Illuminate\Http\Request;

class CoaAccountController extends Controller
{
    /**
     * Template akun dasar untuk laporan neraca.
     *
     * @return array<int, array<string, string|bool>>
     */
    private function balanceSheetTemplate(): array
    {
        return [
            ['code' => '1-100', 'name' => 'Kas', 'type' => 'asset', 'group' => 'ASET LANCAR', 'is_active' => true, 'notes' => 'Kas tunai operasional'],
            ['code' => '1-110', 'name' => 'Bank', 'type' => 'asset', 'group' => 'ASET LANCAR', 'is_active' => true, 'notes' => 'Saldo rekening bank'],
            ['code' => '1-120', 'name' => 'Piutang Usaha', 'type' => 'asset', 'group' => 'ASET LANCAR', 'is_active' => true, 'notes' => 'Tagihan ke pelanggan'],
            ['code' => '1-130', 'name' => 'Persediaan', 'type' => 'asset', 'group' => 'ASET LANCAR', 'is_active' => true, 'notes' => 'Nilai stok bahan/produk'],
            ['code' => '1-190', 'name' => 'Ayat Silang Kas/Bank (Pindah Buku)', 'type' => 'asset', 'group' => 'ASET LANCAR', 'is_active' => true, 'notes' => 'Pos sementara untuk transfer antar akun kas/bank'],
            ['code' => '1-200', 'name' => 'Aset Tetap', 'type' => 'asset', 'group' => 'ASET TETAP', 'is_active' => true, 'notes' => 'Peralatan, mesin, inventaris'],
            ['code' => '2-100', 'name' => 'Utang Usaha', 'type' => 'liability', 'group' => 'KEWAJIBAN LANCAR', 'is_active' => true, 'notes' => 'Tagihan ke supplier'],
            ['code' => '2-110', 'name' => 'Utang Pajak', 'type' => 'liability', 'group' => 'KEWAJIBAN LANCAR', 'is_active' => true, 'notes' => 'Kewajiban pajak berjalan'],
            ['code' => '2-200', 'name' => 'Utang Jangka Panjang', 'type' => 'liability', 'group' => 'KEWAJIBAN JANGKA PANJANG', 'is_active' => true, 'notes' => 'Pinjaman > 1 tahun'],
            ['code' => '3-100', 'name' => 'Modal Disetor', 'type' => 'equity', 'group' => 'EKUITAS', 'is_active' => true, 'notes' => 'Setoran modal pemilik'],
            ['code' => '3-200', 'name' => 'Laba Ditahan', 'type' => 'equity', 'group' => 'EKUITAS', 'is_active' => true, 'notes' => 'Akumulasi laba bersih'],
        ];
    }

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

    /**
     * Generate template akun neraca (idempotent).
     */
    public function generateBalanceTemplate()
    {
        try {
            $created = 0;

            foreach ($this->balanceSheetTemplate() as $account) {
                $exists = CoaAccount::where('code', $account['code'])->exists();
                if ($exists) {
                    continue;
                }

                CoaAccount::create($account);
                $created++;
            }

            return redirect()
                ->route('admin.coa-accounts.index')
                ->with('success', $created > 0
                    ? "Template neraca berhasil dibuat ({$created} akun baru)."
                    : 'Template neraca sudah tersedia, tidak ada akun baru yang ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat template neraca: ' . $e->getMessage());
        }
    }
}
