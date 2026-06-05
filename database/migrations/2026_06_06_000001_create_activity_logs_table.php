<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();      // snapshot nama pelaku
            $table->string('event', 50);                   // created|updated|deleted|login|logout|login_failed
            $table->string('subject_type')->nullable();    // class model (App\Models\Product)
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');                 // teks siap tampil (Indonesia)
            $table->json('properties')->nullable();        // {old: {...}, attributes: {...}}
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamps();

            $table->index(['user_id']);
            $table->index(['event']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
