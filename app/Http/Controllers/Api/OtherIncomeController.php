<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Models\OtherIncome;

class OtherIncomeController extends Controller
{
    public function index(Request $request){
        //$otherincomes = OtherIncome::all();

         $otherincome = DB::table('other_incomes')
                     ->join('purchase_orders','purchase_orders.id','=','other_incomes.id_purchase_order')
                     ->select('other_incomes.*','purchase_orders.vehicle_registration');
                     //->paginate(request()->per_page);
                     //->get();
                     
        if ($s = $request->input('search')) {
            $otherincome->whereRaw("vehicle_registration LIKE '%" . $s . "%'");
                            
        }
                
        if ($sort = $request->input('sort')) {
            $otherincome->orderBy(request()->sort, $request->input('order') );
        }
                
        $result = $otherincome->paginate(request()->per_page);
                
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
        $otherincome = OtherIncome::find($id);

        if(!is_null($otherincome)){
            return response([
                'message' => 'Retrieve Other Income Success',
                'data' => $otherincome
            ],200);
        }

        return response([
            'message' => 'Other Income Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_purchase_order'            => 'required',
            'date'                         => 'required|date_format:Y-m-d',
            'description_income'           => 'required',
            'amount_oi'                    => 'required|numeric',
            'payment_profile'              => 'required',
            
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $otherincome = OtherIncome::create($storeData);

        return response([
            'message' => 'Add Other Income Success',
            'data' => $otherincome,
        ],200);
    }

    public function destroy($id){
        $otherincome = OtherIncome::find($id);

        if(is_null($otherincome)){
            return response([
                'message' => 'Other Income Not Found',
                'data' => null
            ],404);
        }

        if($otherincome->delete()){
            return response([
                'message' => 'Delete Other Income Success',
                'data' => $otherincome,
            ],200);
        }
        
        return response([
            'message' => 'Delete Other Income Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $otherincome = OtherIncome::find($id);
        if(is_null($otherincome)){
            return response([
                'message' => 'Other Income Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_purchase_order'            => 'required',
            'date'                         => 'required|date_format:Y-m-d',
            'description_income'           => 'required',
            'amount_oi'                    => 'required|numeric',
            'payment_profile'              => 'required',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $otherincome->id_purchase_order             = $updateData['id_purchase_order'];
        $otherincome->date                          = $updateData['date'];
        $otherincome->description_income            = $updateData['description_income'];
        $otherincome->amount_oi                     = $updateData['amount_oi'];
        $otherincome->payment_profile               = $updateData['payment_profile'];

       
        if($otherincome->save()){
            return response([
                'message' => 'Update Other Income Success',
                'data' => $otherincome,
            ],200);
        }

        return response([
            'message' => 'Update Other Income Failed',
            'data' => null
        ],400);
    }
    
}

