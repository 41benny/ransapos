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
            $table->boolean('is_backdated')->default(false)->after('kitchen_status');
            $table->foreignId('backdated_by')->nullable()->after('is_backdated')->constrained('users')->nullOnDelete();
            $table->timestamp('backdated_at')->nullable()->after('backdated_by');
            $table->text('backdate_reason')->nullable()->after('backdated_at');
            $table->string('manual_reference', 100)->nullable()->after('backdate_reason');
            $table->index(['is_backdated', 'manual_reference']);
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->string('session_type', 50)->default('regular')->after('session_number');
            $table->date('business_date')->nullable()->after('session_type');
            $table->index(['session_type', 'business_date']);
        });

        DB::table('permissions')->updateOrInsert(
            ['key' => 'sales.backdate.create'],
            [
                'module' => 'sales',
                'action' => 'backdate_create',
                'label' => 'Input Penjualan Backdate',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where('key', 'sales.backdate.create')->value('id');
        $roleIds = DB::table('roles')->whereIn('name', ['admin', 'manager'])->pluck('id');

        if ($permissionId && $roleIds->isNotEmpty()) {
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', 'sales.backdate.create')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropIndex(['session_type', 'business_date']);
            $table->dropColumn(['session_type', 'business_date']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['is_backdated', 'manual_reference']);
            $table->dropConstrainedForeignId('backdated_by');
            $table->dropColumn(['is_backdated', 'backdated_at', 'backdate_reason', 'manual_reference']);
        });
    }
};
