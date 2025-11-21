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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique()->comment('Format: TRF-FROM-TO-YYYYMMDD-XXX');
            $table->foreignId('from_outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->foreignId('to_outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->date('transfer_date');
            $table->enum('status', ['pending', 'in_transit', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            // Tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('transfer_date');
            $table->index('status');
            $table->index(['from_outlet_id', 'transfer_date']);
            $table->index(['to_outlet_id', 'transfer_date']);
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 10, 2);
            $table->decimal('received_quantity', 10, 2)->nullable()->comment('Qty yang diterima (bisa beda jika ada damage)');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Prevent duplicate product in same transfer
            $table->unique(['stock_transfer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
