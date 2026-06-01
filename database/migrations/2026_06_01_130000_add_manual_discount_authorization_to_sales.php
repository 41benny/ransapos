<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_source', 40)->nullable()->after('discount_amount');
            $table->foreignId('manual_discount_authorized_by')->nullable()->after('discount_source')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('manual_discount_authorized_at')->nullable()->after('manual_discount_authorized_by');
            $table->string('manual_discount_reason', 255)->nullable()->after('manual_discount_authorized_at');

            $table->index('discount_source');
            $table->index('manual_discount_authorized_by');
        });

        Schema::create('sale_manual_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('cashier_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('authorized_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('discount_amount_applied', 15, 2)->default(0);
            $table->string('source', 40)->default('dpos_authorized');
            $table->string('reason', 255)->nullable();
            $table->timestamp('authorized_at');
            $table->timestamps();

            $table->index(['outlet_id', 'authorized_at']);
            $table->index(['cashier_user_id', 'authorized_at']);
            $table->index(['authorized_by_user_id', 'authorized_at']);
        });

        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_manual_discounts');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['discount_source']);
            $table->dropIndex(['manual_discount_authorized_by']);
            $table->dropConstrainedForeignId('manual_discount_authorized_by');
            $table->dropColumn([
                'discount_source',
                'manual_discount_authorized_at',
                'manual_discount_reason',
            ]);
        });

        DB::table('permissions')
            ->whereIn('key', ['pos.manual-discount.apply', 'pos.manual-discount.authorize'])
            ->delete();
    }

    private function seedPermissions(): void
    {
        $now = now();
        $permissions = [
            [
                'key' => 'pos.manual-discount.apply',
                'module' => 'pos',
                'action' => 'apply_manual_discount',
                'label' => 'POS Apply Diskon Manual',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'pos.manual-discount.authorize',
                'module' => 'pos',
                'action' => 'authorize_manual_discount',
                'label' => 'POS Otorisasi Diskon Manual',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('permissions')->insertOrIgnore($permissions);

        $permissionIds = DB::table('permissions')
            ->whereIn('key', ['pos.manual-discount.apply', 'pos.manual-discount.authorize'])
            ->pluck('id');

        $roleIds = DB::table('roles')
            ->whereIn('name', ['manager', 'admin'])
            ->pluck('id');

        if ($permissionIds->isEmpty() || $roleIds->isEmpty()) {
            return;
        }

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

        DB::table('role_permissions')->insertOrIgnore($pivotRows);
    }
};
