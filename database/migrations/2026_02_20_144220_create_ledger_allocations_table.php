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
        Schema::create('ledger_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('credit_txn_id'); // The Payment/Credit Note
            $table->unsignedBigInteger('debit_txn_id');  // The Invoice/Debit
            $table->decimal('allocated_amount', 12, 2);
            $table->dateTime('allocated_at');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_ip')->nullable();

            $table->dateTime('voided_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_allocations');
    }
};
