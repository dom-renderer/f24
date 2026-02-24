<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrievanceItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grievance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grievance_id')->constrained('grievances')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->decimal('claimed_quantity', 10, 2);
            $table->text('issue_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grievance_items');
    }
}
