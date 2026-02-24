<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceIncludesTaxPricingTypeToOrderItemTables extends Migration
{
    public function up()
    {
        Schema::table('order_services', function (Blueprint $table) {
            $table->boolean('price_includes_tax')->default(0)->after('subtotal');
            $table->string('pricing_type', 20)->default('fixed')->after('price_includes_tax');
        });

        Schema::table('order_packaging_materials', function (Blueprint $table) {
            $table->boolean('price_includes_tax')->default(0)->after('subtotal');
            $table->string('pricing_type', 20)->default('fixed')->after('price_includes_tax');
        });

        Schema::table('order_other_items', function (Blueprint $table) {
            $table->boolean('price_includes_tax')->default(0)->after('subtotal');
            $table->string('pricing_type', 20)->default('fixed')->after('price_includes_tax');
        });
    }

    public function down()
    {
        Schema::table('order_services', function (Blueprint $table) {
            $table->dropColumn(['price_includes_tax', 'pricing_type']);
        });

        Schema::table('order_packaging_materials', function (Blueprint $table) {
            $table->dropColumn(['price_includes_tax', 'pricing_type']);
        });

        Schema::table('order_other_items', function (Blueprint $table) {
            $table->dropColumn(['price_includes_tax', 'pricing_type']);
        });
    }
}
