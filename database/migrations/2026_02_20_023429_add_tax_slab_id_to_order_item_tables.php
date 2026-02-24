<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxSlabIdToOrderItemTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_services', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_slab_id')->nullable()->after('subtotal');
        });

        Schema::table('order_packaging_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_slab_id')->nullable()->after('subtotal');
        });

        Schema::table('order_other_items', function (Blueprint $table) {
            $table->unsignedBigInteger('tax_slab_id')->nullable()->after('subtotal');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_services', function (Blueprint $table) {
            $table->dropColumn('tax_slab_id');
        });

        Schema::table('order_packaging_materials', function (Blueprint $table) {
            $table->dropColumn('tax_slab_id');
        });

        Schema::table('order_other_items', function (Blueprint $table) {
            $table->dropColumn('tax_slab_id');
        });
    }
}
