<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', 'App\Http\Controllers\Api\AuthController@register');
Route::post('login', 'App\Http\Controllers\Api\AuthController@login');

// PurchaseOrderController
Route::get('showvehiclenumberinsales', 'App\Http\Controllers\Api\PurchaseOrderController@showVehicleNumberinSales');


//SalesOrderController
Route::get('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@show');
Route::post('salesorder', 'App\Http\Controllers\Api\SalesOrderController@store');
Route::put('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@update');
Route::delete('salesorder/{id}', 'App\Http\Controllers\Api\SalesOrderController@destroy');
Route::get('salesorder', 'App\Http\Controllers\Api\SalesOrderController@index');
Route::get('showagreementnumber', 'App\Http\Controllers\Api\SalesOrderController@showAgreementNumber');
Route::get('showactivesales', 'App\Http\Controllers\Api\SalesOrderController@showActiveSales');

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('/logout', 'App\Http\Controllers\Api\AuthController@logout');
});


