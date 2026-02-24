<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'billing_google_map_link')) {
                $table->string('billing_google_map_link')->nullable()->after('billing_pincode');
            }
            if (!Schema::hasColumn('orders', 'billing_latitude')) {
                $table->string('billing_latitude')->nullable()->after('billing_google_map_link');
            }
            if (!Schema::hasColumn('orders', 'billing_longitude')) {
                $table->string('billing_longitude')->nullable()->after('billing_latitude');
            }

            if (!Schema::hasColumn('orders', 'shipping_google_map_link')) {
                $table->string('shipping_google_map_link')->nullable()->after('shipping_pincode');
            }
            if (!Schema::hasColumn('orders', 'shipping_latitude')) {
                $table->string('shipping_latitude')->nullable()->after('shipping_google_map_link');
            }
            if (!Schema::hasColumn('orders', 'shipping_longitude')) {
                $table->string('shipping_longitude')->nullable()->after('shipping_latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'billing_google_map_link')) {
                $table->dropColumn('billing_google_map_link');
            }
            if (Schema::hasColumn('orders', 'billing_latitude')) {
                $table->dropColumn('billing_latitude');
            }
            if (Schema::hasColumn('orders', 'billing_longitude')) {
                $table->dropColumn('billing_longitude');
            }

            if (Schema::hasColumn('orders', 'shipping_google_map_link')) {
                $table->dropColumn('shipping_google_map_link');
            }
            if (Schema::hasColumn('orders', 'shipping_latitude')) {
                $table->dropColumn('shipping_latitude');
            }
            if (Schema::hasColumn('orders', 'shipping_longitude')) {
                $table->dropColumn('shipping_longitude');
            }
        });
    }
};

