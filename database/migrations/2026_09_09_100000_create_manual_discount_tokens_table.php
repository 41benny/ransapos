<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_discount_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 6)->unique();
            $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
            $table->boolean('is_used')->default(false);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_for_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['outlet_id', 'is_used']);
        });

        Schema::table('sale_manual_discounts', function (Blueprint $table): void {
            $table->foreignId('manual_discount_token_id')
                ->nullable()
                ->after('authorized_by_user_id')
                ->constrained('manual_discount_tokens')
                ->nullOnDelete();
        });

        $now = now();
        $permissions = [
            [
                'key' => 'manual-discount-tokens.view',
                'module' => 'manual-discount-tokens',
                'action' => 'view',
                'label' => 'Lihat Token Diskon dPOS',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'manual-discount-tokens.create',
                'module' => 'manual-discount-tokens',
                'action' => 'create',
                'label' => 'Buat Token Diskon dPOS',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('permissions')->insertOrIgnore($permissions);

        $roleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin'])
            ->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('key', ['manual-discount-tokens.view', 'manual-discount-tokens.create'])
            ->pluck('id');

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                $pivotRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($pivotRows !== []) {
            DB::table('role_permissions')->insertOrIgnore($pivotRows);
        }
    }

    public function down(): void
    {
        Schema::table('sale_manual_discounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manual_discount_token_id');
        });

        Schema::dropIfExists('manual_discount_tokens');

        DB::table('permissions')
            ->whereIn('key', ['manual-discount-tokens.view', 'manual-discount-tokens.create'])
            ->delete();
    }
};
