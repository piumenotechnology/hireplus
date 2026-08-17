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
            ->select('*')
            ->whereIn('status_next_step', ['Available', 'Hired']);

        if ($s = $request->input('search')) {
            $purchaseorder->where(function ($query) use ($s) {
                $query->where('vehicle_registration', 'like', '%' . $s . '%')
                    ->orWhere('vehicle_manufactur', 'like', '%' . $s . '%');
            });
        }

        $perPage = $request->input('per_page', 10); // default to 10 if not provided
        $result = $purchaseorder->paginate($perPage);

        if ($result->count() > 0) {
            return response([
                'message' => 'Retrieve All Success',
                'data' => $result
            ], 200);
        }

        return response([
            'message' => 'Empty',
            'data' => null
        ], 404); // 404 is more suitable for "not found" than 400 (bad request)
    }


    //show contract number in other income form
    public function showContractNumberInOtherIncome($id)
    {
        $purchaseorder = DB::table('purchase_orders')
            ->join('sales_orders', 'sales_orders.id_purchase_order', '=', 'purchase_orders.id')
            ->select('sales_orders.id','sales_orders.agreement_number', 'sales_orders.next_step_status_sales')
            ->whereRaw('sales_orders.next_step_status_sales in ("Innactive", "Hired")')
            ->where('sales_orders.id_purchase_order', $id)
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
        $purchaseorder = DB::table('purchase_orders')
            ->leftJoin('sales_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
            ->leftJoin('rehiring_orders', 'sales_orders.id', '=', 'rehiring_orders.id_sales_order')
            ->leftJoin('vehicle_solds', 'sales_orders.id', '=', 'vehicle_solds.id_sales_order')
            ->leftJoin(DB::raw('(SELECT id_sales_order, SUM(amount_oi) as total_other_income 
                                FROM other_incomes 
                                GROUP BY id_sales_order) as oi'), 
                    'sales_orders.id', '=', 'oi.id_sales_order')
            ->selectRaw('purchase_orders.*, 
                        sales_orders.*, 
                        rehiring_orders.*, 
                        vehicle_solds.*, 
                        COALESCE(oi.total_other_income,0) as other_income')
            ->where('purchase_orders.id', $id)
            ->get();

        foreach ($purchaseorder as $po) {
            // Calculate total income with already-summed other_income
            if ($po->status_next_step === 'Sold') {
                $po->total_income = round(
                    ($po->first_payment ?? 0) +
                    (($po->monthly_rental ?? 0) * ($po->margin_term ?? 0)) +
                    ($po->sold_price ?? 0) +
                    ($po->other_income ?? 0), 2
                );
            } else {
                $po->total_income = round(
                    ($po->first_payment ?? 0) +
                    (($po->monthly_rental ?? 0) * ($po->margin_term ?? 0)) +
                    ($po->other_income ?? 0), 2
                );
            }
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
        // Cash POs don't use the hire-purchase schedule, so skip the heavy
        // recursive CTE queries and compute the total cost directly.
        $po = DB::table('purchase_orders')
            ->where('id', $id)
            ->select('purchase_method', 'price_otr')
            ->first();
 
        if ($po && $po->purchase_method === 'Cash') {
            $totalOtherCost = (float) DB::table('other_costs')
                ->where('id_purchase_order', $id)
                ->sum('amount_oc');
 
            $cashTotalCost = round((float) $po->price_otr + $totalOtherCost, 2);
 
            return response([
                'message' => 'Retrieve All Success',
                'data'    => [
                    'sum_total_cost'     => $cashTotalCost, //projected_total_cost
                    'current_total_cost' => $cashTotalCost,
                    'est_current_settlement' => 0,

                ]
            ], 200);
        }
 
        $total_cost_projected = "
            WITH RECURSIVE
            effective_terms AS (
                SELECT
                    po.id                                               AS po_id,
                    po.hp_term,
                    (
                        SELECT so2.next_step_status_sales
                        FROM sales_orders so2
                        WHERE so2.id_purchase_order = po.id
                        ORDER BY so2.id DESC
                        LIMIT 1
                    )                                                   AS last_next_step_status,
                    LEAST(
                        po.hp_term,
                        CASE
                            -- Sold: stop the projection at the vehicle sold date
                            WHEN (
                                SELECT so2.next_step_status_sales
                                FROM sales_orders so2
                                WHERE so2.id_purchase_order = po.id
                                ORDER BY so2.id DESC
                                LIMIT 1
                            ) = 'Sold'
                            AND (
                                SELECT MAX(vs.vehicle_sold_date)
                                FROM vehicle_solds vs
                                WHERE vs.id_purchase_order = po.id
                            ) IS NOT NULL
                            THEN TIMESTAMPDIFF(
                                    MONTH,
                                    po.hire_purchase_starting_date,
                                    (SELECT MAX(vs.vehicle_sold_date)
                                     FROM vehicle_solds vs
                                     WHERE vs.id_purchase_order = po.id)
                                 ) + 1
                            WHEN (
                                SELECT so2.next_step_status_sales
                                FROM sales_orders so2
                                WHERE so2.id_purchase_order = po.id
                                ORDER BY so2.id DESC
                                LIMIT 1
                            ) = 'Innactive'
                            THEN TIMESTAMPDIFF(MONTH, po.hire_purchase_starting_date, CURDATE()) + 1
                            ELSE
                                TIMESTAMPDIFF(
                                    MONTH,
                                    po.hire_purchase_starting_date,
                                    DATE_ADD(
                                        (SELECT so2.contract_start_date
                                        FROM sales_orders so2
                                        WHERE so2.id_purchase_order = po.id
                                        ORDER BY so2.id DESC
                                        LIMIT 1),
                                        INTERVAL
                                        (SELECT so2.margin_term
                                        FROM sales_orders so2
                                        WHERE so2.id_purchase_order = po.id
                                        ORDER BY so2.id DESC
                                        LIMIT 1)
                                        MONTH
                                    )
                                )
                        END
                    )                                                   AS effective_term
                FROM purchase_orders po
                WHERE po.purchase_method != 'Cash'
            ),
 
            other_costs_total AS (
                SELECT
                    oc.id_purchase_order                                AS po_id,
                    COALESCE(SUM(oc.amount_oc), 0)                     AS total_other_cost
                FROM other_costs oc
                GROUP BY oc.id_purchase_order
            ),
 
            seq AS (
                SELECT
                    po.id                                               AS po_id,
                    po.vehicle_registration,
                    1                                                   AS month_num,
                    et.effective_term,
                    et.last_next_step_status,
                    po.hp_term,
                    po.price_otr,
                    po.hp_deposit_amount,
                    po.hp_interest_per_annum,
                    po.monthly_payment,
                    po.final_payment,
                    po.documentation_fees_pu,
                    po.final_fees,
                    po.hire_purchase_starting_date,
                    (po.price_otr - po.hp_deposit_amount)               AS financing
                FROM purchase_orders po
                INNER JOIN effective_terms et ON et.po_id = po.id
                WHERE po.purchase_method != 'Cash'
                AND et.effective_term > 0
 
                UNION ALL
 
                SELECT
                    po_id, vehicle_registration, month_num + 1,
                    effective_term, last_next_step_status, hp_term,
                    price_otr, hp_deposit_amount, hp_interest_per_annum,
                    monthly_payment, final_payment, documentation_fees_pu,
                    final_fees, hire_purchase_starting_date, financing
                FROM seq
                WHERE month_num < effective_term
            ),
 
            calc AS (
                SELECT
                    s.*,
                    DATE_ADD(s.hire_purchase_starting_date, INTERVAL (s.month_num - 1) MONTH) AS calc_date,
                    (s.financing * s.hp_interest_per_annum / 100.0) / 12                       AS interest
                FROM seq s
            ),
 
            calc_with_base AS (
                SELECT
                    c.*,
                    (
                        SELECT bi.percentage
                        FROM base_interests bi
                        WHERE bi.start_date <= c.calc_date
                        ORDER BY bi.start_date DESC
                        LIMIT 1
                    ) AS base_rate
                FROM calc c
            )
 
            SELECT
                ROUND(
                    MAX(cwb.hp_deposit_amount)
                    + (MAX(cwb.monthly_payment) * cwb.effective_term)
                    + SUM(cwb.interest)
                    + SUM((cwb.financing * IFNULL(cwb.base_rate, 0) / 100.0) / 12)
                    + MAX(cwb.documentation_fees_pu)
                    + MAX(cwb.final_fees)
                    + MAX(cwb.final_payment)
                    + COALESCE(MAX(oct.total_other_cost), 0)
                    + (CASE
                           WHEN cwb.last_next_step_status = 'Sold' THEN 0
                           ELSE (cwb.hp_term - cwb.effective_term) * MAX(cwb.monthly_payment)
                       END)
                , 2) AS sum_total_cost
            FROM calc_with_base cwb
            LEFT JOIN other_costs_total oct ON oct.po_id = cwb.po_id
            WHERE cwb.po_id = ?
            GROUP BY
                cwb.po_id,
                cwb.vehicle_registration,
                cwb.hp_term,
                cwb.effective_term,
                cwb.last_next_step_status,
                cwb.hire_purchase_starting_date,
                cwb.hp_interest_per_annum
        ";
 
        $total_cost_live = "
            WITH RECURSIVE
            effective_terms AS (
                SELECT
                    po.id                                                       AS po_id,
                    po.hp_term,
                    po.hire_purchase_starting_date,
                    (
                        SELECT so2.next_step_status_sales
                        FROM sales_orders so2
                        WHERE so2.id_purchase_order = po.id
                        ORDER BY so2.id DESC
                        LIMIT 1
                    )                                                           AS last_status,
                    LEAST(
                        po.hp_term,
                        TIMESTAMPDIFF(MONTH, po.hire_purchase_starting_date, CURDATE())
                    )                                                           AS effective_term
                FROM purchase_orders po
                WHERE po.purchase_method != 'Cash'
                  -- Sold agreements are skipped entirely (vehicle has left the fleet)
                  AND (
                        SELECT so2.next_step_status_sales
                        FROM sales_orders so2
                        WHERE so2.id_purchase_order = po.id
                        ORDER BY so2.id DESC
                        LIMIT 1
                  ) <> 'Sold'
            ),
 
            other_costs_sum AS (
                SELECT
                    id_purchase_order                                           AS po_id,
                    SUM(amount_oc)                                              AS total_other_cost
                FROM other_costs
                GROUP BY id_purchase_order
            ),
 
            seq AS (
                SELECT
                    po.id                                                       AS po_id,
                    po.vehicle_registration,
                    1                                                           AS month_num,
                    et.effective_term,
                    et.last_status,
                    po.hp_term,
                    po.price_otr,
                    po.hp_deposit_amount,
                    po.hp_interest_per_annum,
                    po.monthly_payment,
                    po.final_payment,
                    po.documentation_fees_pu,
                    po.final_fees,
                    po.hire_purchase_starting_date,
                    (po.price_otr - po.hp_deposit_amount)                       AS financing
                FROM purchase_orders po
                INNER JOIN effective_terms et ON et.po_id = po.id
                WHERE po.purchase_method != 'Cash'
                AND et.effective_term > 0
 
                UNION ALL
 
                SELECT
                    po_id, vehicle_registration, month_num + 1,
                    effective_term, last_status, hp_term,
                    price_otr, hp_deposit_amount, hp_interest_per_annum,
                    monthly_payment, final_payment, documentation_fees_pu,
                    final_fees, hire_purchase_starting_date, financing
                FROM seq
                WHERE month_num < effective_term
            ),
 
            calc AS (
                SELECT
                    s.*,
                    DATE_ADD(s.hire_purchase_starting_date, INTERVAL (s.month_num - 1) MONTH) AS calc_date,
                    (s.financing * s.hp_interest_per_annum / 100.0) / 12                       AS interest
                FROM seq s
            ),
 
            calc_with_base AS (
                SELECT
                    c.*,
                    (
                        SELECT bi.percentage
                        FROM base_interests bi
                        WHERE bi.start_date <= c.calc_date
                        ORDER BY bi.start_date DESC
                        LIMIT 1
                    )                                                           AS base_rate
                FROM calc c
            )
 
            SELECT
                ROUND(
                    MAX(cwb.hp_deposit_amount)
                    + MAX(cwb.monthly_payment) * MAX(cwb.effective_term)
                    + ROUND(SUM(cwb.interest), 2)
                    + MAX(cwb.final_fees)
                    + MAX(cwb.documentation_fees_pu)
                    + ROUND(SUM((cwb.financing * IFNULL(cwb.base_rate, 0) / 100.0) / 12), 2)
                    + COALESCE(MAX(oc.total_other_cost), 0)
                , 2)                                                            AS sum_total_cost_live
            FROM calc_with_base cwb
            LEFT JOIN other_costs_sum oc ON oc.po_id = cwb.po_id
            WHERE cwb.po_id = ?
            GROUP BY
                cwb.po_id,
                cwb.vehicle_registration
        ";
        
        $current_settlement = "
                            SELECT
                                po.vehicle_registration,
                                po.hp_term,
                                po.final_payment,
                                po.monthly_payment,
                                po.hp_term - TIMESTAMPDIFF(MONTH, po.hire_purchase_starting_date, CURDATE()) AS remaining_months,
                                ROUND(
                                    ((po.hp_term - TIMESTAMPDIFF(MONTH, po.hire_purchase_starting_date, CURDATE())) * po.monthly_payment)
                                    + po.final_payment
                                ) AS current_settlement
                            FROM purchase_orders po
                            WHERE po.id = ?
                            AND po.hp_term - TIMESTAMPDIFF(MONTH, po.hire_purchase_starting_date, CURDATE()) > 0";

        $result1 = DB::selectOne($total_cost_projected, [$id]);
        $result2 = DB::selectOne($total_cost_live, [$id]);
        $result3 = DB::selectOne($current_settlement, [$id]);
 
        if ($result1 !== null || $result2 !== null) {
            return response([
                'message' => 'Retrieve All Success',
                'data'    => [
                    'sum_total_cost'   => $result1->sum_total_cost ?? null, //projected_total_cost
                    'current_total_cost' => $result2->sum_total_cost_live ?? null,
                    'est_current_settlement' => $result3->current_settlement ?? null,
                ]
            ], 200);
        }
 
        return response([
            'message' => 'Empty',
            'data'    => null
        ], 400);
    }

    // Outstanding balance on the hire purchase as of today: the total cost of
    // the vehicle minus everything already paid (deposit + the instalments that
    // have fallen due, current month included).
    public function currentSettlement($id)
    {
        $vehicle = DB::table('purchase_orders as po')
            ->leftJoin('sales_orders as so', 'so.id_purchase_order', '=', 'po.id')
            ->where('po.id', $id)
            ->orderBy('so.id', 'desc')
            ->select([
                'po.vehicle_registration',
                'po.hire_purchase_starting_date',
                'po.hp_term',
                'po.monthly_payment',
                'po.hp_deposit_amount',
                'so.total_cost',
            ])
            ->first();

        if ($vehicle === null) {
            return response([
                'message' => 'Empty',
                'data'    => null
            ], 400);
        }

        $hpTerm         = (int) $vehicle->hp_term;
        $monthlyPayment = (float) $vehicle->monthly_payment;
        $depositAmount  = (float) $vehicle->hp_deposit_amount;
        $totalCost      = (float) $vehicle->total_cost;

        $ongoing           = 0;
        $runningPayment    = 0.0;
        $currentSettlement = 0.0;

        // No HP schedule (Cash PO) or no cost recorded yet => nothing to settle.
        if ($hpTerm && $monthlyPayment && $totalCost) {
            // Whole calendar months between the HP start and today, counting the
            // current month as an instalment that has already fallen due.
            $start = Carbon::parse($vehicle->hire_purchase_starting_date);
            $today = Carbon::now();

            $ongoing = ($today->month - $start->month)
                + 12 * ($today->year - $start->year)
                + 1;

            $runningPayment = ($monthlyPayment * $ongoing) + $depositAmount;

            // Past the end of the term the agreement is fully paid off.
            $currentSettlement = $ongoing > $hpTerm
                ? 0.0
                : max(0, round($totalCost - $runningPayment, 2));
        }

        return response([
            'message' => 'Retrieve All Success',
            'data'    => [
                'vehicle_registration'        => $vehicle->vehicle_registration,
                'hire_purchase_starting_date' => $vehicle->hire_purchase_starting_date,
                'hp_term'                     => $hpTerm,
                'monthly_payment'             => $monthlyPayment,
                'hp_deposit_amount'           => $depositAmount,
                'total_cost'                  => $totalCost,
                'ongoing_term'                => $ongoing,
                'running_payment'             => round($runningPayment, 2),
                'current_settlement'          => $currentSettlement,
            ]
        ], 200);
    }

    public function listRentalIncome($id)
    {   
        $purchaseorder = DB::table('sales_orders')
        ->join('purchase_orders', 'purchase_orders.id', '=', 'sales_orders.id_purchase_order')
        ->selectRaw("
            ROUND(
                SUM(
                    (
                        sales_orders.monthly_rental *
                        CASE
                            WHEN LOWER(TRIM(BOTH FROM sales_orders.next_step_status_sales)) = 'sold'
                                THEN sales_orders.margin_term + 1
                            ELSE sales_orders.margin_term
                        END
                    ) + sales_orders.first_payment
                )
            ) AS sum_rental_income
        ")
        ->where('purchase_orders.id', $id)
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
       // Sum directly in SQL, no selectRaw+first noise
        $sumOtherIncome = DB::table('other_incomes')
            ->where('id_purchase_order', $id)
            ->sum('amount_oi'); // returns 0 if no rows

        // Round for response, keep it numeric
        $sumOtherIncome = round((float) $sumOtherIncome, 2);

        // Pull the related rows
        $otherIncome = DB::table('other_incomes')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'other_incomes.id_purchase_order')
            ->leftJoin('sales_orders', 'sales_orders.id', '=', 'other_incomes.id_sales_order')
            ->where('other_incomes.id_purchase_order', $id)
            ->select(
                'other_incomes.*',
                'sales_orders.agreement_number',
                'purchase_orders.vehicle_registration'
            )
            ->get();

        // Always return 200, even if empty, this is a successful fetch
        return response()->json([
            'message' => 'OK',
            'sum_other_income' => $sumOtherIncome,
            'data' => $otherIncome,
        ]);
    }

    public function listOtherCost($id)
    {
        $othercost = DB::table('other_costs')
            ->leftJoin('purchase_orders', 'purchase_orders.id', '=', 'other_costs.id_purchase_order')
            ->where('other_costs.id_purchase_order', $id)
            ->select('purchase_orders.vehicle_registration', 'other_costs.*')
            ->get();

        // $sumOtherCost = $othercost->sum('amount_oc');

        $sumOtherCost = DB::table('other_costs')
        ->where('id_purchase_order', $id)
        ->sum('amount_oc');

        return response() -> json([
            'message' => 'Retrieve All Success',
            'sum_other_cost' => $sumOtherCost,
            'data' => $othercost,
        ]);
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
        $purchaseorder = DB::table('purchase_orders')
        ->leftJoinSub(
            DB::table('sales_orders')
                ->select(
                    'id',
                    'id_purchase_order',
                    'contract_start_date',
                    'next_step_status_sales',
                    DB::raw('DATE_ADD(contract_start_date, INTERVAL term_months MONTH) AS end_contract')
                )
                ->whereRaw('id IN (SELECT MAX(id) FROM sales_orders GROUP BY id_purchase_order)'),
            'latest_sales',
            'purchase_orders.id',
            '=',
            'latest_sales.id_purchase_order'
        )
        ->select(
            'purchase_orders.id',
            'purchase_orders.vehicle_registration',
            'purchase_orders.vehicle_manufactur',
            'purchase_orders.vehicle_model',
            'purchase_orders.colour',
            'purchase_orders.vehicle_variant',
            'purchase_orders.min_contract_price_satu',
            'purchase_orders.min_contract_price_dua',
            'purchase_orders.stock_status',
            'purchase_orders.status_next_step',
            'purchase_orders.eta',
            'purchase_orders.residual_value',
            'latest_sales.next_step_status_sales',
            'latest_sales.contract_start_date',
            'latest_sales.end_contract'
        )
        ->whereNotNull('purchase_orders.stock_status')
        ->where('purchase_orders.stock_status', '!=', 'Potential')
        ->where('purchase_orders.stock_status', '!=', 'Booked');

        if ($s = $request->input('search')) {
            $purchaseorder->where(function ($query) use ($s) {
                $query->where('vehicle_registration', 'like', "%$s%")
                    ->orWhere('vehicle_model', 'like', "%$s%")
                    ->orWhere('vehicle_manufactur', 'like', "%$s%");
            });
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
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta', 'purchase_orders.status_next_step', 'purchase_orders.residual_value')
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
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta', 'purchase_orders.status_next_step','purchase_orders.residual_value')
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
            ->select('purchase_orders.id', 'purchase_orders.vehicle_registration', 'purchase_orders.vehicle_manufactur', 'purchase_orders.vehicle_model', 'purchase_orders.colour', 'purchase_orders.vehicle_variant', 'purchase_orders.min_contract_price_satu', 'purchase_orders.min_contract_price_dua', 'purchase_orders.stock_status', 'purchase_orders.eta', 'purchase_orders.status_next_step','purchase_orders.residual_value')
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
        $purchaseorder->stock_status = $purchaseorder->stock_status || 'Available';

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

        // Update Sales Order
        $salesOrder = SalesOrder::where('id_purchase_order', $id)
            ->where('next_step_status_sales', 'Hired')
            ->first();

        if ($salesOrder) {
            // Calculate and update values
            $salesOrder->total_monthly_rental = $purchaseorder->regular_monthly_payment * 11;

            // fo006 annum_payment
            if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
                $salesOrder->annum_payment = 0;
            } else {
                $salesOrder->annum_payment = round($purchaseorder->monthly_payment * $salesOrder->term_months, 2);
            }

            // Sales final payment
            $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
            if ($purchaseorder->purchase_method == 'Rent/Return') {
                $salesOrder->sales_final_payment = round($purchaseorder->financing_amount - $salesOrder->total_monthly_rental, 2);
            } else {
                $salesOrder->sales_final_payment = round($purchaseorder->financing_amount * (1 + $hp_interest_persen) - $salesOrder->total_monthly_rental, 2);
            }

            // fo007 settlement
            if ($purchaseorder->purchase_method != 'Hire Purchase' && $purchaseorder->purchase_method != 'Rent/Return') {
                $salesOrder->settlement = round($purchaseorder->price_otr, 2);
            } else if ($purchaseorder->purchase_method != 'Cash' && $purchaseorder->purchase_method != 'Rent/Return') {
                $hp_interest_persen = $purchaseorder->hp_interest_per_annum / 100;
                $salesOrder->settlement = round(($purchaseorder->financing_amount * (($salesOrder->term_month / 12) + $hp_interest_persen) - $salesOrder->annum_payment), 2);
            } else {
                $salesOrder->settlement = 0;
            }

            // fo008 penalty_early_settlement
            if ($purchaseorder->purchase_method == 'Hire Purchase') {
                $salesOrder->penalty_early_settlement = round(($salesOrder->sales_final_payment * $hp_interest_persen) / 11, 2);
            } else {
                $salesOrder->penalty_early_settlement = 0;
            }

            // fo0011 total_cost
            if ($purchaseorder->purchase_method != 'Cash') {
                $salesOrder->total_cost = round($purchaseorder->sum_docdepoth + $salesOrder->total_monthly_rental + $salesOrder->sales_final_payment + $salesOrder->penalty_early_settlement + $purchaseorder->final_fees + ($purchaseorder->vehicle_tracking * 11), 2);
            } else {
                $salesOrder->total_cost = round($purchaseorder->price_otr, 2);
            }

            $salesOrder->save();
        }

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

    // public function showDashboard($date1, $date2)
    // {
    //     $purchaseorder = DB::table('purchase_orders')
    //         ->join('sales_orders as so', 'purchase_orders.id', '=', 'so.id_purchase_order')
    //         ->leftJoin(DB::raw("(SELECT id_purchase_order, SUM(amount_oi) as total_oi
    //                             FROM other_incomes
    //                             GROUP BY id_purchase_order) as oti"),
    //                             'oti.id_purchase_order', '=', 'purchase_orders.id')   // ✅ Pre-aggregated subquery
    //         ->leftJoin('vehicle_solds as vs', 'vs.id_purchase_order', '=', 'purchase_orders.id')
    //         ->select(
    //             'purchase_orders.*',
    //             'vs.*',
    //             'oti.total_oi as total_other_income_rental',                                            // ✅ Already summed, no SUM() needed
    //             DB::raw('SUM(so.rental_income) as total_rental_income'),
    //             'purchase_orders.id as purchase_id',
    //             DB::raw("DATE_ADD(purchase_orders.hire_purchase_starting_date, INTERVAL COALESCE(purchase_orders.hp_term, 0) MONTH) AS date_after_duration_cost"),
    //             DB::raw("(SELECT COUNT(*) FROM purchase_orders WHERE status_next_step = 'Available') AS available_cars_count"),
    //             DB::raw("(SELECT SUM((regular_monthly_payment + vehicle_tracking) * hp_term) FROM purchase_orders WHERE status_next_step = 'Available') AS available_cars_cost"),
    //             DB::raw('COALESCE((SELECT SUM(base_interest_details.total_base_interest)
    //                     FROM base_interest_details
    //                     WHERE base_interest_details.id_purchase_order = purchase_orders.id), 0)
    //                     AS total_base_interest'),
    //         )
    //         ->whereRaw('DATE_ADD(purchase_orders.hire_purchase_starting_date, INTERVAL COALESCE(purchase_orders.hp_term - 1, 0) MONTH) >= ?', [$date1])
    //         ->whereRaw('purchase_orders.hire_purchase_starting_date <= ?', [$date2])
    //         ->groupBy(
    //             'purchase_orders.id',
    //             'vs.id',
    //             'oti.total_oi',                                                       // ✅ Group by the pre-aggregated value
    //             'purchase_orders.hire_purchase_starting_date',
    //             'purchase_orders.hp_term',
    //             'purchase_orders.status_next_step',
    //             'purchase_orders.regular_monthly_payment',
    //             'purchase_orders.vehicle_tracking',
    //         )
    //         ->orderBy('purchase_orders.id', 'ASC')
    //         ->get();

    //     $salesOrders = DB::table('sales_orders as so')
    //         ->join('purchase_orders as po', 'so.id_purchase_order', '=', 'po.id')
    //         ->leftJoin(DB::raw("(SELECT id_purchase_order,
    //                             SUM((monthly_rental * term_months) + (initial_rental + documentation_fees + other_income)) AS New_Total_income
    //                             FROM sales_orders
    //                             GROUP BY id_purchase_order) as income"),
    //                             'so.id_purchase_order', '=', 'income.id_purchase_order')
    //         ->leftJoin('other_incomes as oti', 'so.id', '=', 'oti.id_sales_order')
    //         ->select(
    //             'so.*',
    //             'po.*',
    //             DB::raw('SUM(oti.amount_oi) as total_other_income_rental'),  // ✅ Wrapped in DB::raw()
    //             'so.id_purchase_order as newid',
    //             'income.New_Total_income',
    //             DB::raw("DATE_ADD(so.contract_start_date, INTERVAL so.term_months MONTH) AS date_after_duration_income"),
    //             DB::raw("DATE_ADD(po.hire_purchase_starting_date, INTERVAL COALESCE(po.hp_term, 0) MONTH) AS date_after_duration_cost"),
    //         )
    //         ->whereRaw('DATE_ADD(so.contract_start_date, INTERVAL (so.term_months - 1) MONTH) >= ?', [$date1])
    //         ->whereRaw('so.contract_start_date <= ?', [$date2])
    //         ->groupBy(                                     // ✅ Added groupBy
    //             'so.id',
    //             'po.id',
    //             'so.id_purchase_order',
    //             'income.New_Total_income',
    //             'so.contract_start_date',
    //             'so.term_months',
    //             'po.hire_purchase_starting_date',
    //             'po.hp_term',
    //             'po.vehicle_registration'
    //         )
    //         ->orderBy('po.vehicle_registration', 'ASC')
    //         ->get();

    //     $modifiedData = [];
    //     $rentalData = [];
    //     $leasingData = [];

    //     $rental = 0;
    //     $total_cost = 0;
    //     $projected_cost = 0;
    //     $count_contracts = 0;
    //     $projected_income = 0;
    //     $countVehicleCost = 0;
    //     $countVehicleIncome = 0;
    //     $total_residual_value = 0;
    //     $projected_cost_in_rental = 0;
    //     $sum_all_projected_income = 0;

    //     //debuging
    //     // $count_data = 0;
    //     // $carsIncome = [];
    //     // $carsCost = [];
    //     // $totalMonth_rental = 0;
    //     // $totalMonth_cost = 0;
    //     // $checking_total_income = 0;
    //     // $checking_total_rental = 0;

    //     //count rental
    //     foreach ($salesOrders as $item) {

    //         $start = new \DateTime($date1);
    //         $end = new \DateTime($date2);
    //         $current = clone $start;
    //         $dateStartContract = new \DateTime($item->contract_start_date);
    //         $dateEndContract = new \DateTime($item->date_after_duration_income);

    //         $originalIncomeDate = (int) $dateEndContract->format('d');

    //         $current->setDate($start->format('Y'), $start->format('m'), min($originalIncomeDate, $start->format('t')));

    //         if ($current <= $start) {
    //             $current->modify('first day of next month');
    //             $current->setDate($current->format('Y'), $current->format('m'), min($originalIncomeDate, $current->format('t')));//add new
    //         }

    //         if($dateStartContract > $start){
    //             $current = $dateStartContract;
    //         }

    //         // $date_modif = [];
    //         // $check_date_now = $current->format('Y-m-d'); //debuging

    //         //count income if active contact
    //         $countMonth = 0;
    //         $monthlyIncome = 0;
    //         $cekTotal_income = 0;

    //         if ($item->next_step_status_sales === 'Hired') {
    //             while ($current <= $end and $current <= $dateEndContract and $current < $dateEndContract) {
    //                 // $date_modif[] = $current->format('Y-m-d'); //debuging
    //                 $countMonth++;
    //                 $current->modify('first day of next month');
    //                 $current->setDate($current->format('Y'), $current->format('m'), min($originalIncomeDate, $current->format('t')));
    //             }
    //             $count_contracts++;
    //             $monthlyIncome = $item->monthly_rental * $countMonth;
    //             $cekTotal_income = $countMonth ? ($item->monthly_rental * $item->term_months) + ($item->initial_rental + $item->documentation_fees + $item->other_income) : 0;
    //         }

    //         // $totalMonth_rental += $countMonth; //debuging
    //         $rental += $monthlyIncome;

    //         //projected margin income
    //         $projected_income += $cekTotal_income;

    //         //projected cost
    //         $dateEndHire = new \DateTime($item->date_after_duration_cost);
    //         $projected_total_cost = 0;
    //         if ($dateEndHire >= $start and $item->purchase_method !== "Cash" and $item->status_next_step !== "Sold") {
    //             $projected_total_cost = ($item->regular_monthly_payment + $item->vehicle_tracking) * $item->hp_term + ($item->total_base_interest ?? 0);
    //             // $count_data++; //debuging
    //         }

    //         $projected_cost_in_rental += $projected_total_cost;

    //         //count cars
    //         if ($item->next_step_status_sales === 'Hired' and  $item->status_next_step ==='Hired') {
    //             $countVehicleIncome++;
    //             // $carsIncome[] =[$item->vehicle_registration , $item->agreement_number]; //debuging
    //         }

    //         if($item->next_step_status_sales === 'Hired'){
    //             $sum_all_projected_income += $item->New_Total_income;
    //         }

    //         // Store data
    //         $rentalData[] = [
    //             // 'id' => $item->newid,
    //             'agreement_number' => $item->agreement_number,
    //             'contract_start_date' => $item->contract_start_date,
    //             'contract_end_date' => $item->date_after_duration_income,
    //             'status_contract' => $item->next_step_status_sales,
    //             'count_month_rental' => $countMonth,
    //             'vehicle_registration' => $item->vehicle_registration,
    //             'id_purchase' => $item->newid,
    //             'monthly_rental' => round($item->monthly_rental, 2),
    //             'rental_income' => round($monthlyIncome,2),
    //             // 'total_income' => round($item->total_income,2),
    //             'total_rental' => round($item->rental_income,2),
    //             'other_income' => round($item->total_other_income_rental, 2),
    //             'residual' => round($item->residual_value,2)
    //             // 'income' => $income,
    //             // 'purchased_method' => $item->purchase_method ,


    //             // 'income_forcasting_' => round($total_income_,2),
    //             // 'income_forcasting' => round($cekTotal_income,2),
    //             // 'total_income_forcasting' => round($item->New_Total_income,2),

    //             // 'total_cost_forcasting' => round($cek_total_cost,2),
    //             // 'purchase_method' => $item->purchase_method,
    //             // 'status_vehicle' => $item->status_next_step,
    //             // 'date_after_duration_cost' => $item->date_after_duration_cost,

    //             // 'date' => $date_modif, //debuging
    //             // 'cek' => $check_date_now, //debuging

    //             // 'hp_payment' => round($subTotal,2),
    //             // 'month_cost' => $countDatePaid,
    //             // 'cost' => round($cost,2)
    //             // 'margin' => round($monthlyIncome - $subTotal, 2),
    //         ];
    //     }

    //     //count cost
    //     foreach ($purchaseorder as $leasing) {
    //         $start = new \DateTime($date1);
    //         $end = new \DateTime($date2);
    //         $dateStartHire = new \DateTime($leasing->hire_purchase_starting_date);
    //         $dateEndHire = new \DateTime($leasing->date_after_duration_cost);

    //         $currentPaid = clone $start;
    //         $originalDay = (int) $dateEndHire->format('d');

    //         $currentPaid->setDate($start->format('Y'), $start->format('m'), min($originalDay, $start->format('t')));

    //         if ($currentPaid <= $start) {
    //             $currentPaid->modify('first day of next month');
    //             $currentPaid->setDate($currentPaid->format('Y'), $currentPaid->format('m'), min($originalDay, $currentPaid->format('t')));
    //         }

    //         if($dateStartHire > $start){
    //             $currentPaid = $dateStartHire;
    //         }

    //         $countDatePaid = 0;
    //         $cek_total_cost = 0;
    //         // $date_modif_cost = []; //debuging
    //         if ($dateEndHire >= $start and $leasing->purchase_method !== "Cash" and $leasing->status_next_step !== "Sold") {
    //             while ($currentPaid <= $end && $currentPaid <= $dateEndHire and $currentPaid < $dateEndHire) {
    //                 // $date_modif_cost[] = $currentPaid->format('Y-m-d'); //debuging
    //                 $countDatePaid++;
    //                 $currentPaid->modify('first day of next month');
    //                 $currentPaid->setDate($currentPaid->format('Y'), $currentPaid->format('m'), min($originalDay, $currentPaid->format('t')));
    //             }
    //             $cek_total_cost = $countDatePaid > 0 ? ($leasing->regular_monthly_payment + $leasing->vehicle_tracking) * $leasing->hp_term + ($leasing->total_base_interest ?? 0) : 0;
    //         }

    //         $projected_cost += $cek_total_cost;

    //         // $totalMonth_cost += $countDatePaid; //debuging

    //         //Calculate total cost
    //         $cost = $subTotal = 0;
    //         if($countDatePaid > 0 and $leasing->status_next_step != "Sold" and $leasing->purchase_method !== "Cash" ) {
    //             $subTotal = $leasing->regular_monthly_payment + $leasing->vehicle_tracking;
    //             $cost = ($subTotal * $countDatePaid) + ($leasing->total_base_interest ?? 0);
    //         }
    //         $total_cost += $cost;

    //         // Residual value calculation
    //         $data_residual = 0;
    //         if ($leasing->status_next_step == 'Sold') {
    //             $data_residual = $leasing->residual_value;
    //         }

    //         $total_residual_value += $data_residual;

    //         if($leasing->status_next_step === "Hired"){
    //             $countVehicleCost++;
    //             // $carsCost[] = $leasing->vehicle_registration; //debuging
    //         }

    //         $leasingData [] = [
    //             "vehicle number" => $leasing->vehicle_registration,
    //             "hire_purchase_start_date" => $leasing->hire_purchase_starting_date,
    //             "hire_purchase_end_date" => $leasing->date_after_duration_cost,
    //             "purchase_method" => $leasing->purchase_method,
    //             "status_vehicle" => $leasing->status_next_step,
    //             "sold_date" => $leasing->vehicle_sold_date,
    //             "count_month" => $countDatePaid,
    //             "regular_monthly_payment" => $subTotal,
    //             "monthly_payment" => $leasing->monthly_payment,
    //             "hp_payment" => $cost,
    //             "base_interest" => $leasing->total_base_interest,
    //             // "residual_value" => round($data_residual, 2),
    //             "final_payment" => $leasing->final_payment,
    //             "otr" => $leasing->price_otr,
    //             "hp_finance_provider" => $leasing->hp_finance_provider,
    //             "hp_interest_per_annum" => $leasing->hp_interest_per_annum,
    //             "hp_deposit_amount" => $leasing->hp_deposit_amount,
    //             "hp_term" => $leasing->hp_term,
    //             "residual_value" => $leasing->residual_value,
    //             "total_rental_income" => $leasing->total_rental_income,
    //             "total_other_income" => $leasing->total_other_income_rental
                

    //             // "forrecasting_cost" => round($cek_total_cost, 2),
    //             // "available_cost" => $leasing->available_cars_cost,
    //             // "date" => $date_modif_cost //debuging
    //         ];
    //     }

    //     $total_income = $rental + $total_residual_value;
    //     $margin = $rental - $total_cost;
    //     $profitMargin = $rental > 0 ? round(($margin / $rental) * 100, 2) : 0;
    //     $total_vehicle = $countVehicleIncome + $leasing->available_cars_count;
    //     $projected_margin = $sum_all_projected_income - ($projected_cost_in_rental + $leasing->available_cars_cost);
    //     $avg_projected_margin = $projected_margin / $count_contracts;

    //     // $avg_projected_margin = $projected_income / $count_contracts;
    //     // $avg_projected_margin = ($projected_income - ($projected_cost + $leasing->available_cars_cost)) / $count_contracts;
    //     // $avg_projected_margin = ($projected_income - ($projected_cost_in_rental + $leasing->available_cars_cost)) / $count_contracts; //cost in rental

    //     // Final structured data
    //     $modifiedData = [
    //         'actual_income' => round($rental, 2),
    //         'actual_cost' => round($total_cost, 2),
    //         'total_residual' => round($total_residual_value, 2),
    //         'total_income' => round($total_income, 2),
    //         'margin' => round($margin, 2),
    //         'margin_percentage' => round($profitMargin,1),
    //         'total_contract' => $count_contracts,
    //         'total_vehicle' => $total_vehicle,
    //         // 'count_vehicle_cost' => $countVehicleCost,
    //         // 'total_vehicle_income' => $countVehicleIncome,

    //         'projected_income' => round($sum_all_projected_income, 2), //all income in range time with same id
    //         'forecasting_income' => round($projected_income, 2),
    //         'forecasting_cost' => round($projected_cost + $leasing->available_cars_cost, 2),
    //         'percentage_forecasting' => round((($projected_margin / $sum_all_projected_income) * 100),1),
    //         'avg_forecasting_income' => round($count_contracts > 0 ? $avg_projected_margin : 0, 1),

    //         // 'data'=> $count_data,
    //         // 'cars_income' => $carsIncome,
    //         // 'cars_count' => $carsCost,
    //         // 'totalMonth_rental' => $totalMonth_rental,
    //         // 'totalMonth_cost' => $totalMonth_cost,
    //         // 'vehicle_in_cost' => $countVehicleCost,
    //         // 'percentage_forecasting' => round(($avg_projected_margin / $projected_income) * 100,5),
    //     ];

    //     if (count($salesOrders) > 0) {
    //         return response([
    //             'message' => 'Retrieve All Success',
    //             'data_rental' => $rentalData,
    //             'data_leasing' => $leasingData,
    //             'data' => $modifiedData
    //         ], 200);
    //     }

    //     return response([
    //         'message' => 'Empty',
    //         'data' => null
    //     ], 400);
    // }

    
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
                DB::raw("DATE_ADD(so.contract_start_date, INTERVAL so.term_months  MONTH) AS date_after_duration_income"),
                DB::raw("DATE_ADD(po.hire_purchase_starting_date, INTERVAL COALESCE(po.hp_term, 0) MONTH) AS date_after_duration_cost"),
            )
            ->whereRaw('DATE_ADD(so.contract_start_date, INTERVAL (so.term_months - 1) MONTH) >= ?', [$date1])
            ->whereRaw('so.contract_start_date <= ?', [$date2])
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




        $modifiedData = [];
        $rentalData = [];
        $leasingData = [];

        $rental = 0;
        $total_cost = 0;
        $projected_cost = 0;
        $count_contracts = 0;
        $projected_income = 0;
        $countVehicleCost = 0;
        $countVehicleIncome = 0;
        $total_residual_value = 0;
        $projected_cost_in_rental = 0;
        $sum_all_projected_income = 0;

        //debuging
        // $count_data = 0;
        // $carsIncome = [];
        // $carsCost = [];
        // $totalMonth_rental = 0;
        // $totalMonth_cost = 0;
        // $checking_total_income = 0;
        // $checking_total_rental = 0;

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
                $count_contracts++;
                $monthlyIncome = $item->monthly_rental * $countMonth;
                $cekTotal_income = $countMonth ? ($item->monthly_rental * $item->term_months) + ($item->initial_rental + $item->documentation_fees + $item->other_income) : 0;
            }

            // $totalMonth_rental += $countMonth; //debuging
            $rental += $monthlyIncome;

            //projected margin income
            $projected_income += $cekTotal_income;

            //projected cost
            $dateEndHire = new \DateTime($item->date_after_duration_cost);
            $projected_total_cost = 0;
            if ($dateEndHire >= $start and $item->purchase_method !== "Cash" and $item->status_next_step !== "Sold") {
                $projected_total_cost = ($item->regular_monthly_payment + $item->vehicle_tracking) * $item->hp_term + ($item->total_base_interest ?? 0);
                // $count_data++; //debuging
            }

            $projected_cost_in_rental += $projected_total_cost;

            //count cars
            if ($item->next_step_status_sales === 'Hired' and  $item->status_next_step ==='Hired') {
                $countVehicleIncome++;
                // $carsIncome[] =[$item->vehicle_registration , $item->agreement_number]; //debuging
            }

            if($item->next_step_status_sales === 'Hired'){
                $sum_all_projected_income += $item->New_Total_income;
            }

            // Store data
            $rentalData[] = [
                // 'id' => $item->newid,
                'agreement_number' => $item->agreement_number,
                'contract_start_date' => $item->contract_start_date,
                'contract_end_date' => $item->date_after_duration_income,
                'status_contract' => $item->next_step_status_sales,
                'count_month_rental' => $countMonth,
                'vehicle_registration' => $item->vehicle_registration,
                'id_purchase' => $item->newid,
                'monthly_rental' => round($item->monthly_rental, 2),
                'rental_income' => round($monthlyIncome,2),
                // 'total_income' => round($item->total_income,2),
                // 'total_rental' => round($item->rental_income,2),
                // 'amount_oi' => $amount_oi,
                // 'income' => $income,
                // 'purchased_method' => $item->purchase_method ,


                // 'income_forcasting_' => round($total_income_,2),
                // 'income_forcasting' => round($cekTotal_income,2),
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
            if ($dateEndHire >= $start and $leasing->purchase_method !== "Cash" and $leasing->status_next_step !== "Sold") {
                while ($currentPaid <= $end && $currentPaid <= $dateEndHire and $currentPaid < $dateEndHire) {
                    // $date_modif_cost[] = $currentPaid->format('Y-m-d'); //debuging
                    $countDatePaid++;
                    $currentPaid->modify('first day of next month');
                    $currentPaid->setDate($currentPaid->format('Y'), $currentPaid->format('m'), min($originalDay, $currentPaid->format('t')));
                }
                $cek_total_cost = $countDatePaid > 0 ? ($leasing->regular_monthly_payment + $leasing->vehicle_tracking) * $leasing->hp_term + ($leasing->total_base_interest ?? 0) : 0;
            }

            $projected_cost += $cek_total_cost;

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

                // "forrecasting_cost" => round($cek_total_cost, 2),
                // "available_cost" => $leasing->avaliable_cars_cost,
                // "date" => $date_modif_cost //debuging
            ];
        }

        $total_income = $rental + $total_residual_value;
        $margin = $rental - $total_cost;
        $profitMargin = $rental > 0 ? round(($margin / $rental) * 100, 2) : 0;
        $total_vehicle = $countVehicleIncome + $leasing->available_cars_count;
        $projected_margin = $sum_all_projected_income - ($projected_cost_in_rental + $leasing->avaliable_cars_cost);
        $avg_projected_margin = $projected_margin / $count_contracts;

        // $avg_projected_margin = $projected_income / $count_contracts;
        // $avg_projected_margin = ($projected_income - ($projected_cost + $leasing->avaliable_cars_cost)) / $count_contracts;
        // $avg_projected_margin = ($projected_income - ($projected_cost_in_rental + $leasing->avaliable_cars_cost)) / $count_contracts; //cost in rental

        // Final structured data
        $modifiedData = [
            'actual_income' => round($rental, 2),
            'actual_cost' => round($total_cost, 2),
            'total_residual' => round($total_residual_value, 2),
            'total_income' => round($total_income, 2),
            'margin' => round($margin, 2),
            'margin_percentage' => round($profitMargin,1),
            'total_contract' => $count_contracts,
            'total_vehicle' => $total_vehicle,
            // 'count_vehicle_cost' => $countVehicleCost,
            // 'total_vehicle_income' => $countVehicleIncome,

            'projected_income' => round($sum_all_projected_income, 2), //all income in range time with same id
            'forecasting_income' => round($projected_income, 2),
            'forecasting_cost' => round($projected_cost + $leasing->avaliable_cars_cost, 2),
            'percentage_forecasting' => round((($projected_margin / $sum_all_projected_income) * 100),1),
            'avg_forecasting_income' => round($count_contracts > 0 ? $avg_projected_margin : 0, 1),

            // 'data'=> $count_data,
            // 'cars_income' => $carsIncome,
            // 'cars_count' => $carsCost,
            // 'totalMonth_rental' => $totalMonth_rental,
            // 'totalMonth_cost' => $totalMonth_cost,
            // 'vehicle_in_cost' => $countVehicleCost,
            // 'percentage_forecasting' => round(($avg_projected_margin / $projected_income) * 100,5),
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

    public function laporanHpTerm(){

        $purchaseorder = DB::table('purchase_orders as po')
            ->leftJoin('vehicle_solds as vs', 'po.id', '=', 'vs.id_purchase_order')
            ->select([
                'po.purchase_method as purchase_method',
                'po.vehicle_registration as vehicle_registration',
                'po.hp_finance_provider as hp_finance_provider',
                'po.hire_purchase_starting_date as hire_purchase_starting_date',
                'po.hp_interest_type as hp_interest_type',
                'po.hp_interest_per_annum as hp_interest_per_annum',
                'po.hp_deposit_amount as hp_deposit_amount',
                'po.hp_term as hp_term',
                DB::raw('CASE
                        WHEN po.purchase_method != \'cash\'
                        THEN DATE_ADD(po.hire_purchase_starting_date, INTERVAL po.hp_term MONTH)
                        ELSE NULL
                    END as end_date'),
                'vs.vehicle_sold_date as defleet_date',
            ])
            ->get();

        // Map column keys to the human-readable CSV headers.
        $columns = [
            'purchase_method'             => 'Purchase Method',
            'vehicle_registration'        => 'Vehicle Registration No. (Include Spaces) (please type \'tba\' if it is not ready)',
            'hp_finance_provider'         => 'HP Finance Provider (Only for Hire Purchase)',
            'hire_purchase_starting_date' => 'Hire Purchase Starting Date / Vehicle Purchase Date / Rent Starting Date',
            'hp_interest_type'            => 'HP Interest Type',
            'hp_interest_per_annum'       => 'HP Interest per Annum (%) - (Only for Hire Purchase)',
            'hp_deposit_amount'           => 'HP Deposit Amount (GBP) - (Only for Hire Purchase)',
            'hp_term'                     => 'HP Term (Months) - (Only for Hire Purchase)',
            'end_date'                    => 'End Date',
            'defleet_date'                => 'Defleet Date',
        ];

        $fileName = 'laporan_hp_term_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($purchaseorder, $columns) {
            $handle = fopen('php://output', 'w');

            // Header row.
            fputcsv($handle, array_values($columns));

            // Data rows.
            foreach ($purchaseorder as $row) {
                $line = [];
                foreach (array_keys($columns) as $key) {
                    $line[] = $row->$key;
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

}
