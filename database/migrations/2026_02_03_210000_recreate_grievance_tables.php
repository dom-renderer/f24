<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecreateGrievanceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('grievance_items');
        Schema::dropIfExists('grievances');

        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('reported_by')->comment('User ID who reported the grievance');
            $table->tinyInteger('status')->default(0)->comment('0: Pending, 1: Resolved, 2: Rejected');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('grievance_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grievance_id');
            $table->unsignedBigInteger('order_item_id');
            $table->decimal('quantity', 10, 2);
            $table->string('issue_type')->comment('not_received, partially_received, defective');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('grievance_id')->references('id')->on('grievances')->onDelete('cascade');
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
        Schema::dropIfExists('grievances');
    }
}
