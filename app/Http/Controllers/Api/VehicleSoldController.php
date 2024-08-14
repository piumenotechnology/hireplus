<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Models\VehicleSold;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\OtherIncome;
use Haruncpi\LaravelIdGenerator\IdGenerator;


class VehicleSoldController extends Controller
{
    public function index(Request $request){
        
        $vehiclesold = DB::table('vehicle_solds')
                    ->join('sales_orders','sales_orders.id','=','vehicle_solds.id_sales_order')
                    ->join('purchase_orders','purchase_orders.id','=','vehicle_solds.id_purchase_order')
                    ->select('vehicle_solds.*','sales_orders.agreement_number','purchase_orders.vehicle_registration');
                    //->paginate(request()->per_page);
                    // ->get();
        
        if ($s = $request->input('search')) {
            $vehiclesold->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
            ->orWhereRaw("agreement_number LIKE '%" . $s . "%'");
        }
                
        if ($sort = $request->input('sort')) {
            $vehiclesold->orderBy(request()->sort, $request->input('order') );
        }
                
        $result = $vehiclesold->paginate(request()->per_page);
                
        if(count($result) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ],200);
        }
                
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);

        // if(count($vehiclesold) > 0){
        //     return response([
        //         'message' => 'Retrieve All Success',
        //         'data' => $vehiclesold
        //     ],200);
        // }

        // return response([
        //     'message' => 'Empty',
        //     'data' => null
        // ],400);

    }

    public function show($id){
        $vehiclesold = VehicleSold::find($id);

        if(!is_null($vehiclesold)){
            return response([
                'message' => 'Retrieve Vehicle Sold Success',
                'data' => $vehiclesold
            ],200);
        }

        return response([
            'message' => 'Vehicle Sold Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_sales_order'             => 'nullable',
            'id_purchase_order'          => 'nullable',
            'vehicle_sold_date'          => 'nullable',
            'sold_price'                 => 'nullable',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $vehiclesold = VehicleSold::create($storeData);

        $vehiclesold->sold_price = round($vehiclesold->sold_price,2);

        $sales_order = SalesOrder::find($vehiclesold->id_sales_order);

        $purchaseorder = PurchaseOrder::find($vehiclesold->id_purchase_order);

        $purchaseorder->status_next_step = 'Sold';
        $purchaseorder->save();
        
        $sales_order->next_step_status_sales = 'Sold';
        $sales_order->save();
         
        $amount_oi = VehicleSold::join('other_incomes', 'other_incomes.id_purchase_order','=','vehicle_solds.id_purchase_order')
         ->whereRaw('vehicle_solds.id_purchase_order = '.$vehiclesold->id_purchase_order)
         ->value('amount_oi');

        $vehiclesolddate = \Carbon\Carbon::parse($request->vehicle_sold_date);
        $contractstartdate = \Carbon\Carbon::parse($sales_order->contract_start_date);
        
        //fo001
        if($vehiclesold->vehicle_sold_date != null) {
            $sales_order->margin_term = $contractstartdate->diffInMonths($vehiclesolddate);
            $sales_order->save();
        } 

         //rental income
        $sales_order->rental_income = round($sales_order->first_payment + $sales_order->monthly_rental * ($sales_order->margin_term + 1),2);
        $sales_order->save();

        //fo002
         if($amount_oi == null){
                $sales_order->total_income = round($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
             
         } else {
            $sales_order->total_income = round($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
            $sales_order->save();
             
         }

        //  //fo007
        //     $sales_order->total_income_new = round(($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->term_months - 1))),2); 
        //     $sales_order->save();
         

         //fo0012 contract_margin
        $sales_order->contract_margin = round(($sales_order->total_income) - $sales_order->total_cost,2);
        $sales_order->save();
        
        return response([
            'message' => 'Add Vehicle Sold Success',
            'data' => $vehiclesold,
        ],200);
    }

    public function destroy($id){
        $vehiclesold = VehicleSold::find($id);
       
        if(is_null($vehiclesold)){
            return response([
                'message' => 'Vehicle Sold Not Found',
                'data' => null
            ],404);
        }

        $update = PurchaseOrder::where('id',$vehiclesold->id_purchase_order)
                  ->update(['status_next_step' => 'Available']);

        if($vehiclesold->delete()){
            return response([
                'message' => 'Delete Vehicle Sold Success',
                'data' => $vehiclesold,
            ],200);
        }
        
        return response([
            'message' => 'Delete Vehicle Sold Failed',
            'data' => null,
        ],400);

    }

    
    public function update(Request $request, $id){
        $vehiclesold = VehicleSold::find($id);
        if(is_null($vehiclesold)){
            return response([
                'message' => 'Vehicle Sold Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_sales_order'             => 'nullable',
            'id_purchase_order'          => 'nullable',
            'vehicle_sold_date'         => 'nullable',
            'sold_price'                 => 'nullable',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);
        
        $vehiclesold->id_sales_order             = $updateData['id_sales_order'];
        $vehiclesold->id_purchase_order          = $updateData['id_purchase_order'];
        $vehiclesold->vehicle_sold_date          = $updateData['vehicle_sold_date'];
        $vehiclesold->sold_price                 = $updateData['sold_price'];

        $vehiclesold->sold_price = round($vehiclesold->sold_price,2);
        
        $sales_order = SalesOrder::find($vehiclesold->id_sales_order);

        $amount_oi = VehicleSold::join('other_incomes', 'other_incomes.id_purchase_order','=','vehicle_solds.id_purchase_order')
        ->whereRaw('vehicle_solds.id_purchase_order = '.$vehiclesold->id_purchase_order)
        ->value('amount_oi');
        
        $vehiclesolddate = \Carbon\Carbon::parse($request->vehicle_sold_date);
        $contractstartdate = \Carbon\Carbon::parse($sales_order->contract_start_date);
             
        //fo001
        if($vehiclesold->vehicle_sold_date != null) {
            $sales_order->margin_term = $contractstartdate->diffInMonths($vehiclesolddate);
            $sales_order->save();
        }

         //rental income
        $sales_order->rental_income = round($sales_order->first_payment + $sales_order->monthly_rental * ($sales_order->margin_term + 1),2);
        $sales_order->save();

         //fo002
         if($amount_oi == null){
            $sales_order->total_income = round($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
            $sales_order->save();
         
        } else {
            $sales_order->total_income = round($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
            $sales_order->save();
         
        }

        // //fo007
        
        //     $sales_order->total_income_new = round(($vehiclesold->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->term_months - 1))),2); 
        //     $sales_order->save();
         

         //fo0012 contract_margin
        $sales_order->contract_margin = round(($sales_order->total_income) - $sales_order->total_cost,2);
        $sales_order->save();

        if($vehiclesold->save()){
            return response([
                'message' => 'Update Vehicle Sold Success',
                'data' => $vehiclesold,
            ],200);
        }

        return response([
            'message' => 'Update Vehicle Sold Failed',
            'data' => null
        ],400);
    }
    
}
