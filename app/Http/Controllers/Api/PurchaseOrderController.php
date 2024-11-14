<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Models\PurchaseOrder;
use App\Models\RehiringOrder;
use App\Models\OtherCost;
use App\Models\OtherIncome;
use App\Models\SalesOrder;
use App\Models\VehicleSold;
use App\Models\BaseInterest;
use App\Models\BaseInterestDetail;

class PurchaseOrderController extends Controller
{
    public function index(){
        $purchaseorders = PurchaseOrder::all();

        // $purchaseorder = DB::table('purchase_orders')
        //             ->join('sales_orders','sales_orders.id','=','purchase_orders.id_sales_order')
        //             ->select('purchase_orders.*','sales_orders.agreement_no')
        //             ->get();

        if(count($purchaseorders) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorders
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function indexAll(Request $request){
        //$purchaseorders = PurchaseOrder::all()

        // $purchaseorders = DB::table('purchase_orders')->paginate(request()->per_page);
        
        $query = PurchaseOrder::query();

        if ($s = $request->input('search')) {
            $query->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $query->orderBy(request()->sort, $request->input('order') );
        }

        $result = $query->paginate(request()->per_page);

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

    public function showVehicleNumberinSales(){
        
        $purchaseorder = DB::table('purchase_orders')
                     ->select('purchase_orders.vehicle_registration')
                     ->where('status_next_step','Available')
                     ->get();

        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);

    }

    public function show($id){
        $purchaseorder = PurchaseOrder::find($id);

        if(!is_null($purchaseorder)){
            return response([
                'message' => 'Retrieve Purchase Order Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Purchase Order Not Found',
            'data' => null
        ],400);
    }

    //
     public function showVehicle(){
         $purchaseorder = DB::table('purchase_orders')
                         ->join('sales_orders','sales_orders.id','=','purchase_orders.id_sales_order')
                         ->select('purchase_orders.*','sales_orders.agreement_number')
                         //->whereRaw('vehicle_registration = "'.$vehicle_number.'"')
                         ->get();
             if(count($purchaseorder) > 0){
             return response([
                 'message' => 'Retrieve All Success',
                 'data' => $purchaseorder
             ],200);
         }
              
         return response([
             'message' => 'Empty',
             'data' => null
         ],400);
     }

     //show vehicle registration number in vehicle sold form
     //show vehicle number in sales order's form, show car status just available and hired only.
     public function showVehicleNumberExceptSold(Request $request){
        $purchaseorder = DB::table('purchase_orders')
                        ->select('purchase_orders.*')
                        ->whereRaw('status_next_step in ("Available", "Hired")');
                        // ->get();

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        $result = $purchaseorder->paginate(request()->per_page);

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

    //show original sales number in vehicle sold form
    public function showSalesNumberInVehicleSold(){
        $purchaseorder = DB::table('purchase_orders')
                        ->join('sales_orders','sales_orders.id_purchase_order','=','purchase_orders.id')
                        ->select('purchase_orders.vehicle_registration','purchase_orders.status_next_step','sales_orders.agreement_number')
                        ->whereRaw('status_next_step in ("Available", "Hired")')
                        ->get();
            if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }
             
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    //
    public function listVehicleById($id){
        $rehiringByPurchaseId = RehiringOrder::whereRaw('id_purchase_order = '.$id)->first();
        
        if($rehiringByPurchaseId != null){
            $purchaseorder = SalesOrder::join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                        ->join('rehiring_orders','rehiring_orders.id_purchase_order','=','purchase_orders.id')
                        ->whereRaw('purchase_orders.id = '.$id)
                        ->first();
        }
        else{
            $purchaseorder = SalesOrder::join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                        ->whereRaw('purchase_orders.id = '.$id)
                        ->first();
        }
        if($purchaseorder != null){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }
                
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function listVehicleInVehicleCard($id){

        //$salesByPurchaseId = SalesOrder::whereRaw('id_purchase_order = '.$id)->first();

        $purchaseorder = DB::table('purchase_orders')
                    ->leftJoin('sales_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                    ->leftJoin('rehiring_orders','sales_orders.id','=','rehiring_orders.id_sales_order')
                    ->leftJoin('vehicle_solds','sales_orders.id','=','vehicle_solds.id_sales_order')
                    //->selectRaw('SUM(total_income_new) as total, purchase_orders.*, sales_orders.*, rehiring_orders.*, vehicle_solds.*')
                    ->whereRaw('purchase_orders.id = '.$id)
                    // ->groupBy('agreement_number')
                    //->sum('total_income_new')
                    ->get();
        

        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }
            
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);

    }

    public function listCostInCard($id){

        //$salesByPurchaseId = SalesOrder::whereRaw('id_purchase_order = '.$id)->first();

            $purchaseorder = DB::table('purchase_orders')
                           ->leftJoin('sales_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                           ->select('purchase_orders.*','sales_orders.total_cost')
                           ->whereRaw('purchase_orders.id = '.$id)->take(1)->get();
                           //->groupBy('agreement_number')
                           //->sum('total_income_new')
                           
       

         if(count($purchaseorder) > 0){
             return response([
                 'message' => 'Retrieve All Success',
                 'data' => $purchaseorder
             ],200);
         }
           
        return response([
            'message' => 'Empty',
            'data' => null
        ],400);

    }

     public function listTotalInCard($id){

            $otherIncomeByPurchaseId = OtherIncome::whereRaw('id_purchase_order = '.$id)->first();
            $otherCostByPurchaseId = OtherCost::whereRaw('id_purchase_order = '.$id)->first();

            if($otherIncomeByPurchaseId != null && $otherCostByPurchaseId != null) {
                $purchaseorder = DB::table('sales_orders')
                            ->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                            ->join('vehicle_solds','sales_orders.id','=','vehicle_solds.id_sales_order')
                            ->join('other_incomes','purchase_orders.id','=','other_incomes.id_purchase_order')
                            ->join('other_costs','purchase_orders.id','=','other_costs.id_purchase_order')
                            ->selectRaw('round((SUM(total_income) + SUM(amount_oi)),2) as sum_total_income, round(SUM(rental_income),2) as sum_rental_income, 
                            round((AVG(total_cost) + SUM(amount_oc)),2) as sum_total_cost, purchase_orders.vehicle_registration,
                            round(((SUM(total_income) + amount_oi)-AVG(total_cost)),2) as margin, vehicle_solds.sold_price, sales_orders.residual_value')
                            ->whereRaw('purchase_orders.id = '.$id)
                            ->get();

            } else if ($otherIncomeByPurchaseId == null && $otherCostByPurchaseId == null) {
                $purchaseorder = DB::table('sales_orders')
                            ->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                            ->leftJoin('vehicle_solds','purchase_orders.id','=','vehicle_solds.id_purchase_order')
                            ->selectRaw('round(SUM(total_income),2) as sum_total_income, round(SUM(rental_income),2) as sum_rental_income, 
                            round(AVG(total_cost),2) as sum_total_cost, purchase_orders.vehicle_registration,
                            round(SUM(total_income)-AVG(total_cost),2) as margin, vehicle_solds.sold_price, sales_orders.residual_value')
                            ->whereRaw('purchase_orders.id = '.$id)
                            ->get();
            }

         if(count($purchaseorder) > 0){
             return response([
                 'message' => 'Retrieve All Success',
                 'data' => $purchaseorder
             ],200);
         }
            
         return response([
             'message' => 'Empty',
             'data' => null
         ],400);

     }


    public function listTotalIncome($id){
            $purchaseorder = DB::table('sales_orders')
                        ->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                        ->selectRaw('round(SUM(total_income),2) as sum_total_income')
                        ->whereRaw('purchase_orders.id = '.$id)
                        ->first();
    
     if($purchaseorder != null){
         return response([
             'message' => 'Retrieve All Success',
             'data' => $purchaseorder
         ],200);
     }
        
     return response([
         'message' => 'Empty',
         'data' => null
     ],400);
}

    public function listTotalCost($id){
        $purchaseorder = DB::table('sales_orders')
                    ->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                    ->selectRaw('round(AVG(total_cost),2) as sum_total_cost')
                    ->whereRaw('purchase_orders.id = '.$id)
                    ->first();

        if($purchaseorder != null){
            return response([
            'message' => 'Retrieve All Success',
            'data' => $purchaseorder
        ],200);
    }

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

public function listRentalIncome($id){
    $purchaseorder = DB::table('sales_orders')
                ->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                ->selectRaw('round(SUM(rental_income),2) as sum_rental_income')
                ->whereRaw('purchase_orders.id = '.$id)
                ->first();

    if($purchaseorder != null){
        return response([
        'message' => 'Retrieve All Success',
        'data' => $purchaseorder
    ],200);
}

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

public function listOtherIncome($id){
    $purchaseorder = DB::table('other_incomes')
                ->join('purchase_orders','purchase_orders.id','=','other_incomes.id_purchase_order')
                ->selectRaw('round(SUM(amount_oi),2) as sum_other_income')
                ->whereRaw('purchase_orders.id = '.$id)
                ->first();

    if($purchaseorder != null){
        return response([
        'message' => 'Retrieve All Success',
        'data' => $purchaseorder
    ],200);
}

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

public function listOtherCost($id){
    $purchaseorder = DB::table('other_costs')
                ->join('purchase_orders','purchase_orders.id','=','other_costs.id_purchase_order')
                ->selectRaw('round(SUM(amount_oc),2) as sum_other_cost')
                ->whereRaw('purchase_orders.id = '.$id)
                ->first();

    if($purchaseorder != null){
        return response([
        'message' => 'Retrieve All Success',
        'data' => $purchaseorder
    ],200);
}

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

public function listSoldPrice($id){
    $purchaseorder = DB::table('vehicle_solds')
                ->join('purchase_orders','purchase_orders.id','=','vehicle_solds.id_purchase_order')
                ->selectRaw('round(SUM(sold_price),2) as sum_sold_price')
                ->whereRaw('purchase_orders.id = '.$id)
                ->first();

    if($purchaseorder != null){
        return response([
        'message' => 'Retrieve All Success',
        'data' => $purchaseorder
    ],200);
}

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

//vehicle performance
public function listResidualValue($id){
    $purchaseorder = DB::table('purchase_orders')
                //->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                ->selectRaw('round(SUM(residual_value),2) as sum_residual_value')
                ->whereRaw('purchase_orders.id = '.$id)
                ->first();

    if($purchaseorder != null){
        return response([
        'message' => 'Retrieve All Success',
        'data' => $purchaseorder
    ],200);
}

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);
}

public function availableStock(Request $request){
    //$rehiringorder = RehiringOrder::select('rehiring_orders.id_purchase_order')->get();

    //if($rehiringorder == null) {
        
    $purchaseorder = DB::table('purchase_orders')
                    ->select('purchase_orders.id','purchase_orders.vehicle_registration','purchase_orders.vehicle_manufactur','purchase_orders.vehicle_model','purchase_orders.colour','purchase_orders.vehicle_variant','purchase_orders.min_contract_price_satu','purchase_orders.min_contract_price_dua','purchase_orders.stock_status','purchase_orders.eta')
                    // ->whereRaw('status_next_step in ("Available")')
                    ->whereRaw('stock_status in ("Available") AND status_next_step in ("Available")');
                    // ->get();

    if ($s = $request->input('search')) {
        $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
    }

    if ($sort = $request->input('sort')) {
        $purchaseorder->orderBy(request()->sort, $request->input('order') );
    }

    $result = $purchaseorder->paginate(request()->per_page);

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

public function potentialStock(Request $request){
    $purchaseorder = DB::table('purchase_orders')
                    ->select('purchase_orders.id','purchase_orders.vehicle_registration','purchase_orders.vehicle_manufactur','purchase_orders.vehicle_model','purchase_orders.colour','purchase_orders.vehicle_variant','purchase_orders.min_contract_price_satu','purchase_orders.min_contract_price_dua','purchase_orders.stock_status')
                    ->whereRaw('stock_status in ("Potential")');

    if ($s = $request->input('search')) {
        $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
    }

    if ($sort = $request->input('sort')) {
        $purchaseorder->orderBy(request()->sort, $request->input('order') );
    }

    $result = $purchaseorder->paginate(request()->per_page);

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

public function bookedStock(Request $request){
    $purchaseorder = DB::table('purchase_orders')
                    ->select('purchase_orders.id','purchase_orders.vehicle_registration','purchase_orders.vehicle_manufactur','purchase_orders.vehicle_model','purchase_orders.colour','purchase_orders.vehicle_variant','purchase_orders.min_contract_price_satu','purchase_orders.min_contract_price_dua','purchase_orders.stock_status')
                    ->whereRaw('stock_status in ("Booked")');

    if ($s = $request->input('search')) {
        $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
            ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
    }

    if ($sort = $request->input('sort')) {
        $purchaseorder->orderBy(request()->sort, $request->input('order') );
    }

    $result = $purchaseorder->paginate(request()->per_page);

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

    public function changeStockStatus(Request $request, $id){
        $purchaseorder = PurchaseOrder::find($id);
        if(is_null($purchaseorder)){
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ],404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'stock_status'             => 'nullable',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);
        
        $purchaseorder->stock_status            = $updateData['stock_status'];
        
        if($purchaseorder->save()){
            return response([
                'message' => 'Update Vehicle Stock Status Success',
                'data' => $purchaseorder,
            ],200);
        }

        return response([
            'message' => 'Update Vehicle Stock Status Failed',
            'data' => null
        ],400);
    }

    public function changeEta(Request $request, $id){
        $purchaseorder = PurchaseOrder::find($id);
        if(is_null($purchaseorder)){
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ],404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'eta'             => 'nullable',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);
        
        $purchaseorder->eta            = $updateData['eta'];
        
        if($purchaseorder->save()){
            return response([
                'message' => 'Update Vehicle ETA Success',
                'data' => $purchaseorder,
            ],200);
        }

        return response([
            'message' => 'Update Vehicle ETA Failed',
            'data' => null
        ],400);
    }

    public function showVehicleNumber(){
        $rehiringorder = RehiringOrder::select('rehiring_orders.id_purchase_order')->get();
        
        $purchaseorder = DB::table('purchase_orders')
                    ->select('id','vehicle_registration')
                    ->whereNotIn('id',$rehiringorder)
                    ->whereOr()
                    ->get();

        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function showVehicleNumberInOtherCost(){
        $othercost = OtherCost::select('other_costs.id_purchase_order')->get();
        
        $purchaseorder = DB::table('purchase_orders')
                    ->select('id','vehicle_registration')
                    ->whereNotIn('id',$othercost)
                    ->whereOr()
                    ->get();

        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function showVehicleNumberInOtherIncome(){
        $otherincome = OtherIncome::select('other_incomes.id_purchase_order')->get();
        
        $purchaseorder = DB::table('purchase_orders')
                    ->select('id','vehicle_registration')
                    ->whereNotIn('id',$otherincome)
                    ->whereOr()
                    ->get();

        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    //kalau sales yang muncul 2, tapi purchase yang ga memiliki sales ga muncul. Kalau purchase, sales yang double ga muncul
    public function compilationDB(){

        $purchaseorder = DB::table('purchase_orders')
                        ->leftJoin('sales_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
                        ->leftJoin('other_incomes','purchase_orders.id','=','other_incomes.id_purchase_order')
                        ->leftJoin('other_costs','purchase_orders.id','=','other_costs.id_purchase_order')
                        ->leftJoin('rehiring_orders','sales_orders.id','=','rehiring_orders.id_sales_order')
                        ->leftJoin('vehicle_solds','sales_orders.id','=','vehicle_solds.id_sales_order')
                        ->select('sales_orders.*','other_incomes.*','purchase_orders.*','other_costs.*','rehiring_orders.*','vehicle_solds.*')
                        //->groupByRaw('agreement_number')
                        ->paginate(request()->per_page);
                        //->get();
        
        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function laporan($date1,$date2){

        $purchaseorder = DB::table('purchase_orders')
                        ->whereBetween('purchase_orders.hire_purchase_starting_date',[$date1,$date2])
                        ->select('purchase_orders.*')
                        ->get();
        
        if(count($purchaseorder) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    // public function showAgreementNumber($id){
    //     $purchaseorder = DB::table('purchase_orders')
    //                 ->join('sales_orders','sales_orders.id','=','purchase_orders.id_sales_order')
    //                 ->select('purchase_orders.*','sales_orders.agreement_no')
    //                 ->get();

    //     if(count($purchaseorder) > 0){
    //         return response([
    //             'message' => 'Retrieve All Success',
    //             'data' => $purchaseorder
    //         ],200);
    //     }

    //     return response([
    //         'message' => 'Empty',
    //         'data' => null
    //     ],400);
    // }

    

    //  public function showVehicleRehiringOrder($id){
    //      $purchaseorder = DB::table('purchase_orders')
    //                      ->join('sales_orders','sales_orders.id','=','purchase_orders.id_sales_order')
    //                      ->join('rehiring_orders','rehiring_orders.id','=','purchase_orders.id_rehiring_order')
    //                      ->select('purchase_orders.*','sales_orders.agreement_no','rehiring_orders.next_step')
    //                      ->whereRaw('purchase_orders.id = "'.$id.'"')
    //                      ->get();

    //      if(count($purchaseorder) > 0){
    //          return response([
    //              'message' => 'Retrieve All Success',
    //              'data' => $purchaseorder
    //          ],200);
    //      }
                
    //      return response([
    //          'message' => 'Empty',
    //          'data' => null
    //      ],400);
    //  }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_sales_order'                => 'nullable',
            'purchase_method'               => 'required|in:Hire Purchase,Cash,Rent/Return',
            'vehicle_registration'          => ['required', Rule::unique('purchase_orders')],
            'hp_finance_provider'           => 'nullable',
            'hire_purchase_starting_date'   => 'required|date_format:Y-m-d',
            'hp_interest_per_annum'         => 'nullable',
            'hp_deposit_amount'             => 'nullable',
            'hp_term'                       => 'nullable',
            'documentation_fees_pu'         => 'nullable',
            'final_fees'                    => 'nullable',
            'other_fees'                    => 'nullable',
            'price_otr'                     => 'required',
            'monthly_payment'               => 'nullable',
            'final_payment'                 => 'nullable',
            'hp_interest_type'              => 'nullable',
            'vehicle_manufactur'            => 'required',
            'vehicle_model'                 => 'required',
            'vehicle_variant'               => 'required',
            'basic_list_price'              => 'required',
            'residual_value'                => 'required',
            'colour'                        => 'required',
            'min_contract_price_satu'       => 'required',
            'min_contract_price_dua'        => 'required',
            'service_maintenance'           => 'nullable',
            'mot_due_date'                  => 'required|date_format:Y-m-d',
            'rfl_due_date'                  => 'required|date_format:Y-m-d',
            'service_schedule_miles'        => 'nullable',
            'service_schedule_years'        => 'nullable',
            'last_service_mileage'          => 'nullable',
            'last_service_date'             => 'nullable|date_format:Y-m-d',
            'financing_amount'              => 'nullable',
            'regular_monthly_payment'       => 'nullable',
            'status_next_step'              => 'nullable',
            'vehicle_tracking '             => 'nullable',
            'sum_docdepoth'                 => 'nullable',
            'tgl_available'                 => 'nullable',
            'stock_status'                  => 'nullable'
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        // $checkPurchaseOrderExist = PurchaseOrder::whereRaw('vehicle_registration = "'.$request->vehicle_registration.'" and status_next_step in ("Available", "Hired")')->get();
        //     if(count($checkPurchaseOrderExist) > 0){
        //      return response (['message' => 'Vehicle number cannot process'],400);
        //  }

        $purchaseorder = PurchaseOrder::create($storeData);

        $purchaseorder->hp_interest_per_annum = round($purchaseorder->hp_interest_per_annum,2);
        $purchaseorder->hp_deposit_amount = round($purchaseorder->hp_deposit_amount,2);
        $purchaseorder->documentation_fees_pu = round($purchaseorder->documentation_fees_pu,2);
        $purchaseorder->final_fees = round($purchaseorder->final_fees,2);
        $purchaseorder->other_fees = round($purchaseorder->other_fees,2);
        
        $purchaseorder->price_otr = round($purchaseorder->price_otr,2);
        $purchaseorder->monthly_payment = round($purchaseorder->monthly_payment,2);
        $purchaseorder->final_payment = round($purchaseorder->final_payment,2);
        
        $purchaseorder->residual_value = round($purchaseorder->residual_value,2);

        $purchaseorder->status_next_step = 'Available';

        //isi purchase date di tgl_available
        $purchaseorder->tgl_available = $purchaseorder->hire_purchase_starting_date;
        
        //isi vehicle stock status
        $purchaseorder->stock_status = $purchaseorder->stock_status;

        //except hire purchase, hp interest annum = o
        // if($purchaseorder->purchase_method == 'Hire Purchase'){
        //     $purchaseorder->hp_interest_per_annum = 0;
        // }
        
        //fo003 //Financing Amount
         if($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
             $purchaseorder->financing_amount = 0;
             $purchaseorder->save();
         } else if ($purchaseorder->purchase_method == 'Rent/Return') {
            $purchaseorder->financing_amount = round($purchaseorder->monthly_payment * $purchaseorder->hp_term,2);
            $purchaseorder->save();
        } else {
             if($purchaseorder->price_otr >= $purchaseorder->deposit) {
                $purchaseorder->financing_amount = round($purchaseorder->price_otr - $purchaseorder->hp_deposit_amount,2);
                $purchaseorder->save();
             } else if ($purchaseorder->deposit > $purchaseorder->price_otr){
                $purchaseorder->financing_amount = round(($purchaseorder->hp_deposit_amount - $purchaseorder->price_otr) * (-1),2);
                $purchaseorder->save();
             }
         }

          //fo004 //Regular Monthly Payment
          //fo004
        if($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->regular_monthly_payment = 0;
            $purchaseorder->save();
        } else {
            $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
            $purchaseorder->regular_monthly_payment = round($purchaseorder->monthly_payment + (($purchaseorder->financing_amount * $hp_interest_persen) / 12),2);
            $purchaseorder->save();

        }
          
        //fo0012 vehicle_tracking
        if($purchaseorder->purchase_method == 'Cash') {
            $purchaseorder->vehicle_tracking = 0;
            $purchaseorder->save();
        } else {
            $purchaseorder->vehicle_tracking = 8.67;
            $purchaseorder->save();
        }

        //fo009 //sum_docdepoth 
        $purchaseorder->sum_docdepoth = round($purchaseorder->documentation_fees_pu + $purchaseorder->hp_deposit_amount + $purchaseorder->other_fees,2);
        $purchaseorder->save();
        
        // $baseInterest = BaseInterest::whereRaw('status = "active"')->first();

        // $baseInterestDetail = BaseInterestDetail::create([
        //     'id_base_interest' => $baseInterest->id,
        //     'id_purchase_order' => $purchaseorder->id,
        //     'total_base_interest' => ($baseInterest->percentage/100) * $purchaseorder->price_otr
        // ]);

        return response([
            'message' => 'Add Purchase Order Success',
            'data' => $purchaseorder,
        ],200);
    }

    public function destroy($id){
        $purchaseorder = PurchaseOrder::find($id);

        if(is_null($purchaseorder)){
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ],404);
        }
        
        $deleteContract = SalesOrder::where('id_purchase_order',$purchaseorder->id)
                ->delete();

        $deleteRehiring = RehiringOrder::where('id_purchase_order',$purchaseorder->id)
                ->delete();
        
        $deleteVehicleSold = VehicleSold::where('id_purchase_order',$purchaseorder->id)
                ->delete();

        $deleteOtherCost = OtherCost::where('id_purchase_order',$purchaseorder->id)
                ->delete();

        $deleteOtherIncome = OtherIncome::where('id_purchase_order',$purchaseorder->id)
                ->delete();

        if($purchaseorder->delete()){
            return response([
                'message' => 'Delete Purchase Order Success',
                'data' => $purchaseorder,
            ],200);
        }
        
        return response([
            'message' => 'Delete Purchase Order Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $purchaseorder = PurchaseOrder::find($id);
        $oldFinancingAmount = $purchaseorder->financing_amount;
        if(is_null($purchaseorder)){
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_sales_order'                => 'nullable',
            'purchase_method'               => 'required|in:Hire Purchase,Cash,Rent/Return',
            'vehicle_registration'          => ['required', Rule::unique('purchase_orders')->ignore($purchaseorder)],
            'hp_finance_provider'           => 'nullable',
            'hire_purchase_starting_date'   => 'required|date_format:Y-m-d',
            'hp_interest_per_annum'         => 'nullable',
            'hp_deposit_amount'             => 'nullable',
            'hp_term'                       => 'nullable',
            'documentation_fees_pu'         => 'nullable',
            'final_fees'                    => 'nullable',
            'other_fees'                    => 'nullable',
            'price_otr'                     => 'required',
            'monthly_payment'               => 'nullable',
            'final_payment'                 => 'nullable',
            'hp_interest_type'              => 'nullable',
            'vehicle_manufactur'            => 'required',
            'vehicle_model'                 => 'required',
            'vehicle_variant'               => 'required',
            'basic_list_price'              => 'required',
            'residual_value'                => 'required',
            'colour'                        => 'required',
            'min_contract_price_satu'       => 'required',
            'min_contract_price_dua'        => 'required',
            'service_maintenance'           => 'nullable',
            'mot_due_date'                  => 'required|date_format:Y-m-d',
            'rfl_due_date'                  => 'required|date_format:Y-m-d',
            'service_schedule_miles'        => 'nullable',
            'service_schedule_years'        => 'nullable',
            'last_service_mileage'          => 'nullable',
            'last_service_date'             => 'nullable|date_format:Y-m-d',
            'financing_amount'              => 'nullable',
            'regular_monthly_payment'       => 'nullable',
            'status_next_step'              => 'nullable',
            'vehicle_tracking '             => 'nullable',
            'sum_docdepoth'                 => 'nullable',
            'tgl_available'                 => 'nullable',
            'stock_status'                  => 'nullable'
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        //$purchaseorder->id_sales_order                = $updateData['id_sales_order'];
        $purchaseorder->purchase_method               = $updateData['purchase_method'];
        $purchaseorder->vehicle_registration          = $updateData['vehicle_registration'];
        $purchaseorder->hp_finance_provider           = $updateData['hp_finance_provider'];
        $purchaseorder->hire_purchase_starting_date   = $updateData['hire_purchase_starting_date'];
        $purchaseorder->hp_interest_per_annum         = $updateData['hp_interest_per_annum'];
        $purchaseorder->hp_deposit_amount             = $updateData['hp_deposit_amount'];
        $purchaseorder->hp_term                       = $updateData['hp_term'];
        $purchaseorder->documentation_fees_pu         = $updateData['documentation_fees_pu'];
        $purchaseorder->final_fees                    = $updateData['final_fees'];
        $purchaseorder->other_fees                    = $updateData['other_fees'];
        $purchaseorder->price_otr                     = $updateData['price_otr'];
        $purchaseorder->monthly_payment               = $updateData['monthly_payment'];
        $purchaseorder->final_payment                 = $updateData['final_payment'];
        $purchaseorder->hp_interest_type              = $updateData['hp_interest_type'];
        $purchaseorder->vehicle_manufactur            = $updateData['vehicle_manufactur'];
        $purchaseorder->vehicle_model                 = $updateData['vehicle_model'];
        $purchaseorder->vehicle_variant               = $updateData['vehicle_variant'];
        $purchaseorder->basic_list_price              = $updateData['basic_list_price'];
        $purchaseorder->residual_value                = $updateData['residual_value'];
        $purchaseorder->colour                        = $updateData['colour'];
        $purchaseorder->min_contract_price_satu       = $updateData['min_contract_price_satu'];
        $purchaseorder->min_contract_price_dua        = $updateData['min_contract_price_dua'];
        $purchaseorder->service_maintenance           = $updateData['service_maintenance'];
        $purchaseorder->mot_due_date                  = $updateData['mot_due_date'];
        $purchaseorder->rfl_due_date                  = $updateData['rfl_due_date'];
        $purchaseorder->service_schedule_miles        = $updateData['service_schedule_miles'];
        $purchaseorder->service_schedule_years        = $updateData['service_schedule_years'];
        $purchaseorder->last_service_mileage          = $updateData['last_service_mileage'];
        $purchaseorder->last_service_date             = $updateData['last_service_date'];
        $purchaseorder->stock_status            = $updateData['stock_status'];
        //$purchaseorder->tgl_available                 = $updateData['tgl_available'];

        $purchaseorder->hp_interest_per_annum = round($purchaseorder->hp_interest_per_annum,2);
        $purchaseorder->hp_deposit_amount = round($purchaseorder->hp_deposit_amount,2);
        $purchaseorder->documentation_fees_pu = round($purchaseorder->documentation_fees_pu,2);
        $purchaseorder->final_fees = round($purchaseorder->final_fees,2);
        $purchaseorder->other_fees = round($purchaseorder->other_fees,2);
        $purchaseorder->price_otr = round($purchaseorder->price_otr,2);
        $purchaseorder->monthly_payment = round($purchaseorder->monthly_payment,2);
        $purchaseorder->final_payment = round($purchaseorder->final_payment,2);

        $purchaseorder->residual_value = round($purchaseorder->residual_value,2);

        $purchaseorder->tgl_available = $purchaseorder->hire_purchase_starting_date;
        
        //fo003 //Financing Amount
        if($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->financing_amount = 0;
            $purchaseorder->save();
        } else if ($purchaseorder->purchase_method == 'Rent/Return') {
            $purchaseorder->financing_amount = round($purchaseorder->monthly_payment * $purchaseorder->hp_term,2);
            $purchaseorder->save();
        } else {
            if($purchaseorder->price_otr >= $purchaseorder->deposit) {
               $purchaseorder->financing_amount = round($purchaseorder->price_otr - $purchaseorder->hp_deposit_amount,2);
               $purchaseorder->save();
            } else if ($purchaseorder->deposit > $purchaseorder->price_otr){
               $purchaseorder->financing_amount = round(($purchaseorder->hp_deposit_amount - $purchaseorder->price_otr) * (-1),2);
               $purchaseorder->save();
            }
        }

         //fo004 //Regular Monthly Payment
         //fo004
       if($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
           $purchaseorder->regular_monthly_payment = 0;
           $purchaseorder->save();
       } else {
           $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
           $purchaseorder->regular_monthly_payment = round($purchaseorder->monthly_payment + (($purchaseorder->financing_amount * $hp_interest_persen) / 12),2);
           $purchaseorder->save();

       }
         
       //fo0012 vehicle_tracking
       if($purchaseorder->purchase_method == 'Cash') {
           $purchaseorder->vehicle_tracking = 0;
           $purchaseorder->save();
       } else {
           $purchaseorder->vehicle_tracking = 8.67;
           $purchaseorder->save();
       }

       //fo009 //sum_docdepoth 
       $purchaseorder->sum_docdepoth = round($purchaseorder->documentation_fees_pu + $purchaseorder->hp_deposit_amount + $purchaseorder->other_fees,2);
       $purchaseorder->save();

        if($purchaseorder->save()){
            $baseInterest = BaseInterest::whereRaw('status = "active"')->first();
            $baseInterestDetail = BaseInterestDetail::whereRaw('id_purchase_order = "'.$id.'"')->get();

            foreach ($baseInterestDetail as $item) {
                $item->total_base_interest = (((($item->total_base_interest/$oldFinancingAmount) * 100 )/100) * $purchaseorder->financing_amount)/12;
                $item->save();
            }
            return response([
                'message' => 'Update Purchase Order Success',
                'data' => $purchaseorder,
            ],200);
        }

        return response([
            'message' => 'Update Purchase Order Failed',
            'data' => null
        ],400);
    }
    
    // public function showDashboard($date1,$date2){

    //     $purchaseorder = DB::table('sales_orders')
    //                             ->leftJoin('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
    //                             ->leftJoin('vehicle_solds','sales_orders.id','=','vehicle_solds.id_sales_order')
    //                             ->leftJoin('other_incomes','purchase_orders.id','=','other_incomes.id_purchase_order')
    //                             ->leftJoin('other_costs','purchase_orders.id','=','other_costs.id_purchase_order')
    //                             ->select('*')
    //                             ->whereBetween('sales_orders.contract_start_date',[$date1,$date2])
    //                             ->groupBy('agreement_number')
    //                             ->get();
        
    //     if(count($purchaseorder) > 0){
    //         return response([
    //             'message' => 'Retrieve All Success',
    //             'data' => $purchaseorder
    //         ],200);
    //     }
    
    //     return response([
    //         'message' => 'Empty',
    //         'data' => null
    //     ],400);
    // }
    
    
    public function showDashboard($date1, $date2)
    {
        $purchaseorder = DB::table('sales_orders')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->leftJoin('vehicle_solds', 'sales_orders.id', '=', 'vehicle_solds.id_sales_order')
            ->leftJoin('other_incomes', 'purchase_orders.id', '=', 'other_incomes.id_purchase_order')
            ->leftJoin('other_costs', 'purchase_orders.id', '=', 'other_costs.id_purchase_order')
            ->select(
                '*',
                DB::raw("DATE_ADD(sales_orders.contract_start_date, INTERVAL sales_orders.term_months MONTH) AS date_after_duration")
            )
            ->whereRaw("DATE_ADD(sales_orders.contract_start_date, INTERVAL sales_orders.term_months MONTH) BETWEEN ? AND ?", [$date1, $date2])
            // ->groupBy('date_after_duration')
            ->get();
    
        if (count($purchaseorder) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ], 200);
        }
    
        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function countVehicleHired($date1,$date2){
        
        $purchaseorder = DB::table('purchase_orders')
                    ->select('vehicle_registration')
                    ->whereBetween('purchase_orders.hire_purchase_starting_date',[$date1,$date2])
                    ->whereRaw('status_next_step in ("Hired")')
                    ->get()->count();
    
        if($purchaseorder != null){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }
                            
        return response([
                'message' => 'Empty',
                'data' => null
        ],400);
    }
    
    public function countVehicleSold($date1,$date2){
            
        $purchaseorder = DB::table('purchase_orders')
                    ->select('vehicle_registration')
                    ->whereBetween('purchase_orders.hire_purchase_starting_date',[$date1,$date2])
                    ->whereRaw('status_next_step in ("Sold")')
                    ->get()->count();
    
        if($purchaseorder != null){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorder
            ],200);
        }
                            
        return response([
                'message' => 'Empty',
                'data' => null
        ],400);
    }

}
