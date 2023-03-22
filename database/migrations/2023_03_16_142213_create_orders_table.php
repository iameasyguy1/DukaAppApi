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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->longText('notes')->nullable();
            $table->string('mpesa_no')->nullable();
            $table->integer('quantity')->default(1)->nullable();
            $table->double('total')->default(0)->nullable();
            $table->longText('shipping_address')->nullable();
            $table->string('order_status')->default('pending')->nullable();
            $table->string('payment_status')->default('unpaid')->nullable();
            $table->string('payment_mode')->default('mpesa')->nullable();
            $table->string('tranx_ref')->nullable();
            $table->unsignedBigInteger('page_id')->nullable();
            $table->foreign('page_id')->references('id')->on('pages')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
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
};
