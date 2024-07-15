<?php

use App\Http\Controllers\Api\MileageController;
use App\Models\BaseInterest;
use App\Models\BaseInterestDetail;
use App\Models\OtherCost;
use App\Models\OtherIncome;
use App\Models\RehiringOrder;
use App\Models\VehicleSold;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', 'App\Http\Controllers\Api\AuthController@register');
Route::post('login', 'App\Http\Controllers\Api\AuthController@login');

// PurchaseOrderController
Route::get('showvehiclenumberinsales', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumberinSales');
Route::get('purchaseorder/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@show');
Route::post('purchaseorder', 'App\Http\Controllers\Api\PurchaseOrderController@store');
Route::put('purchaseorder/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@update');
Route::delete('purchaseorder/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@destroy');
Route::get('purchaseorder', 'App\Http\Controllers\Api\PurchaseOrderController@index');
Route::get('purchaseorderall', 'App\Http\Controllers\Api\PurchaseOrderController@indexAll');
Route::get('showvehiclenumberexceptsold', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumberExceptSold');
Route::get('showsalesnumberinvehiclesold', 'App\Http\Controllers\Api\PurchaseOrderController@showSalesNumberInVehicleSold');
Route::get('availablestock', 'App\Http\Controllers\Api\PurchaseOrderController@availableStock');
Route::get('potentialstock', 'App\Http\Controllers\Api\PurchaseOrderController@potentialStock');
Route::get('bookedstock', 'App\Http\Controllers\Api\PurchaseOrderController@bookedStock');
Route::put('changestockstatus/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@changeStockStatus');
Route::get('showcontractbyid/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@showContractById');
Route::get('showvehiclebyid/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleById');
Route::get('showcostandfunding/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@showCostandFunding');
Route::get('showdashboard/{date1},{date2}', 'App\Http\Controllers\Api\PurchaseOrderController@showDashboard');
Route::get('countvehiclehired/{date1},{date2}', 'App\Http\Controllers\Api\PurchaseOrderController@countVehicleHired');
Route::get('countvehiclesold/{date1},{date2}', 'App\Http\Controllers\Api\PurchaseOrderController@countVehicleSold');
Route::get('laporan/{date1},{date2}', 'App\Http\Controllers\Api\PurchaseOrderController@laporan');

//dashboard
Route::get('sumtotalincome', 'App\Http\Controllers\Api\PurchaseOrderController@sumTotalIncome');
Route::get('sumtotalcost', 'App\Http\Controllers\Api\PurchaseOrderController@sumTotalCost');
Route::get('sumrentalincome', 'App\Http\Controllers\Api\PurchaseOrderController@sumRentalIncome');
Route::get('sumotherincome', 'App\Http\Controllers\Api\PurchaseOrderController@sumOtherIncome');
Route::get('sumothercost', 'App\Http\Controllers\Api\PurchaseOrderController@sumOtherCost');
Route::get('sumsoldprice', 'App\Http\Controllers\Api\PurchaseOrderController@sumSoldPrice');
Route::get('sumresidualvalue', 'App\Http\Controllers\Api\PurchaseOrderController@sumResidualValue');

Route::get('listvehicleinvehiclecard/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listVehicleInVehicleCard');
Route::get('listtotalincard/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listTotalInCard');
Route::get('listcostincard/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listCostInCard');
Route::get('listtotalincome/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listTotalIncome');
Route::get('listtotalcost/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listTotalCost');
Route::get('listrentalincome/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listRentalIncome');
Route::get('listotherincome/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listOtherIncome');
Route::get('listothercost/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listOtherCost');
Route::get('listsoldprice/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listSoldPrice');
Route::get('listresidualvalue/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listResidualValue');
Route::get('compilationdb', 'App\Http\Controllers\Api\PurchaseOrderController@compilationDB');
Route::get('showvehicle', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicle');
Route::get('listvehiclebyid/{id}', 'App\Http\Controllers\Api\PurchaseOrderController@listVehicleById');
Route::get('showvehiclenumber', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumber');
Route::get('showvehiclenumberinothercost', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumberInOtherCost');
Route::get('showvehiclenumberinotherincome', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumberInOtherIncome');

//SalesOrderController
Route::get('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@show');
Route::post('salesorder', 'App\Http\Controllers\Api\SalesOrderController@store');
Route::put('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@update');
Route::delete('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@destroy');
Route::get('salesorder', 'App\Http\Controllers\Api\SalesOrderController@index');
Route::get('showagreementnumber', 'App\Http\Controllers\Api\SalesOrderController@showAgreementNumber');
Route::get('showactivesales', 'App\Http\Controllers\Api\SalesOrderController@showActiveSales');
Route::get('showagreementnumberinrehiring', 'App\Http\Controllers\Api\SalesOrderController@showAgreementNumberInRehiring');
Route::get('showagreementnumberinvehiclesold', 'App\Http\Controllers\Api\SalesOrderController@showAgreementNumberInVehicleSold');

// RehiringOrder
Route::get('rehiringorder/{id}', 'App\Http\Controllers\Api\RehiringController@show');
Route::post('rehiringorder', 'App\Http\Controllers\Api\RehiringController@store');
Route::put('rehiringorder/{id}', 'App\Http\Controllers\Api\RehiringController@update');
Route::delete('rehiringorder/{id}', 'App\Http\Controllers\Api\RehiringController@destroy');
Route::get('rehiringorder', 'App\Http\Controllers\Api\RehiringController@index');
Route::get('showvehiclesold', 'App\Http\Controllers\Api\RehiringController@showVehicleSold');
Route::put('updatevehiclesold/{id}', 'App\Http\Controllers\Api\RehiringController@updateVehicleSold');
Route::get('showvehiclerehiringorder', 'App\Http\Controllers\Api\RehiringController@showVehicleRehiringOrder');

// VehicleSold
Route::get('vehiclesold/{id}', 'App\Http\Controllers\Api\VehicleSoldController@show');
Route::post('vehiclesold', 'App\Http\Controllers\Api\VehicleSoldController@store');
Route::put('vehiclesold/{id}', 'App\Http\Controllers\Api\VehicleSoldController@update');
Route::delete('vehiclesold/{id}', 'App\Http\Controllers\Api\VehicleSoldController@destroy');
Route::get('vehiclesold', 'App\Http\Controllers\Api\VehicleSoldController@index');

// OtherCost
Route::get('othercost/{id}', 'App\Http\Controllers\Api\OtherCostController@show');
Route::post('othercost', 'App\Http\Controllers\Api\OtherCostController@store');
Route::put('othercost/{id}', 'App\Http\Controllers\Api\OtherCostController@update');
Route::delete('othercost/{id}', 'App\Http\Controllers\Api\OtherCostController@destroy');
Route::get('othercost', 'App\Http\Controllers\Api\OtherCostController@index');

// OtherIncome
Route::get('otherincome/{id}', 'App\Http\Controllers\Api\OtherIncomeController@show');
Route::post('otherincome', 'App\Http\Controllers\Api\OtherIncomeController@store');
Route::put('otherincome/{id}', 'App\Http\Controllers\Api\OtherIncomeController@update');
Route::delete('otherincome/{id}', 'App\Http\Controllers\Api\OtherIncomeController@destroy');
Route::get('otherincome', 'App\Http\Controllers\Api\OtherIncomeController@index');

// MileageController
Route::get('mileage/{id}', 'App\Http\Controllers\Api\MileageController@show');
Route::post('mileage', 'App\Http\Controllers\Api\MileageController@store');
Route::put('mileage/{id}', 'App\Http\Controllers\Api\MileageController@update');
Route::delete('mileage/{id}', 'App\Http\Controllers\Api\MileageController@destroy');
Route::get('mileage', 'App\Http\Controllers\Api\MileageController@index');
Route::get('listmileagebyid/{id}', 'App\Http\Controllers\Api\MileageController@listMileageById');

// BaseInterest
Route::get('baseinterest/{id}', 'App\Http\Controllers\Api\BaseInterestController@show');
Route::post('baseinterest', 'App\Http\Controllers\Api\BaseInterestController@store');
Route::put('baseinterest/{id}', 'App\Http\Controllers\Api\BaseInterestController@update');
Route::delete('baseinterest/{id}', 'App\Http\Controllers\Api\BaseInterestController@destroy');
Route::get('baseinterest', 'App\Http\Controllers\Api\BaseInterestController@index');
Route::put('updatebaseinterest/{id}', 'App\Http\Controllers\Api\BaseInterestController@updateStatus');
Route::get('findbaseinterest', 'App\Http\Controllers\Api\BaseInterestController@findBaseInterest');
Route::get('showbaseinterestbetweendate/{date1},{date2}', 'App\Http\Controllers\Api\BaseInterestController@showBaseInterestBetweenDate');
Route::get('showbaseinterest/{date},{date2}', 'App\Http\Controllers\Api\BaseInterestController@showBaseInterest');

// BaseInterestDetail
Route::get('sumtotalbaseinterest/{id}', 'App\Http\Controllers\Api\BaseInterestDetailController@sumTotalBaseInterest');
Route::post('baseinterestdetail', 'App\Http\Controllers\Api\BaseInterestDetailController@store');

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('/logout', 'App\Http\Controllers\Api\AuthController@logout');
});


