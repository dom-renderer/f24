<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 100)->nullable();
            $table->enum('order_type', ['company', 'franchise', 'dealer']);
            $table->boolean('for_customer')->default(false);
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('sender_store_id')->nullable();
            $table->unsignedBigInteger('receiver_store_id')->nullable();
            $table->unsignedBigInteger('dealer_id')->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_second_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone_number')->nullable();
            $table->string('customer_remark')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0:Pending, 1:Approved, 2:Dispatched, 3:Delivered, 4:Cancelled');
            $table->boolean('is_approved')->default(false);
            $table->boolean('collect_on_delivery')->default(false)->comment('Checkbox for pending amount');
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->boolean('tax_type')->default(0)->comment('0 = Percentage | 1 = Fix');
            $table->decimal('tax_amount', 15, 2)->default(0);

            $table->boolean('discunt_type')->default(0)->comment('0 = Percentage | 1 = Fix');
            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('amount_collected', 15, 2)->default(0);

            $table->unsignedBigInteger('delivery_user')->nullable();

            $table->text('cancellation_note')->nullable();
            $table->boolean('utencils_collected')->default(false);
            $table->boolean('payment_received')->default(false);

            $table->dateTime('delivery_schedule_from')->nullable();
            $table->dateTime('delivery_schedule_to')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->json('handling_instructions')->nullable();
            $table->text('handling_note')->nullable();

            $table->string('payment_proof')->nullable();
            $table->string('customer_signature')->nullable();
            $table->string('delivery_guy_signature')->nullable();
            $table->string('payment_method')->nullable();

            $table->unsignedBigInteger('vehicle_id')->nullable();

            $table->enum('bill_to_type', ['store', 'user', 'factory'])->default('store')->comment('store => bill_to = stores.id, user => bill_to = users.id, factory => bill_to = null');
            $table->unsignedBigInteger('bill_to_id')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_contact_number')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_address_1')->nullable();
            $table->string('billing_address_2')->nullable();
            $table->string('billing_pincode')->nullable();
            $table->string('billing_latitude')->nullable();
            $table->string('billing_longitude')->nullable();
            $table->string('billing_gst_in')->nullable();

            $table->string('shipping_name')->nullable();
            $table->string('shipping_contact_number')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_address_1')->nullable();
            $table->string('shipping_address_2')->nullable();
            $table->string('shipping_pincode')->nullable();
            $table->string('shipping_latitude')->nullable();
            $table->string('shipping_longitude')->nullable();
            $table->string('shipping_gst_in')->nullable();

            $table->boolean('bill_to_same_as_ship_to')->default(0);

            $table->decimal('cgst_percentage', 5, 2)->default(0);
            $table->decimal('sgst_percentage', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);

            $table->text('delivery_address')->nullable();
            $table->text('delivery_link')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
