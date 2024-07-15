<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rehiring_orders', function (Blueprint $table) {
            $table->id();
            $table->string('next_step')->nullable();
            $table->integer('id_sales_order')->nullable();
            //$table->integer('id_other_income')->nullable();
            $table->string('new_sales_order_no')->nullable();
            $table->integer('id_purchase_order')->nullable();
            $table->date('vehicle_return_date')->nullable();
            $table->double('sold_price')->nullable();
            //$table->double('total_income');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehiring_orders');
    }
};
