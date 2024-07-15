<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator;
use App\Mileage;


class MileageController extends Controller
{
    public function index(){
        $mileages = DB::table('mileages')
                    ->join('purchase_orders','purchase_orders.id','=','mileages.id_purchase_order')
                    ->select('mileages.*','purchase_orders.vehicle_registration')
                    ->get();

        if(count($mileages) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $mileages
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function listMileageById($id){
        $mileages = DB::table('mileages')
                    ->join('purchase_orders','purchase_orders.id','=','mileages.id_purchase_order')
                    ->select('mileages.*','purchase_orders.vehicle_registration')
                    ->whereRaw('purchase_orders.id = '.$id)
                    ->get();

        if(count($mileages) > 0){
            return response([
                'message' => 'Retrieve All Success',
                'data' => $mileages
            ],200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ],400);
    }

    public function show($id){
        $mileage = Mileage::find($id);

        if(!is_null($mileage)){
            return response([
                'message' => 'Retrieve Mileage Success',
                'data' => $mileage
            ],200);
        }

        return response([
            'message' => 'Mileage Not Found',
            'data' => null
        ],400);
    }

    public function store(Request $request){
        $storeData = $request->all();
        $validate = Validator::make($storeData, [
            'id_purchase_order'            => 'required',
            'tgl_mileage'                  => 'required|date_format:Y-m-d',
            'jumlah_mileage'               => 'required',
        ]);

        if($validate->fails())
            return response (['message' => $validate->errors()],400);

        $mileage = Mileage::create($storeData);
        return response([
            'message' => 'Add Mileage Success',
            'data' => $mileage,
        ],200);
    }

    public function destroy($id){
        $mileage = Mileage::find($id);

        if(is_null($mileage)){
            return response([
                'message' => 'Mileage Not Found',
                'data' => null
            ],404);
        }

        if($mileage->delete()){
            return response([
                'message' => 'Delete Mileage Success',
                'data' => $mileage,
            ],200);
        }
        
        return response([
            'message' => 'Delete Mileage Failed',
            'data' => null,
        ],400);

    }

    public function update(Request $request, $id){
        $mileage = Mileage::find($id);
        if(is_null($mileage)){
            return response([
                'message' => 'Mileage Not Found',
                'data' => null
            ],404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_purchase_order'            => 'required',
            'tgl_mileage'                  => 'required|date_format:Y-m-d',
            'jumlah_mileage'               => 'required',
        ]);

        if($validate->fails())
        return response(['message' => $validate->errors()],400);

        $mileage->id_purchase_order             = $updateData['id_purchase_order'];
        $mileage->tgl_mileage                   = $updateData['tgl_mileage'];
        $mileage->jumlah_mileage                = $updateData['jumlah_mileage'];
        
        if($mileage->save()){
            return response([
                'message' => 'Update Mileage Success',
                'data' => $mileage,
            ],200);
        }

        return response([
            'message' => 'Update Mileage Failed',
            'data' => null
        ],400);
    }
    
}
