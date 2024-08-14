<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_purchase_order','type','agreement_no','agreement_number','cust_name','contract_start_date','annual_mileage',
        'term_months','initial_rental','documentation_fees','monthly_rental','other_income','margin_term','total_income','next_step_status_sales','first_payment','total_income_new',
        'penalty_early_settlement','settlement','annum_payment','total_cost','contract_margin','rental_income'
    ];

    public function getCreatedAtAttribute(){
        if(!is_null($this->attributes['created_at'])){
            return Carbon::parse($this->attributes['created_at'])->format('Y-m-d H:i:s');
        }
    }

    public function getUpdatedAtAttribute(){
        if(!is_null($this->attributes['updated_at'])){
            return Carbon::parse($this->attributes['updated_at'])->format('Y-m-d H:i:s');
        }
    }
    
}
