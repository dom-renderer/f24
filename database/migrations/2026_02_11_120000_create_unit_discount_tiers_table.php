<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitDiscountTiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pricing_tier_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_unit_id');
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->nullable()->comment('Null = infinity');
            $table->unsignedTinyInteger('discount_type')->default(0)->comment('0 = Percentage, 1 = Fixed amount');
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('price_before_discount', 15, 2)->nullable();
            $table->boolean('status')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_discount_tiers');
    }
}

