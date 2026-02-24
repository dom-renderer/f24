<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->date('txn_date');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 12, 2);
            $table->date('due_date')->nullable();

            // Source types: 
            // order = Dispatch
            // payment = Manual Payment
            // sales_return = Grievance/Return
            // adjustment = Manual Adjustment
            // opening_balance = OB
            // order_payment_log = COD/Driver Collection
            $table->enum('source_type', ['order', 'payment', 'sales_return', 'adjustment', 'opening_balance', 'order_payment_log']);

            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable(); // Explicit link for easier querying
            $table->unsignedBigInteger('payment_id')->nullable(); // Explicit link for easier querying
            // For sales_return, we might use source_id = grievance_id

            $table->string('reference_no')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['active', 'voided'])->default('active');
            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_ip')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_ip')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Unique constraint to prevent duplicate dispatch debits
            $table->unique(['order_id', 'source_type', 'type']);

            $table->index(['store_id', 'txn_date']);
            $table->index('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
