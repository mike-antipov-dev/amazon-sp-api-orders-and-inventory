<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->string('asin')->nullable();
            $table->primary('asin');
            $table->unique('asin');
            $table->string('fn_sku')->nullable();
            $table->string('seller_sku')->nullable();
            $table->string('condition')->nullable();
            $table->string('inventory_details')->nullable();
            $table->dateTime('last_updated_time')->nullable();
            $table->string('product_name')->nullable();
            $table->smallinteger('total_quantity')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_items');
    }
}
