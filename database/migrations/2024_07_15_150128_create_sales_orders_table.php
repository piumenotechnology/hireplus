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
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('id_purchase_order');
            $table->string('type');
            $table->string('agreement_no')->nullable();
            $table->string('agreement_number');
            $table->string('cust_name');
            $table->date('contract_start_date');
            $table->double('annual_mileage');
            $table->integer('term_months');
            $table->double('initial_rental');
            $table->double('documentation_fees');
            $table->double('monthly_rental');
            $table->double('other_income');
            $table->integer('margin_term')->nullable();
            $table->double('total_income')->nullable();
            $table->string('next_step_status_sales')->nullable();
            $table->double('first_payment')->nullable();
            $table->double('total_monthly_rental')->nullable();
            $table->double('penalty_early_settlement')->nullable();
            $table->double('settlement')->nullable();
            $table->double('annum_payment')->nullable();
            $table->double('sales_final_payment')->nullable();
            $table->double('total_cost')->nullable();
            $table->double('contract_margin')->nullable();
            $table->double('rental_income')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
