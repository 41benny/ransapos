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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->onDelete('cascade');
            $table->foreignId('coa_account_id')->nullable()->constrained('coa_accounts')->onDelete('set null')->comment('Link to Chart of Accounts');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('parent_id');
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique()->comment('Format: EXP-OUTLET-YYYYMMDD-XXX');
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->date('expense_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50)->comment('cash, transfer, credit_card, etc');
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->onDelete('set null')->comment('If paid from cash/bank account');
            $table->string('reference_no', 100)->nullable()->comment('Invoice/receipt number');
            $table->text('description');
            $table->string('attachment_path')->nullable()->comment('Receipt/proof image');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');

            // Approval tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('expense_date');
            $table->index('status');
            $table->index(['outlet_id', 'expense_date']);
            $table->index(['expense_category_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
