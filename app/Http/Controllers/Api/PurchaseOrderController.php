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
    public function index()
    {
        $purchaseorders = PurchaseOrder::all();

        // $purchaseorder = DB::table('purchase_orders')
        //             ->join('sales_orders','sales_orders.id','=','purchase_orders.id_sales_order')
        //             ->select('purchase_orders.*','sales_orders.agreement_no')
        //             ->get();

        if (count($purchaseorders) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $purchaseorders
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function indexAll(Request $request)
    {
        //$purchaseorders = PurchaseOrder::all()
        // $purchaseorders = DB::table('purchase_orders')->paginate(request()->per_page);

        $query = PurchaseOrder::query();

        if ($s = $request->input('search')) {
            $query->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $query->orderBy(request()->sort, $request->input('order'));
        }

        $result = $query->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function showVehicleNumberinSales()
    {

        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.vehicle_registration')
            ->where('status_next_step', 'Available')
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

    public function show($id)
    {
        $purchaseorder = PurchaseOrder::find($id);

        if (!is_null($purchaseorder)) {
            return response([
                'message' => 'Retrieve Purchase Order Success',
                'data' => $purchaseorder
            ], 200);
        }

        return response([
            'message' => 'Purchase Order Not Found',
            'data' => null
        ], 400);
    }

    //
    public function showVehicle()
    {
        $purchaseorder = DB::table('purchase_orders')
            ->join('sales_orders', 'sales_orders.id', '=', 'purchase_orders.id_sales_order')
            ->select('purchase_orders.*', 'sales_orders.agreement_number')
            //->whereRaw('vehicle_registration = "'.$vehicle_number.'"')
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

    //show vehicle registration number in vehicle sold form
    //show vehicle number in sales order's form, show car status just available and hired only.
    public function showVehicleNumberExceptSold(Request $request)
    {
        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.*')
            ->whereRaw('status_next_step in ("Available", "Hired")');
        // ->get();

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        $result = $purchaseorder->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    //show original sales number in vehicle sold form
    public function showSalesNumberInVehicleSold()
    {
        $purchaseorder = DB::table('purchase_orders')
            ->join('sales_orders', 'sales_orders.id_purchase_order', '=', 'purchase_orders.id')
            ->select('purchase_orders.vehicle_registration', 'purchase_orders.status_next_step', 'sales_orders.agreement_number')
            ->whereRaw('status_next_step in ("Available", "Hired")')
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

    //
    public function listVehicleById($id)
    {
        $rehiringByPurchaseId = RehiringOrder::whereRaw('id_purchase_order = ' . $id)->first();

        if ($rehiringByPurchaseId != null) {
            $purchaseorder = SalesOrder::join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
                ->join('rehiring_orders', 'rehiring_orders.id_purchase_order', '=', 'purchase_orders.id')
                ->whereRaw('purchase_orders.id = ' . $id)
                ->first();
        } else {
            $purchaseorder = SalesOrder::join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
                ->whereRaw('purchase_orders.id = ' . $id)
                ->first();
        }
        if ($purchaseorder != null) {
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

    public function listVehicleInVehicleCard($id)
    {

        //$salesByPurchaseId = SalesOrder::whereRaw('id_purchase_order = '.$id)->first();

        $purchaseorder = DB::table('purchase_orders')
            ->leftJoin('sales_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->leftJoin('rehiring_orders', 'sales_orders.id', '=', 'rehiring_orders.id_sales_order')
            ->leftJoin('vehicle_solds', 'sales_orders.id', '=', 'vehicle_solds.id_sales_order')
            //->selectRaw('SUM(total_income_new) as total, purchase_orders.*, sales_orders.*, rehiring_orders.*, vehicle_solds.*')
            ->whereRaw('purchase_orders.id = ' . $id)
            // ->groupBy('agreement_number')
            //->sum('total_income_new')
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

    public function listCostInCard($id)
    {

        //$salesByPurchaseId = SalesOrder::whereRaw('id_purchase_order = '.$id)->first();

        $purchaseorder = DB::table('purchase_orders')
            ->leftJoin('sales_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->select('purchase_orders.*', 'sales_orders.total_cost')
            ->whereRaw('purchase_orders.id = ' . $id)->take(1)->get();
        //->groupBy('agreement_number')
        //->sum('total_income_new')



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

    public function listTotalInCard($id)
    {

        $otherIncomeByPurchaseId = OtherIncome::whereRaw('id_purchase_order = ' . $id)->first();
        $otherCostByPurchaseId = OtherCost::whereRaw('id_purchase_order = ' . $id)->first();

        if ($otherIncomeByPurchaseId != null && $otherCostByPurchaseId != null) {
            $purchaseorder = DB::table('sales_orders')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
                ->join('vehicle_solds', 'sales_orders.id', '=', 'vehicle_solds.id_sales_order')
                ->join('other_incomes', 'purchase_orders.id', '=', 'other_incomes.id_purchase_order')
                ->join('other_costs', 'purchase_orders.id', '=', 'other_costs.id_purchase_order')
                ->selectRaw('round((SUM(total_income) + SUM(amount_oi)),2) as sum_total_income, round(SUM(rental_income),2) as sum_rental_income,
                            round((AVG(total_cost) + SUM(amount_oc)),2) as sum_total_cost, purchase_orders.vehicle_registration,
                            round(((SUM(total_income) + amount_oi)-AVG(total_cost)),2) as margin, vehicle_solds.sold_price, sales_orders.residual_value')
                ->whereRaw('purchase_orders.id = ' . $id)
                ->get();
        } else if ($otherIncomeByPurchaseId == null && $otherCostByPurchaseId == null) {
            $purchaseorder = DB::table('sales_orders')
                ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
                ->leftJoin('vehicle_solds', 'purchase_orders.id', '=', 'vehicle_solds.id_purchase_order')
                ->selectRaw('round(SUM(total_income),2) as sum_total_income, round(SUM(rental_income),2) as sum_rental_income,
                            round(AVG(total_cost),2) as sum_total_cost, purchase_orders.vehicle_registration,
                            round(SUM(total_income)-AVG(total_cost),2) as margin, vehicle_solds.sold_price, sales_orders.residual_value')
                ->whereRaw('purchase_orders.id = ' . $id)
                ->get();
        }

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


    public function listTotalIncome($id)
    {
        $purchaseorder = DB::table('sales_orders')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->selectRaw('round(SUM(total_income),2) as sum_total_income')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function listTotalCost($id)
    {
        $purchaseorder = DB::table('sales_orders')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->selectRaw('round(AVG(total_cost),2) as sum_total_cost')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function listRentalIncome($id)
    {
        $purchaseorder = DB::table('sales_orders')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->selectRaw('round(SUM(rental_income),2) as sum_rental_income')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function listOtherIncome($id)
    {
        $purchaseorder = DB::table('other_incomes')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'other_incomes.id_purchase_order')
            ->selectRaw('round(SUM(amount_oi),2) as sum_other_income')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function listOtherCost($id)
    {
        $purchaseorder = DB::table('other_costs')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'other_costs.id_purchase_order')
            ->selectRaw('round(SUM(amount_oc),2) as sum_other_cost')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function listSoldPrice($id)
    {
        $purchaseorder = DB::table('vehicle_solds')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'vehicle_solds.id_purchase_order')
            ->selectRaw('round(SUM(sold_price),2) as sum_sold_price')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    //vehicle performance
    public function listResidualValue($id)
    {
        $purchaseorder = DB::table('purchase_orders')
            //->join('purchase_orders','purchase_orders.id','=','sales_orders.id_purchase_order')
            ->selectRaw('round(SUM(residual_value),2) as sum_residual_value')
            ->whereRaw('purchase_orders.id = ' . $id)
            ->first();

        if ($purchaseorder != null) {
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

    public function availableStock(Request $request)
    {
        //$rehiringorder = RehiringOrder::select('rehiring_orders.id_purchase_order')->get();

        //if($rehiringorder == null) {

        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.status_next_step', 'purchase_orders.eta')
            // ->whereRaw('status_next_step in ("Available")');
            // ->whereRaw('(stock_status = "Available" OR stock_status IS NULL)')
            ->whereRaw('status_next_step = "Available"');

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $purchaseorder->orderBy(request()->sort, $request->input('order'));
        }

        $result = $purchaseorder->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function potentialStock(Request $request)
    {
        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta')
            ->whereRaw('stock_status in ("Potential")');

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $purchaseorder->orderBy(request()->sort, $request->input('order'));
        }

        $result = $purchaseorder->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function bookedStock(Request $request)
    {
        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta')
            ->whereRaw('stock_status in ("Booked")');

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $purchaseorder->orderBy(request()->sort, $request->input('order'));
        }

        $result = $purchaseorder->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }
    public function confirmedStock(Request $request)
    {
        $purchaseorder = DB::table('purchase_orders')
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta')
            ->whereRaw('stock_status in ("Confirmed Return")');

        if ($s = $request->input('search')) {
            $purchaseorder->whereRaw("vehicle_registration LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_model LIKE '%" . $s . "%'")
                ->orWhereRaw("vehicle_manufactur LIKE '%" . $s . "%'");
        }

        if ($sort = $request->input('sort')) {
            $purchaseorder->orderBy(request()->sort, $request->input('order'));
        }

        $result = $purchaseorder->paginate(request()->per_page);

        if (count($result) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function changeStockStatus(Request $request, $id)
    {
        $purchaseorder = PurchaseOrder::find($id);
        if (is_null($purchaseorder)) {
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'stock_status'             => 'nullable',
        ]);

        if ($validate->fails())
            return response(['message' => $validate->errors()], 400);

        $purchaseorder->stock_status            = $updateData['stock_status'];

        if ($purchaseorder->save()) {
            return response([
                'message' => 'Update Vehicle Stock Status Success',
                'data' => $purchaseorder,
            ], 200);
        }

        return response([
            'message' => 'Update Vehicle Stock Status Failed',
            'data' => null
        ], 400);
    }

    public function changeEta(Request $request, $id)
    {
        $purchaseorder = PurchaseOrder::find($id);
        if (is_null($purchaseorder)) {
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'eta'             => 'nullable',
        ]);

        if ($validate->fails())
            return response(['message' => $validate->errors()], 400);

        $purchaseorder->eta            = $updateData['eta'];

        if ($purchaseorder->save()) {
            return response([
                'message' => 'Update Vehicle ETA Success',
                'data' => $purchaseorder,
            ], 200);
        }

        return response([
            'message' => 'Update Vehicle ETA Failed',
            'data' => null
        ], 400);
    }

    public function showVehicleNumber()
    {
        $rehiringorder = RehiringOrder::select('rehiring_orders.id_purchase_order')->get();

        $purchaseorder = DB::table('purchase_orders')
            ->select('id', 'vehicle_registration')
            ->whereNotIn('id', $rehiringorder)
            ->whereOr()
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

    public function showVehicleNumberInOtherCost()
    {
        $othercost = OtherCost::select('other_costs.id_purchase_order')->get();

        $purchaseorder = DB::table('purchase_orders')
            ->select('id', 'vehicle_registration')
            ->whereNotIn('id', $othercost)
            ->whereOr()
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

    public function showVehicleNumberInOtherIncome()
    {
        $otherincome = OtherIncome::select('other_incomes.id_purchase_order')->get();

        $purchaseorder = DB::table('purchase_orders')
            ->select('id', 'vehicle_registration')
            ->whereNotIn('id', $otherincome)
            ->whereOr()
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

    //kalau sales yang muncul 2, tapi purchase yang ga memiliki sales ga muncul. Kalau purchase, sales yang double ga muncul
    public function compilationDB()
    {

        $purchaseorder = DB::table('purchase_orders')
            ->leftJoin('sales_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->leftJoin('other_incomes', 'purchase_orders.id', '=', 'other_incomes.id_purchase_order')
            ->leftJoin('other_costs', 'purchase_orders.id', '=', 'other_costs.id_purchase_order')
            ->leftJoin('rehiring_orders', 'sales_orders.id', '=', 'rehiring_orders.id_sales_order')
            ->leftJoin('vehicle_solds', 'sales_orders.id', '=', 'vehicle_solds.id_sales_order')
            ->select('sales_orders.*', 'other_incomes.*', 'purchase_orders.*', 'other_costs.*', 'rehiring_orders.*', 'vehicle_solds.*')
            //->groupByRaw('agreement_number')
            ->paginate(request()->per_page);
        //->get();

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

    public function laporan($date1, $date2)
    {

        $purchaseorder = DB::table('purchase_orders')
            ->whereBetween('purchase_orders.hire_purchase_starting_date', [$date1, $date2])
            ->select('purchase_orders.*')
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

    public function store(Request $request)
    {
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

        if ($validate->fails())
            return response(['message' => $validate->errors()], 400);

        // $checkPurchaseOrderExist = PurchaseOrder::whereRaw('vehicle_registration = "'.$request->vehicle_registration.'" and status_next_step in ("Available", "Hired")')->get();
        //     if(count($checkPurchaseOrderExist) > 0){
        //      return response (['message' => 'Vehicle number cannot process'],400);
        //  }

        $purchaseorder = PurchaseOrder::create($storeData);

        $purchaseorder->hp_interest_per_annum = round($purchaseorder->hp_interest_per_annum, 2);
        $purchaseorder->hp_deposit_amount = round($purchaseorder->hp_deposit_amount, 2);
        $purchaseorder->documentation_fees_pu = round($purchaseorder->documentation_fees_pu, 2);
        $purchaseorder->final_fees = round($purchaseorder->final_fees, 2);
        $purchaseorder->other_fees = round($purchaseorder->other_fees, 2);

        $purchaseorder->price_otr = round($purchaseorder->price_otr, 2);
        $purchaseorder->monthly_payment = round($purchaseorder->monthly_payment, 2);
        $purchaseorder->final_payment = round($purchaseorder->final_payment, 2);

        $purchaseorder->residual_value = round($purchaseorder->residual_value, 2);

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
        if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->financing_amount = 0;
            $purchaseorder->save();
        } else if ($purchaseorder->purchase_method == 'Rent/Return') {
            $purchaseorder->financing_amount = round($purchaseorder->monthly_payment * $purchaseorder->hp_term, 2);
            $purchaseorder->save();
        } else {
            if ($purchaseorder->price_otr >= $purchaseorder->deposit) {
                $purchaseorder->financing_amount = round($purchaseorder->price_otr - $purchaseorder->hp_deposit_amount, 2);
                $purchaseorder->save();
            } else if ($purchaseorder->deposit > $purchaseorder->price_otr) {
                $purchaseorder->financing_amount = round(($purchaseorder->hp_deposit_amount - $purchaseorder->price_otr) * (-1), 2);
                $purchaseorder->save();
            }
        }

        //fo004 //Regular Monthly Payment
        //fo004
        if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->regular_monthly_payment = 0;
            $purchaseorder->save();
        } else {
            $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
            $purchaseorder->regular_monthly_payment = round($purchaseorder->monthly_payment + (($purchaseorder->financing_amount * $hp_interest_persen) / 12), 2);
            $purchaseorder->save();
        }

        //fo0012 vehicle_tracking
        if ($purchaseorder->purchase_method == 'Cash') {
            $purchaseorder->vehicle_tracking = 0;
            $purchaseorder->save();
        } else {
            $purchaseorder->vehicle_tracking = 8.67;
            $purchaseorder->save();
        }

        //fo009 //sum_docdepoth
        $purchaseorder->sum_docdepoth = round($purchaseorder->documentation_fees_pu + $purchaseorder->hp_deposit_amount + $purchaseorder->other_fees, 2);
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
        ], 200);
    }

    public function destroy($id)
    {
        $purchaseorder = PurchaseOrder::find($id);

        if (is_null($purchaseorder)) {
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ], 404);
        }

        $deleteContract = SalesOrder::where('id_purchase_order', $purchaseorder->id)
            ->delete();

        $deleteRehiring = RehiringOrder::where('id_purchase_order', $purchaseorder->id)
            ->delete();

        $deleteVehicleSold = VehicleSold::where('id_purchase_order', $purchaseorder->id)
            ->delete();

        $deleteOtherCost = OtherCost::where('id_purchase_order', $purchaseorder->id)
            ->delete();

        $deleteOtherIncome = OtherIncome::where('id_purchase_order', $purchaseorder->id)
            ->delete();

        if ($purchaseorder->delete()) {
            return response([
                'message' => 'Delete Purchase Order Success',
                'data' => $purchaseorder,
            ], 200);
        }

        return response([
            'message' => 'Delete Purchase Order Failed',
            'data' => null,
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $purchaseorder = PurchaseOrder::find($id);
        $oldFinancingAmount = $purchaseorder->financing_amount;
        if (is_null($purchaseorder)) {
            return response([
                'message' => 'Purchase Order Not Found',
                'data' => null
            ], 404);
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

        if ($validate->fails())
            return response(['message' => $validate->errors()], 400);

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

        $purchaseorder->hp_interest_per_annum = round($purchaseorder->hp_interest_per_annum, 2);
        $purchaseorder->hp_deposit_amount = round($purchaseorder->hp_deposit_amount, 2);
        $purchaseorder->documentation_fees_pu = round($purchaseorder->documentation_fees_pu, 2);
        $purchaseorder->final_fees = round($purchaseorder->final_fees, 2);
        $purchaseorder->other_fees = round($purchaseorder->other_fees, 2);
        $purchaseorder->price_otr = round($purchaseorder->price_otr, 2);
        $purchaseorder->monthly_payment = round($purchaseorder->monthly_payment, 2);
        $purchaseorder->final_payment = round($purchaseorder->final_payment, 2);

        $purchaseorder->residual_value = round($purchaseorder->residual_value, 2);

        $purchaseorder->tgl_available = $purchaseorder->hire_purchase_starting_date;

        //fo003 //Financing Amount
        if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->financing_amount = 0;
            $purchaseorder->save();
        } else if ($purchaseorder->purchase_method == 'Rent/Return') {
            $purchaseorder->financing_amount = round($purchaseorder->monthly_payment * $purchaseorder->hp_term, 2);
            $purchaseorder->save();
        } else {
            if ($purchaseorder->price_otr >= $purchaseorder->deposit) {
                $purchaseorder->financing_amount = round($purchaseorder->price_otr - $purchaseorder->hp_deposit_amount, 2);
                $purchaseorder->save();
            } else if ($purchaseorder->deposit > $purchaseorder->price_otr) {
                $purchaseorder->financing_amount = round(($purchaseorder->hp_deposit_amount - $purchaseorder->price_otr) * (-1), 2);
                $purchaseorder->save();
            }
        }

        //fo004 //Regular Monthly Payment
        //fo004
        if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
            $purchaseorder->regular_monthly_payment = 0;
            $purchaseorder->save();
        } else {
            $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
            $purchaseorder->regular_monthly_payment = round($purchaseorder->monthly_payment + (($purchaseorder->financing_amount * $hp_interest_persen) / 12), 2);
            $purchaseorder->save();
        }

        //fo0012 vehicle_tracking
        if ($purchaseorder->purchase_method == 'Cash') {
            $purchaseorder->vehicle_tracking = 0;
            $purchaseorder->save();
        } else {
            $purchaseorder->vehicle_tracking = 8.67;
            $purchaseorder->save();
        }

        //fo009 //sum_docdepoth
        $purchaseorder->sum_docdepoth = round($purchaseorder->documentation_fees_pu + $purchaseorder->hp_deposit_amount + $purchaseorder->other_fees, 2);
        $purchaseorder->save();

        if ($purchaseorder->save()) {
            $baseInterest = BaseInterest::whereRaw('status = "active"')->first();
            $baseInterestDetail = BaseInterestDetail::whereRaw('id_purchase_order = "' . $id . '"')->get();

            foreach ($baseInterestDetail as $item) {
                $item->total_base_interest = (((($item->total_base_interest / $oldFinancingAmount) * 100) / 100) * $purchaseorder->financing_amount) / 12;
                $item->save();
            }
            return response([
                'message' => 'Update Purchase Order Success',
                'data' => $purchaseorder,
            ], 200);
        }

        return response([
            'message' => 'Update Purchase Order Failed',
            'data' => null
        ], 400);
    }

    public function showDashboard($date1, $date2)
    {
        $salesOrders = DB::table('sales_orders as so')
            ->join('purchase_orders as po', 'so.id_purchase_order', '=', 'po.id')
            ->leftJoin(DB::raw("(SELECT id_purchase_order,
                                SUM((monthly_rental * term_months) + (initial_rental + documentation_fees + other_income)) AS New_Total_income
                                FROM sales_orders
                                GROUP BY id_purchase_order) as income"),
                                'so.id_purchase_order', '=', 'income.id_purchase_order')
            ->select(
                'so.*',
                'po.*',
                'so.id_purchase_order as newid',
                 'income.New_Total_income',
                // 'po.vehicle_registration',
                // 'po.status_next_step',
                DB::raw("DATE_ADD(so.contract_start_date, INTERVAL so.term_months  MONTH) AS date_after_duration_income"),
                DB::raw("DATE_ADD(po.hire_purchase_starting_date, INTERVAL COALESCE(po.hp_term, 0) MONTH) AS date_after_duration_cost"),
            )
            ->whereRaw('DATE_ADD(so.contract_start_date, INTERVAL (so.term_months - 1) MONTH) >= ?', [$date1])
            ->whereRaw('so.contract_start_date <= ?', [$date2])
            // ->groupBy('so.id_purchase_order', 'income.New_Total_income', 'po.id')
            ->orderBy('po.vehicle_registration', 'ASC')
            ->get();

        $purchaseorder = DB::table('purchase_orders')
            ->leftJoin('vehicle_solds as vs', 'vs.id_purchase_order', '=', 'purchase_orders.id')
            ->select(
                'purchase_orders.*',
                'vs.*',
                'purchase_orders.id as purchase_id',
                DB::raw("DATE_ADD(purchase_orders.hire_purchase_starting_date, INTERVAL COALESCE(purchase_orders.hp_term, 0) MONTH) AS date_after_duration_cost"),
                DB::raw("(SELECT COUNT(*) FROM purchase_orders WHERE status_next_step = 'Available') AS available_cars_count"),
                DB::raw("(SELECT SUM((regular_monthly_payment + vehicle_tracking) * hp_term ) FROM `purchase_orders` WHERE status_next_step = 'Available') AS avaliable_cars_cost"),
                DB::raw('COALESCE((SELECT SUM(base_interest_details.total_base_interest)
                FROM base_interest_details
                WHERE base_interest_details.id_purchase_order = purchase_orders.id), 0)
                AS total_base_interest'),
            )
            ->whereRaw('DATE_ADD(purchase_orders.hire_purchase_starting_date, INTERVAL COALESCE(purchase_orders.hp_term - 1, 0) MONTH) >= ?', [$date1])
            ->whereRaw('purchase_orders.hire_purchase_starting_date <= ?', [$date2])
            ->orderBy('purchase_orders.id', 'ASC')
            ->get();

        // $contract_sibling = DB::table('sales_orders')
        //         ->select('*')
        //         ->where('id_purchase_order', '=', $salesOrders->id_purchase_order)
        //         ->get();


        $modifiedData = [];
        $rentalData = [];
        $leasingData = [];

        $total_residual_value = 0;
        $count_contracts = 0;
        $countVehicleCost = 0;
        $countVehicleIncome = 0;

        $projected_income = 0;

        $total_cost = 0;
        $rental = 0;

        $forecasting_income = 0;
        $forecasting_cost = 0;
        $forecasting_cost_in_rental = 0;

        $count_data = 0;

        //debuging
        // $carsIncome = [];
        // $carsCost = [];
        // $totalMonth_rental = 0;
        // $totalMonth_cost = 0;

        //count rental
        foreach ($salesOrders as $item) {
            $start = new \DateTime($date1);
            $end = new \DateTime($date2);
            $current = clone $start;
            $dateStartContract = new \DateTime($item->contract_start_date);
            $dateEndContract = new \DateTime($item->date_after_duration_income);

            $originalIncomeDate = (int) $dateEndContract->format('d');

            $current->setDate($start->format('Y'), $start->format('m'), min($originalIncomeDate, $start->format('t')));

            if ($current <= $start) {
                $current->modify('first day of next month');
                $current->setDate($current->format('Y'), $current->format('m'), min($originalIncomeDate, $current->format('t')));//add new
            }

            if($dateStartContract > $start){
                $current = $dateStartContract;
            }

            // $date_modif = [];
            // $check_date_now = $current->format('Y-m-d'); //debuging

            //count income if active contact
            $countMonth = 0;
            $monthlyIncome = 0;
            $cekTotal_income = 0;
            if ($item->next_step_status_sales === 'Hired') {
                while ($current <= $end and $current <= $dateEndContract and $current < $dateEndContract) {
                    // $date_modif[] = $current->format('Y-m-d'); //debuging
                    $countMonth++;
                    $current->modify('first day of next month');
                    $current->setDate($current->format('Y'), $current->format('m'), min($originalIncomeDate, $current->format('t')));
                }
                $monthlyIncome = $item->monthly_rental * $countMonth;
                $count_contracts++;
                $cekTotal_income = $countMonth ? ($item->monthly_rental * $item->term_months) + ($item->initial_rental + $item->documentation_fees + $item->other_income) : 0;
            }

            // $totalMonth_rental += $countMonth; //debuging
            $rental += $monthlyIncome;

            //forcasting cost
            $dateEndHire = new \DateTime($item->date_after_duration_cost);
            $cek_total_cost = 0;
            // $date_modif_cost = []; //debuging
            if ($dateEndHire >= $start and $item->purchase_method !== "Cash" and $item->status_next_step !== "Sold" ) {
                $cek_total_cost = ($item->regular_monthly_payment + $item->vehicle_tracking) * $item->hp_term + ($item->total_base_interest ?? 0);
                $count_data++;
            }

            $forecasting_cost_in_rental += $cek_total_cost;

            //forcasting margin income
            $forecasting_income += $cekTotal_income;

            //count cars
            if ($item->next_step_status_sales === 'Hired' and  $item->status_next_step ==='Hired') {
                $countVehicleIncome++;
                // $carsIncome[] =[$item->vehicle_registration , $item->agreement_number]; //debuging
            }

            if($item->next_step_status_sales === 'Hired'){
                $projected_income += $item->New_Total_income;
            }

            // Store data
            $rentalData[] = [
                'id' => $item->newid,
                'vehicle_registration' => $item->vehicle_registration,
                'agreement_number' => $item->agreement_number,
                'contract_start_date' => $item->contract_start_date,
                'contract_end_date' => $item->date_after_duration_income,
                'status_contract' => $item->next_step_status_sales,
                'count_month_rental' => $countMonth,
                'monthly_rental' => round($item->monthly_rental, 2),
                'rental_income' => round($monthlyIncome,2),
                'income_forcasting' => round($cekTotal_income,2),
                // 'total_income_forcasting' => round($item->New_Total_income,2),

                // 'total_cost_forcasting' => round($cek_total_cost,2),
                // 'purchase_method' => $item->purchase_method,
                // 'status_vehicle' => $item->status_next_step,
                // 'date_after_duration_cost' => $item->date_after_duration_cost,

                // 'date' => $date_modif, //debuging
                // 'cek' => $check_date_now, //debuging

                // 'hp_payment' => round($subTotal,2),
                // 'month_cost' => $countDatePaid,
                // 'cost' => round($cost,2)
                // 'margin' => round($monthlyIncome - $subTotal, 2),
            ];
        }

        //count cost
        foreach ($purchaseorder as $leasing) {
            $start = new \DateTime($date1);
            $end = new \DateTime($date2);
            $dateStartHire = new \DateTime($leasing->hire_purchase_starting_date);
            $dateEndHire = new \DateTime($leasing->date_after_duration_cost);

            $currentPaid = clone $start;
            $originalDay = (int) $dateEndHire->format('d');

            $currentPaid->setDate($start->format('Y'), $start->format('m'), min($originalDay, $start->format('t')));

            if ($currentPaid <= $start) {
                $currentPaid->modify('first day of next month');
                $currentPaid->setDate($currentPaid->format('Y'), $currentPaid->format('m'), min($originalDay, $currentPaid->format('t')));
            }

            if($dateStartHire > $start){
                $currentPaid = $dateStartHire;
            }

            $countDatePaid = 0;
            $cek_total_cost = 0;
            // $date_modif_cost = []; //debuging
            if ($dateEndHire >= $start and $leasing->purchase_method !== "Cash" and $leasing->status_next_step !== "Sold" ) {
                while ($currentPaid <= $end && $currentPaid <= $dateEndHire and $currentPaid < $dateEndHire) {
                    // $date_modif_cost[] = $currentPaid->format('Y-m-d'); //debuging
                    $countDatePaid++;
                    $currentPaid->modify('first day of next month');
                    $currentPaid->setDate($currentPaid->format('Y'), $currentPaid->format('m'), min($originalDay, $currentPaid->format('t')));
                }
                $cek_total_cost = $countDatePaid > 0 ? ($leasing->regular_monthly_payment + $leasing->vehicle_tracking) * $leasing->hp_term + ($leasing->total_base_interest ?? 0) : 0;
            }

            $forecasting_cost += $cek_total_cost;

            // $totalMonth_cost += $countDatePaid; //debuging

            //Calculate total cost
            $cost = $subTotal = 0;
            if($countDatePaid > 0 and $leasing->status_next_step != "Sold" and $leasing->purchase_method !== "Cash" ) {
                $subTotal = $leasing->regular_monthly_payment + $leasing->vehicle_tracking;
                $cost = ($subTotal * $countDatePaid) + ($leasing->total_base_interest ?? 0);
            }
            $total_cost += $cost;

            // Residual value calculation
            $data_residual = 0;
            if ($leasing->status_next_step == 'Sold') {
                $data_residual = $leasing->residual_value;
            }

            $total_residual_value += $data_residual;

            if($leasing->status_next_step === "Hired"){
                $countVehicleCost++;
                // $carsCost[] = $leasing->vehicle_registration; //debuging
            }

            $leasingData [] = [
                "vehicle number" => $leasing->vehicle_registration,
                "hire_purchase_start_date" => $leasing->hire_purchase_starting_date,
                "hire_purchase_end_date" => $leasing->date_after_duration_cost,
                "purchase_method" => $leasing->purchase_method,
                "status_vehicle" => $leasing->status_next_step,
                "sold_date" => $leasing->vehicle_sold_date,
                "count_month" => $countDatePaid,
                "regular_monthly_payment" => round($subTotal, 2),
                "hp_payment" => round($cost, 2),
                "base_interest" => round($leasing->total_base_interest ?? 0, 2),
                "residual_value" => round($data_residual, 2),
                "forrecasting_cost" => round($cek_total_cost, 2),
                // "available_cost" => $leasing->avaliable_cars_cost,
                // "date" => $date_modif_cost //debuging
            ];
        }

        $total_income = $rental + $total_residual_value;
        $margin = $rental - $total_cost;
        $profitMargin = $rental > 0 ? round(($margin / $rental) * 100, 2) : 0;

        $total_vehicle = $countVehicleIncome + $leasing->available_cars_count;

        // $avg_projected_margin = ($forecasting_income - ($forecasting_cost + $leasing->avaliable_cars_cost)) / $count_contracts;
        // $avg_projected_margin = ($forecasting_income - ($forecasting_cost_in_rental + $leasing->avaliable_cars_cost)) / $count_contracts; //cost in rental
        // $avg_projected_margin = $forecasting_income / $count_contracts;

        $avg_projected_margin = ($projected_income - ($forecasting_cost_in_rental + $leasing->avaliable_cars_cost)) / $count_contracts; //cost in rental

        // Final structured data
        $modifiedData = [
            'actual_income' => round($rental, 2),
            'actual_cost' => round($total_cost, 2),
            'total_residual' => round($total_residual_value, 2),
            'total_income' => round($total_income, 2),
            'margin' => round($margin, 2),
            'margin_percentage' => $profitMargin,
            'total_contract' => $count_contracts,
            'total_vehicle' => $total_vehicle,
            // 'count_vehicle_cost' => $countVehicleCost,
            // 'total_vehicle_income' => $countVehicleIncome ,

            // 'data'=> $count_data,
            // 'cars_income' => $carsIncome,
            // 'cars_count' => $carsCost,
            // 'totalMonth_rental' => $totalMonth_rental,
            // 'totalMonth_cost' => $totalMonth_cost,
            // 'vehicle_in_cost' => $countVehicleCost,

            'forecasting_income' => round($forecasting_income, 2),
            'forecasting_cost' => round($forecasting_cost + $leasing->avaliable_cars_cost, 2),
            'percentage_forecasting' => round(($avg_projected_margin / $forecasting_income) * 100,5),
            'avg_forecasting_income' => round($count_contracts > 0 ? $avg_projected_margin : 0, 2),
        ];

        if (count($salesOrders) > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data_rental' => $rentalData,
                'data_leasing' => $leasingData,
                'data' => $modifiedData
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 400);
    }

    public function countVehicleHired($date1, $date2)
    {

        $purchaseorder = DB::table('purchase_orders')
            ->select('vehicle_registration')
            ->whereBetween('purchase_orders.hire_purchase_starting_date', [$date1, $date2])
            ->whereRaw('status_next_step in ("Hired")')
            ->get()->count();

        if ($purchaseorder != null) {
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

    public function countVehicleSold($date1, $date2)
    {

        $purchaseorder = DB::table('purchase_orders')
            ->select('vehicle_registration')
            ->whereBetween('purchase_orders.hire_purchase_starting_date', [$date1, $date2])
            ->whereRaw('status_next_step in ("Sold")')
            ->get()->count();

        if ($purchaseorder != null) {
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

}
