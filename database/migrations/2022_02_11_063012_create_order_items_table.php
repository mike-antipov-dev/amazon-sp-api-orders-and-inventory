<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->string('amazon_order_id')->nullable();
            $table->string('asin')->nullable();
            $table->primary('asin')->nullable();
            $table->unique('asin')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('order_item_id')->nullable();
            $table->string('title')->nullable();
            $table->smallinteger('quantity_ordered')->nullable();
            $table->smallinteger('quantity_shipped')->nullable();
            $table->smallinteger('number_of_items')->nullable();
            $table->smallinteger('points_granted')->nullable();
            $table->smallinteger('item_price')->nullable();
            $table->string('currency_code')->nullable();
            $table->smallinteger('shipping_price')->nullable();
            $table->smallinteger('item_tax')->nullable();
            $table->smallinteger('shipping_tax')->nullable();
            $table->smallinteger('shipping_discount')->nullable();
            $table->smallinteger('shipping_discount_tax')->nullable();
            $table->smallinteger('promotion_discount')->nullable();
            $table->smallinteger('promotion_discount_tax')->nullable();
            $table->string('promotion_ids')->nullable();
            $table->smallinteger('cod_fee')->nullable();
            $table->smallinteger('cod_fee_discount')->nullable();
            $table->boolean('is_gift')->nullable();
            $table->string('condition_note')->nullable();
            $table->smallinteger('condition_id')->nullable();
            $table->smallinteger('condition_subtype_id')->nullable();
            $table->dateTime('scheduled_delivery_start_date')->nullable();
            $table->dateTime('scheduled_delivery_end_date')->nullable();
            $table->string('price_designation')->nullable();
            $table->string('tax_collection_model')->nullable();
            $table->string('tax_collection_reasponsible_party')->nullable();
            $table->boolean('serial_number_required')->nullable();
            $table->boolean('is_transparency')->nullable();
            $table->string('ioss_number')->nullable();
            $table->string('store_chain_store_id')->nullable();
            $table->string('deemed_reseller_category')->nullable();
            $table->string('buyer_info')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
    }
}
