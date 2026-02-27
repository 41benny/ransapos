<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\CashTransaction;
use App\Models\Purchase;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $transactions = CashTransaction::where('reference_type', 'purchase')
            ->where('description', 'like', 'Pembayaran Purchase #%')
            ->get();

        foreach ($transactions as $transaction) {
            if ($transaction->reference_id) {
                $purchase = Purchase::with('supplier')->find($transaction->reference_id);
                if ($purchase) {
                    $supplierName = $purchase->supplier ? $purchase->supplier->name : '-';
                    $poNumber = $purchase->purchase_number;
                    $poDate = $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-';

                    $newDescription = "Pembayaran hutang supplier {$supplierName}, no po {$poNumber} tgl {$poDate}";

                    DB::table('cash_transactions')
                        ->where('id', $transaction->id)
                        ->update(['description' => $newDescription]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible
    }
};
