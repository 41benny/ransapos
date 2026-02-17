<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('logged_in_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('cascade');
            // Explicit default avoids MariaDB/MySQL implicit ON UPDATE behavior.
            $table->timestamp('clock_in')->useCurrent();
            $table->timestamp('clock_out')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['present', 'late', 'absent'])->default('present');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Indexes untuk performa query
            $table->index(['user_id', 'clock_in']);
            $table->index(['outlet_id', 'clock_in']);
            $table->index('logged_in_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
