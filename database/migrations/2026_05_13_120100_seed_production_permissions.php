<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('permissions')->upsert([
            [
                'key' => 'productions.view',
                'module' => 'productions',
                'action' => 'view',
                'label' => 'Lihat Produksi',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'productions.create',
                'module' => 'productions',
                'action' => 'create',
                'label' => 'Buat Produksi',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['module', 'action', 'label', 'updated_at']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('key', ['productions.view', 'productions.create'])
            ->delete();
    }
};
