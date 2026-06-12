<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keputusan owner: hanya box dimsum dan cup minuman yang dihitung.
 * Hapus item packaging "Box Takoyaki *" beserta mapping-nya.
 */
return new class extends Migration
{
    public function up(): void
    {
        $takoyakiItemIds = DB::table('packaging_items')
            ->where('name', 'like', 'Box Takoyaki%')
            ->pluck('id');

        if ($takoyakiItemIds->isEmpty()) {
            return;
        }

        DB::table('product_packaging_mappings')->whereIn('packaging_item_id', $takoyakiItemIds)->delete();
        // Bersihkan juga jejak di opening/closing/adjustment bila ada (biasanya kosong).
        DB::table('cash_session_packaging_openings')->whereIn('packaging_item_id', $takoyakiItemIds)->delete();
        DB::table('cash_session_packaging_closings')->whereIn('packaging_item_id', $takoyakiItemIds)->delete();
        DB::table('packaging_adjustments')->whereIn('packaging_item_id', $takoyakiItemIds)->delete();
        DB::table('packaging_items')->whereIn('id', $takoyakiItemIds)->delete();
    }

    public function down(): void
    {
        // Tidak di-restore otomatis; jalankan ulang seeder bila perlu.
    }
};
