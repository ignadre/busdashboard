<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api')->group(function () {
    Route::get('/getTest', 'APIController@getTest');
});

Route::middleware('api')->group(function () {
    Route::get('/buses/info', 'APIController@getBusInfo');
});

Route::middleware('api')->group(function () {
    Route::get('/buses/location', 'APIController@getBusLocations');
});

Route::middleware('api')->group(function () {
    Route::get('/bus-stops/nearby', 'APIController@getNearbyBusStop');
});

// Route::middleware('api')->group(function () {
//     Route::get('/bus-stops/{bus_stop_id}/service', 'APIController@getBusService');
// });

Route::middleware('api')->group(function () {
    Route::get('/routes/{route_id}/bus-stops', 'APIController@getBusStop');
});

Route::post('/getBusStop', 'getBusInfoController@getBusStop');