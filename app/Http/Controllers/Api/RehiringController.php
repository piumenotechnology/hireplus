<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Models\RehiringOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\OtherIncome;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class RehiringController extends Controller
{
    public function index(Request $request){
        //$rehiringorders = RehiringOrder::all();
        $rehiringorders = DB::table('rehiring_orders')
                    ->join('sales_orders','sales_orders.id','=','rehiring_orders.id_sales_order')
                    ->join('purchase_orders','purchase_orders.id','=','rehiring_orders.id_purchase_order')
                    ->select('rehiring_orders.*','sales_orders.agreement_number','purchase_orders.vehicle_registration');
                    //->paginate(request()->per_page);
                    //->get();

        if ($s = $request->input('search')) {
            $rehiringorders->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
            ->orWhereRaw("agreement_number LIKE '%" . $s . "%'");
        }
                
        if ($sort = $request->input('sort')) {
            $rehiringorders->orderBy(request()->sort, $request->input('order') );
        }
                
        $result = $rehiringorders->paginate(request()->per_page);
                
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

    }

    //tampil dialog di serach, dimana vehicle regis number masuk di database rehiring
    //  public function showVehicleRehiringOrder(){
    //      $rehiringorder = DB::table('rehiring_orders')
    //                      ->join('sales_orders','sales_orders.id','=','rehiring_orders.id_sales_order')
    //                      ->join('purchase_orders','purchase_orders.id','=','rehiring_orders.id_purchase_order')
    //                      ->select('rehiring_orders.*','sales_orders.agreement_no','purchase_orders.vehicle_registration')
    //                      //->whereRaw('rehiring_orders.id = "'.$id.'"')
    //                      ->get();

    //      if(count($rehiringorder) > 0){
    //          return response([
    //              'message' => 'Retrieve All Success',
    //              'data' => $rehiringorder
    //          ],200);
    //      }
                
    //      return response([
    //          'message' => 'Empty',
    //          'data' => null
    //      ],400);
    //  }

    public function showVehicleSold(){
        //$rehiringorders = RehiringOrder::all();
        $rehiringorders = DB::table('rehiring_orders')
                          ->join('sales_orders','sales_orders.id','=','rehiring_orders.id_sales_order')
                          ->join('purchase_orders','purchase_orders.id','=','rehiring_orders.id_purchase_order')
                          ->where('next_step' ,'=', 'Sold')
                          ->select('rehiring_orders.*','sales_orders.agreement_number','purchase_orders.vehicle_registration')
                          ->get();

        if(count($rehiringorders) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $rehiringorders
            ],200);
        }
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function show($id){
        $rehiringorder = RehiringOrder::find($id);

        if(!is_null($rehiringorder)){
            return response([
                'message' => 'Retrieve Rehiring Order Success',
                'data' => $rehiringorder
            ],200);
        }

        return response([
            'message' => 'Rehiring Order Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'next_step'                  => 'nullable',
            'id_sales_order'             => 'nullable',
            'new_sales_order_no'         => 'nullable',
            'id_purchase_order'          => 'nullable',
            'vehicle_return_date'        => 'nullable',
            //'sold_price'                 => 'nullable',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $rehiringorder = RehiringOrder::create($storeData);

        $sales_order = SalesOrder::find($rehiringorder->id_sales_order);

        $purchaseorder = PurchaseOrder::find($rehiringorder->id_purchase_order);

        //update tgl available dengan tgl rehiring
        $purchaseorder->tgl_available = $rehiringorder->vehicle_return_date;

        $purchaseorder->status_next_step = 'Available';
        $purchaseorder->save();
        
        $sales_order->next_step_status_sales = 'Innactive';
        $sales_order->save();
         
        $amount_oi = RehiringOrder::join('other_incomes', 'other_incomes.id_purchase_order','=','rehiring_orders.id_purchase_order')
         ->whereRaw('rehiring_orders.id_purchase_order = '.$rehiringorder->id_purchase_order)
         ->value('amount_oi');

        $vehiclereturndate = \Carbon\Carbon::parse($request->vehicle_return_date);
        $contractstartdate = \Carbon\Carbon::parse($sales_order->contract_start_date);
        
        //fo001
        if($rehiringorder->vehicle_return_date != null) {
            $sales_order->margin_term = $contractstartdate->diffInMonths($vehiclereturndate);
            $sales_order->save();
        } 

        //rental income
        if($sales_order->next_step_status_sales != 'Hired') {
            if($amount_oi == null){
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        } else {
            if($amount_oi == null){
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        }

        //fo002
        if($sales_order->next_step_status_sales != 'Hired') {
            if($amount_oi == null){
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        } else {
            if($amount_oi == null){
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        }
        
        $sales_order->total_monthly_rental = $purchaseorder->regular_monthly_payment * 11; 
        $sales_order->save();


        $rehiringorder->new_sales_order_no = IdGenerator::generate(['table' => 'rehiring_orders','field'=>'new_sales_order_no', 'length' => 8, 'prefix' =>'NSO-']);
        //output: P00001
        $rehiringorder->save();
        
        return response([
            'message' => 'Add Rehiring Order Success',
            'data' => $rehiringorder,
        ],200);
    }

    public function destroy($id){
        $rehiringorder = RehiringOrder::find($id);
       
        if(is_null($rehiringorder)){
            return response([
                'message' => 'Rehiring Order Not Found',
                'data' => null
            ],404);
        }

        $update = PurchaseOrder::where('id',$rehiringorder->id_purchase_order)
                    ->update(['status_next_step' => 'Hired']);

        $update = SalesOrder::where('id',$rehiringorder->id_sales_order)
                    ->update(['next_step_status_sales' => 'Hired']);

        if($rehiringorder->delete()){
            return response([
                'message' => 'Delete Rehiring Order Success',
                'data' => $rehiringorder,
            ],200);
        }
        
        return response([
            'message' => 'Delete Rehiring Order Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $rehiringorder = RehiringOrder::find($id);
        if(is_null($rehiringorder)){
            return response([
                'message' => 'Rehiring Order Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'next_step'                  => 'nullable',
            'id_sales_order'             => 'nullable',
            'new_sales_order_no'         => 'nullable',
            'id_purchase_order'          => 'nullable',
            'vehicle_return_date'        => 'nullable',
            //'sold_price'                 => 'nullable',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);
        
        //$rehiringorder->next_step                  = $updateData['next_step'];
        $rehiringorder->id_sales_order             = $updateData['id_sales_order'];
        $rehiringorder->new_sales_order_no         = $updateData['new_sales_order_no'];
        $rehiringorder->id_purchase_order          = $updateData['id_purchase_order'];
        $rehiringorder->vehicle_return_date        = $updateData['vehicle_return_date'];
        //$rehiringorder->sold_price                 = $updateData['sold_price'];

        $sales_order = SalesOrder::find($rehiringorder->id_sales_order);
        
        $purchaseorder = PurchaseOrder::find($rehiringorder->id_purchase_order);

        //update status current contract menjadi 'Innactive'
        $update = SalesOrder::where('id',$rehiringorder->id_sales_order)
                    ->update(['next_step_status_sales' => 'Innactive']);

        //update tgl available dengan tgl rehiring, blm bisa terganti di tgl available, klk rehiringnya di update
        $purchaseorder->tgl_available = $rehiringorder->vehicle_return_date;

        $amount_oi = RehiringOrder::join('other_incomes', 'other_incomes.id_purchase_order','=','rehiring_orders.id_purchase_order')
        ->whereRaw('rehiring_orders.id_purchase_order = '.$rehiringorder->id_purchase_order)
        ->value('amount_oi');
        
        $vehiclereturndate = \Carbon\Carbon::parse($request->vehicle_return_date);
        $contractstartdate = \Carbon\Carbon::parse($sales_order->contract_start_date);
             
        //fo001
        if($rehiringorder->vehicle_return_date != null) {
            $sales_order->margin_term = $contractstartdate->diffInMonths($vehiclereturndate);
            $sales_order->save();
        }

        //rental income
        if($sales_order->next_step_status_sales != 'Hired') {
            if($amount_oi == null){
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        } else {
            if($amount_oi == null){
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->rental_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        }

        //fo002
        if($sales_order->next_step_status_sales != 'Hired') {
            if($amount_oi == null){
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        } else {
            if($amount_oi == null){
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + 0),2);
                $sales_order->save();
            } else {
                $sales_order->total_income = round($sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->margin_term) + $amount_oi),2);
                $sales_order->save();
            }
        }
        
        $sales_order->total_monthly_rental = $purchaseorder->regular_monthly_payment * 11; 
        $sales_order->save();

        
        if($rehiringorder->save()){
            return response([
                'message' => 'Update Rehiring Order Success',
                'data' => $rehiringorder,
            ],200);
        }

        return response([
            'message' => 'Update Rehiring Order Failed',
            'data' => null
        ],400);
    }

    // public function updateVehicleSold(Request $request, $id){
    //     $rehiringorder = RehiringOrder::find($id);
    //     if(is_null($rehiringorder)){
    //         return response([
    //             'message' => 'Rehiring Order Not Found',
    //             'data' => null
    //         ],404);
    //     }

    //     $updateData = $request->all();
    //     $validate = Validator::make($updateData, [
    //         'next_step'                  => 'required|in:Rehiring,Sold',
    //         'id_sales_order'             => 'nullable',
    //         'id_purchase_order'          => 'nullable',
    //         'vehicle_return_date'        => 'nullable',
    //         'sold_price'                 => 'nullable',
    //     ]);

    //     if($validate->fails())
    //     return response(['message' => $validate->errors()],400);
        
    //     $rehiringorder->next_step                  = $updateData['next_step'];
    //     $rehiringorder->id_sales_order             = $updateData['id_sales_order'];
    //     $rehiringorder->id_purchase_order          = $updateData['id_purchase_order'];
    //     $rehiringorder->vehicle_return_date        = $updateData['vehicle_return_date'];
    //     $rehiringorder->sold_price                 = $updateData['sold_price'];

    //     $rehiringorder->sold_price = round($rehiringorder->sold_price,2);
        
    //     $sales_order = SalesOrder::find($rehiringorder->id_sales_order);

    //     $amount_oi = RehiringOrder::join('other_incomes', 'other_incomes.id_purchase_order','=','rehiring_orders.id_purchase_order')
    //     ->whereRaw('rehiring_orders.id_purchase_order = '.$rehiringorder->id_purchase_order)
    //     ->value('amount_oi');
        
    //     $vehiclereturndate = \Carbon\Carbon::parse($request->vehicle_return_date);
    //     $contractstartdate = \Carbon\Carbon::parse($sales_order->contract_start_date);
             
    //     //fo001
    //     if($rehiringorder->vehicle_return_date != null) {
    //         $sales_order->margin_term = $contractstartdate->diffInMonths($vehiclereturndate);
    //         $sales_order->save();
    //     }

    //     //fo002
    //     if($amount_oi == null){
    //         if($rehiringorder->next_step != 'Sold') {
    //             $sales_order->total_income = round($sales_order->residual_value + $sales_order->initial_rental + ($sales_order->monthly_rental * ($sales_order->margin_term - 1) + 0),2);
    //             $sales_order->save();
    //         } else {
    //             $sales_order->total_income = round($rehiringorder->sold_price + $sales_order->initial_rental + ($sales_order->monthly_rental * ($sales_order->margin_term - 1) + 0),2);
    //             $sales_order->save();
    //         }
    //     } else {
    //         if($rehiringorder->next_step != 'Sold') {
    //             $sales_order->total_income = round($sales_order->residual_value + $sales_order->initial_rental + ($sales_order->monthly_rental * ($sales_order->margin_term - 1) + $amount_oi),2);
    //             $sales_order->save();
    //         } else {
    //             $sales_order->total_income = round($rehiringorder->sold_price + $sales_order->initial_rental + ($sales_order->monthly_rental * ($sales_order->margin_term - 1) + $amount_oi),2);
    //             $sales_order->save();
    //         }
    //     }

    //     //fo007
    //     if($rehiringorder->next_step == 'Sold'){
    //         $sales_order->total_income_new = round(($rehiringorder->sold_price + $sales_order->first_payment + ($sales_order->monthly_rental * ($sales_order->term_months - 1))),2); 
    //         $sales_order->save();
    //      }

    //      //fo0012 contract_margin
    //     $sales_order->contract_margin = round(($sales_order->total_income_new) - $sales_order->total_cost,2);
    //     $sales_order->save();

    //     if($rehiringorder->save()){
    //         return response([
    //             'message' => 'Update Rehiring Order Success',
    //             'data' => $rehiringorder,
    //         ],200);
    //     }

    //     return response([
    //         'message' => 'Update Rehiring Order Failed',
    //         'data' => null
    //     ],400);
    // }
    
}

