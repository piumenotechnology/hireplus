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

class BaseInterestDetailController extends Controller
{
    public function sumTotalBaseInterest($id){
    $baseInterestDetail = DB::table('base_interest_details')
                    ->join('purchase_orders','purchase_orders.id','=','base_interest_details.id_purchase_order')
                    ->selectRaw('round(SUM(total_base_interest),2) as sum_total_base_interest')
                    ->whereRaw('purchase_orders.id = '.$id)
                    ->first();

    if($baseInterestDetail != null){
        return response([
            'message' => 'Retrieve All Success',
            'data' => $baseInterestDetail
        ],200);
    }

    return response([
        'message' => 'Empty',
        'data' => null
    ],400);

    }
    
    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_base_interest'            => 'nullable',
            'id_purchase_order'           => 'nullable',
            'total_base_interest'         => 'nullable',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $baseinterestdetail = BaseInterestDetail::create($storeData);

        $baseinterest = BaseInterest::find($baseinterestdetail->id_base_interest);
        $purchaseorder = PurchaseOrder::find($baseinterestdetail->id_purchase_order);

        $baseinterestdetail->total_base_interest = (($baseinterest->percentage/100) * $purchaseorder->price_otr)/12;
        $baseinterestdetail->save();

        return response([
            'message' => 'Add Other Cost Success',
            'data' => $baseinterestdetail,
        ],200);
    }
}

