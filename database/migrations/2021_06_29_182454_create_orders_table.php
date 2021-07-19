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
            $table->integer('user_id');
            $table->integer('status');
            $table->integer('amount');
            $table->integer('province')->nullable();
            $table->integer('city')->nullable();
            $table->string('address', 1024)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('receiver_name', 64)->nullable();
            $table->string('receiver_mobile', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
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
        Schema::dropIfExists('orders');
    }
}
