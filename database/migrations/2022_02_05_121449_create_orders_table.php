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
            $table->string('amazon_order_id')->nullable();
            $table->primary('amazon_order_id');
            $table->unique('amazon_order_id');
            $table->string('seller_order_id')->nullable();
            $table->dateTime('purchase_date')->nullable();
            $table->dateTime('last_update_date')->nullable();
            $table->string('order_status')->nullable();
            $table->string('fulfillment_channel')->nullable();
            $table->string('sales_channel')->nullable();
            $table->string('order_channel')->nullable();
            $table->string('ship_service_level')->nullable();
            $table->string('order_total')->nullable();
            $table->string('currency_code')->nullable();
            $table->smallinteger('number_of_items_shipped')->nullable();
            $table->smallinteger('number_of_items_unshipped')->nullable();
            $table->string('payment_execution_detail')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_method_details')->nullable();
            $table->string('marketplace_id')->nullable();
            $table->string('shipment_service_level_category')->nullable();
            $table->string('easy_ship_shipment_status')->nullable();
            $table->string('cba_displayable_shipping_label')->nullable();
            $table->string('order_type')->nullable();
            $table->dateTime('earliest_ship_date')->nullable();
            $table->dateTime('latest_ship_date')->nullable();
            $table->dateTime('earliest_delivery_date')->nullable();
            $table->dateTime('latest_delivery_date')->nullable();
            $table->boolean('is_business_order')->nullable();
            $table->boolean('is_prime')->nullable();
            $table->boolean('is_premium_order')->nullable();
            $table->boolean('is_global_express_enabled')->nullable();
            $table->string('replaced_order_id')->nullable();
            $table->boolean('is_replacement_order')->nullable();
            $table->dateTime('promise_response_due_date')->nullable();
            $table->boolean('is_estimated_ship_date_set')->nullable();
            $table->boolean('is_sold_by_ab')->nullable();
            $table->string('default_ship_from_location_address')->nullable();
            $table->string('buyer_invoice_preference')->nullable();
            $table->string('buyer_tax_information')->nullable();
            $table->string('fulfillment_instruction')->nullable();
            $table->boolean('is_ispu')->nullable();
            $table->string('marketplace_tax_info')->nullable();
            $table->string('seller_display_name')->nullable();
            $table->string('buyer_info')->nullable();
            $table->string('automated_shipping_settings')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('street')->nullable();
            $table->string('country')->nullable();
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
