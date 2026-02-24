<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProductPriceManagementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('order_product_price_management')) {
            Schema::create('order_product_price_management', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('unit_id');

            $table->decimal('price', 10, 2);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['order_product_id', 'store_id', 'unit_id'],
                'oppm_order_product_store_unit_unique'
            );

            $table->index('store_id');
            $table->index('unit_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_product_price_management');
    }
}
