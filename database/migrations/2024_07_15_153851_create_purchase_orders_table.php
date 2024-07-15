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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('id_sales_order')->nullable();
            $table->string('purchase_method');
            $table->string('vehicle_registration');
            $table->string('hp_finance_provider')->nullable();
            $table->date('hire_purchase_starting_date');
            $table->double('hp_interest_per_annum')->nullable();
            $table->double('hp_deposit_amount')->nullable();
            $table->integer('hp_term')->nullable();
            $table->double('documentation_fees_pu')->nullable();
            $table->double('final_fees')->nullable();
            $table->double('other_fees')->nullable();
            $table->double('price_otr');
            $table->double('monthly_payment')->nullable();
            $table->double('final_payment')->nullable();
            $table->string('hp_interest_type')->nullable();
            $table->string('vehicle_manufactur');
            $table->string('vehicle_model');
            $table->string('vehicle_variant');
            $table->double('basic_list_price');
            $table->double('residual_value');
            $table->string('colour');
            $table->double('min_contract_price_satu');
            $table->double('min_contract_price_dua');
            $table->string('service_maintenance')->nullable();
            $table->date('mot_due_date');
            $table->date('rfl_due_date');
            $table->double('service_schedule_miles')->nullable();
            $table->double('service_schedule_years')->nullable();
            $table->double('last_service_mileage')->nullable();
            $table->date('last_service_date')->nullable();
            $table->double('financing_amount')->nullable();
            $table->double('regular_monthly_payment')->nullable();
            $table->string('status_next_step')->nullable();
            $table->double('vehicle_tracking')->nullable();
            $table->double('sum_docdepoth')->nullable();
            $table->date('tgl_available')->nullable();
            $table->string('stock_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
