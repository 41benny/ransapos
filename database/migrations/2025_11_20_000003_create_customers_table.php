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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 20)->unique()->comment('Format: CUST-XXXXXX');
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->enum('customer_type', ['regular', 'member', 'vip'])->default('regular');
            $table->enum('member_tier', ['bronze', 'silver', 'gold', 'platinum'])->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->decimal('total_spending', 15, 2)->default(0)->comment('Lifetime spending');
            $table->integer('total_transactions')->default(0);
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('notes')->nullable()->comment('Preferences, allergies, etc');
            $table->date('member_since')->nullable();
            $table->date('last_visit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('phone');
            $table->index('customer_type');
            $table->index('member_tier');
            $table->index('is_active');
            $table->index('total_spending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
