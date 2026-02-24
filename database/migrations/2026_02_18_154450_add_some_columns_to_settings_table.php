<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSomeColumnsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->double('company_store_discount', 5, 2)->default(0)->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->double('company_store_discount', 5, 2)->default(0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('company_store_discount');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('company_store_discount');
        });
    }
}
