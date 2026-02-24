<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxSlabToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_slab_id')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_slab_id')->nullable();
            $table->double('cgst')->default(0)->nullable();
            $table->double('sgst')->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('tax_slab_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['tax_slab_id', 'cgst', 'sgst']);
        });        
    }
}
