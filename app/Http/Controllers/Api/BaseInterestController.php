<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Models\BaseInterest;
use App\Models\PurchaseOrder;
use App\Models\BaseInterestDetail;

class BaseInterestController extends Controller
{
    private $id;
    private $percentage;

    public function __construct() {
        $this->id = 0;
        $this->percentage = 0;
        $this->created_at = "";
    }

    public function index(){
        $baseinterest = BaseInterest::All();
        
                
         if(count($baseinterest) > 0){
             return response([
                 'message' => 'Retrieve All Success',
                 'data' => $baseinterest
             ],200);
        }

        // $purchaseorder = PurchaseOrder::whereRaw('id = "265"')->first();

        // $endDate = date('Y-m-d', strtotime($purchaseorder->hire_purchase_starting_date.  $purchaseorder->hp_term.' month'));
        // $createdAt = PurchaseOrder::selectRaw('DATE(created_at) as created_at')->whereRaw('id = 264')->first();

        // $date = Carbon::createFromFormat('Y-m-d', substr($createdAt->created_at, 0, 10));
        // $endDateCredit = Carbon::createFromFormat('Y-m-d', substr($endDate, 0, 10));

        // $result = $endDateCredit->greaterThanOrEqualTo($date);

        
        return response([
            'message' => 'Empty',
            'data' => $baseinterest
        ],400);
    }

    public function findBaseInterest(){
        
        $findbaseinterest = DB::table('base_interests')
                    ->select('base_interests.percentage')
                    ->whereRaw('status in ("active")')
                    ->get();

        if($findbaseinterest != null){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $findbaseinterest
            ],200);
        }
                            
        return response([
                'message' => 'Empty',
                'data' => null
        ],400);
    }

    public function show($id){
        $baseinterest = BaseInterest::find($id);

        if(!is_null($baseinterest)){
            return response([
                'message' => 'Retrieve Base Interest Success',
                'data' => $baseinterest
            ],200);
        }

        return response([
            'message' => 'Base Interest Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'start_date'           => 'nullable|date_format:Y-m-d',
            'end_date'             => 'nullable|date_format:Y-m-d',
            'percentage'           => 'required',
            'status'               => 'required',
            'month_interval'       => 'nullable',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $checkBaseInterest = BaseInterest::whereRaw('status in ("active")')->get();
        if(count($checkBaseInterest) > 0){
            return response (['message' => 'Base interest cannot be processed because status still active'],400);
        }

        $baseinterest = BaseInterest::create($storeData);
        
        $this->id = $baseinterest->id;
        $this->percentage = $baseinterest->percentage;
        $this->start_date = $baseinterest->start_date;
        

        $baseInterestDetail = BaseInterestDetail::groupBy("id_purchase_order")->get();
       
            foreach ($baseInterestDetail as $item) {
                $financingamount = PurchaseOrder::whereRaw('id = "'.$item->id_purchase_order.'"')->value('financing_amount');
                $purchaseorder = PurchaseOrder::whereRaw('id = "'.$item->id_purchase_order.'"')->first();
    
                $endDate = date('Y-m-d', strtotime($purchaseorder->hire_purchase_starting_date.  $purchaseorder->hp_term.' month'));
    
                $date = Carbon::createFromFormat('Y-m-d', substr($this->start_date, 0, 10));
                $endDateCredit = Carbon::createFromFormat('Y-m-d', substr($endDate, 0, 10));
    
                $result = $endDateCredit->greaterThanOrEqualTo($date);
    
                if($result){
                    $baseInterestDetail = BaseInterestDetail::create([
                        'id_base_interest' => $this->id,
                        'id_purchase_order' => $item->id_purchase_order,
                        'total_base_interest' => (($this->percentage/100) * $financingamount)/12
                    ]);
                }
    
            }
        
        
        return response([
            'message' => 'Add Base Interest Success',
            'data' => $baseinterest,
        ],200);
    }

    public function destroy($id){
        $baseinterest = BaseInterest::find($id);

        if(is_null($baseinterest)){
            return response([
                'message' => 'Base Interest Not Found',
                'data' => null
            ],404);
        }

        if($baseinterest->delete()){
            return response([
                'message' => 'Delete Base Interest Success',
                'data' => $baseinterest,
            ],200);
        }
        
        return response([
            'message' => 'Delete Base Interest Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $baseinterest = BaseInterest::find($id);
        if(is_null($baseinterest)){
            return response([
                'message' => 'Base Interest Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'start_date'           => 'nullable|date_format:Y-m-d',
            'percentage'           => 'required',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $baseinterest->start_date           = $updateData['start_date'];
        $baseinterest->percentage           = $updateData['percentage'];

        $purchaseorder = PurchaseOrder::find($baseinterest->id_purchase_order);

        $startDate = $baseinterest->start_date;
        $month = $purchaseorder->hp_term;
        $newDate = date('Y-m-d', strtotime($startDate.  $month.' month'));
        
        if($baseinterest->save()){
            return response([
                'message' => 'Update Base Interest Success',
                'data' => $baseinterest,
            ],200);
        }

        return response([
            'message' => 'Update Base Interest Failed',
            'data' => null
        ],400);
    }

    public function updateStatus(Request $request, $id){
        $baseinterest = BaseInterest::find($id);
        if(is_null($baseinterest)){
            return response([
                'message' => 'Base Interest Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'status'               => 'required',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $baseinterest->status   = $updateData['status'];

        $end_date_now = Carbon::now()->format('Y-m-d');
        $baseinterest->end_date = $end_date_now;
        
        if($baseinterest->save()){
            return response([
                'message' => 'Update Base Interest Success',
                'data' => $baseinterest,
            ],200);
        }

        return response([
            'message' => 'Update Base Interest Failed',
            'data' => null
        ],400);
    }

    //index -1, biar yang sebelumnya tetep kena
    public function showBaseInterest($date,$date2){

        $baseinterest= BaseInterest::whereRaw("DATE_SUB('".$date."', INTERVAL month_interval MONTH) <= start_date")
                       ->where('start_date', '<=', $date2)
                       ->orderBy('start_date')
                       // ->pluck('percentage');
                       ->get();
       
       if(count($baseinterest) > 0){
           return response([
               'message' => 'Retrieve All Success',
               'data' => $baseinterest
           ],200);
       }
   
       return response([
           'message' => 'Empty',
           'data' => null
       ],400);
   }
    
}
