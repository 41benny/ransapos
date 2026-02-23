<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ambil COA Hutang Usaha (2-100)
        $hutangCoa = DB::table('coa_accounts')->where('code', '2-100')->first();
        if (!$hutangCoa) {
            // Jika belum ada, cari berdasarkan nama atau group sebagai fallback
            $hutangCoa = DB::table('coa_accounts')
                ->where('name', 'like', '%Hutang Usaha%')
                ->orWhere('group', 'HUTANG')
                ->first();
        }

        if (!$hutangCoa) {
            return;
        }

        // 2. Ambil semua transaksi yang bertipe purchase
        $transactions = DB::table('cash_transactions')
            ->where('reference_type', 'purchase')
            ->get();

        foreach ($transactions as $t) {
            // Ambil data purchase untuk mendapatkan nomor PO dan supplier_id
            $purchase = DB::table('purchases')->where('id', $t->reference_id)->first();
            if (!$purchase) continue;

            // Ambil nama supplier
            $supplierName = '-';
            if ($purchase->supplier_id) {
                $supplier = DB::table('suppliers')->where('id', $purchase->supplier_id)->first();
                if ($supplier) {
                    $supplierName = $supplier->name;
                }
            }
            
            $poNumber = $purchase->purchase_number;
            $poDate = date('d/m/Y', strtotime($purchase->purchase_date));

            $newDesc = "Pembayaran hutang supplier {$supplierName}, no po {$poNumber} tgl {$poDate}";

            // Update COA dan deskripsi hanya jika keterangannya masih format lama atau kosong
            // Format lama biasanya: "Pembayaran Purchase #..." atau "Pembayaran Hutang"
            if (empty($t->description) || 
                str_contains($t->description, 'Pembayaran Purchase') || 
                $t->description == 'Pembayaran Hutang') {
                
                DB::table('cash_transactions')
                    ->where('id', $t->id)
                    ->update([
                        'coa_account_id' => $hutangCoa->id,
                        'description' => $newDesc
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data fix migration usually doesn't need reverse logic
    }
};
