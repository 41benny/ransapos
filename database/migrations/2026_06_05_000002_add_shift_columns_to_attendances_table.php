<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Semua kolom bersifat aditif & nullable/default agar data absensi lama tetap valid
     * (record tanpa shift hanya tidak memiliki metrik lembur/pulang-cepat).
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('outlet_id')
                ->constrained('shifts')->nullOnDelete();
            // Menit keterlambatan dari jam masuk shift (0 = tepat waktu / lebih awal).
            $table->unsignedInteger('late_minutes')->default(0)->after('status');
            // Menit pulang lebih cepat dari jam selesai shift.
            $table->unsignedInteger('early_leave_minutes')->default(0)->after('late_minutes');
            // Menit lembur (clock-out melewati jam selesai shift).
            $table->unsignedInteger('overtime_minutes')->default(0)->after('early_leave_minutes');
            // Total menit kerja (diisi saat clock-out).
            $table->unsignedInteger('worked_minutes')->nullable()->after('overtime_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn(['late_minutes', 'early_leave_minutes', 'overtime_minutes', 'worked_minutes']);
        });
    }
};
