<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Support\Jsonable;
use Carbon\Carbon;
use Validator;
use App\Models\OtherCost;

class OtherCostController extends Controller
{
    public function index(Request $request){
        //$othercosts = OtherCost::all();

        $othercost = DB::table('other_costs')
                    ->join('purchase_orders','purchase_orders.id','=','other_costs.id_purchase_order')
                    ->select('other_costs.*','purchase_orders.vehicle_registration');
                    //->paginate(request()->per_page);
                    //->get();

        if ($s = $request->input('search')) {
            $othercost->whereRaw("vehicle_registration LIKE '%" . $s . "%'");
        }
                
        if ($sort = $request->input('sort')) {
            $othercost->orderBy(request()->sort, $request->input('order') );
        }
                
        $result = $othercost->paginate(request()->per_page);
                
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

    public function show($id){
        $othercost = OtherCost::find($id);

        if(!is_null($othercost)){
            return response([
                'message' => 'Retrieve Other Cost Success',
                'data' => $othercost
            ],200);
        }

        return response([
            'message' => 'Other Cost Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_purchase_order'            => 'required',
            'date'                         => 'required|date_format:Y-m-d',
            'description_expenses'         => 'required',
            'vendor_name'                  => 'required',
            'amount_oc'                    => 'required|numeric',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $othercost = OtherCost::create($storeData);
        return response([
            'message' => 'Add Other Cost Success',
            'data' => $othercost,
        ],200);
    }

    public function destroy($id){
        $othercost = OtherCost::find($id);

        if(is_null($othercost)){
            return response([
                'message' => 'Other Cost Not Found',
                'data' => null
            ],404);
        }

        if($othercost->delete()){
            return response([
                'message' => 'Delete Other Cost Success',
                'data' => $othercost,
            ],200);
        }
        
        return response([
            'message' => 'Delete Other Cost Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $othercost = OtherCost::find($id);
        if(is_null($othercost)){
            return response([
                'message' => 'Other Cost Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_purchase_order'            => 'required',
            'date'                         => 'required|date_format:Y-m-d',
            'description_expenses'         => 'required',
            'vendor_name'                  => 'required',
            'amount_oc'                    => 'required|numeric',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $othercost->id_purchase_order             = $updateData['id_purchase_order'];
        $othercost->date                          = $updateData['date'];
        $othercost->description_expenses          = $updateData['description_expenses'];
        $othercost->vendor_name                   = $updateData['vendor_name'];
        $othercost->amount_oc                     = $updateData['amount_oc'];
        
        if($othercost->save()){
            return response([
                'message' => 'Update Other Cost Success',
                'data' => $othercost,
            ],200);
        }

        return response([
            'message' => 'Update Other Cost Failed',
            'data' => null
        ],400);
    }
    
}

