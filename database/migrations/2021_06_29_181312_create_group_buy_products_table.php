<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupBuyProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_buy_products', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->integer('product_id');
            $table->integer('price');
            $table->integer('quantity');
            $table->integer('status');
            $table->string('description', 1024);
            $table->integer('user_quantity_limit')->nullable();
            $table->boolean('is_special')->default(false);
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_buy_products');
    }
}
