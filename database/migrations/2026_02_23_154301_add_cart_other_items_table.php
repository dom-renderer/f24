<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCartOtherItemsTable extends Migration
{
    public function up()
    {
        Schema::create('cart_other_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('other_item_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('price_includes_tax')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cart_other_items');
    }
}
