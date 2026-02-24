<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagingMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packaging_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('pricing_type', ['fixed', 'as_per_actual']);
            $table->decimal('price_per_piece', 8, 2)->nullable();
            $table->boolean('price_includes_tax')->default(0);
            $table->foreignId('tax_slab_id')->nullable()->constrained('tax_slabs');
            $table->boolean('status')->default(1)->comment('1=Active, 0=Inactive');
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
        Schema::dropIfExists('packaging_materials');
    }
}
