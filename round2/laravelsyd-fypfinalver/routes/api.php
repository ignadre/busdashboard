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

//Test use
Route::middleware('api')->group(function () {
    Route::get('/getTest', 'APIController@getTest');
});

//1.From https://laravelsyd-fypfinalver.herokuapp.com/getBusInfo 
Route::middleware('api')->group(function () {
    Route::get('/buses/info', 'APIController@getBusInfo');
});

//2.From https://laravelsyd-fypfinalver.herokuapp.com/getBusLocations 
Route::middleware('api')->group(function () {
    Route::get('/buses/location', 'APIController@getBusLocations');
});

//3.From https://laravelsyd-fypfinalver.herokuapp.com/getBusService 
Route::middleware('api')->group(function () {
    Route::get('/bus-stops/{bus_stop_id}/service', 'APIController@getBusService');
});

//4.From https://laravelsyd-fypfinalver.herokuapp.com/getNearbyBusStop 
Route::middleware('api')->group(function () {
    Route::get('/bus-stops/nearby', 'APIController@getNearbyBusStop');
});

//5. From https://laravelsyd-fypfinalver.herokuapp.com/getbus_stops_eta
Route::middleware('api')->group(function () {
    Route::get('/bus-services/{bus_service_number}/routes/{route_id}/bus-stops', 'APIController@getbus_stops_eta');
});

//6. From https://laravelsyd-fypfinalver.herokuapp.com/testgetKM 
// Not completed
Route::middleware('api')->group(function () {
    
    Route::get('/testgetKM','userController@NewtestgetKM'); 
    // Route::get('/api/bus-services/{bus_serivce_number}/routes/{route_id}/distance','userController@NewtestgetKM');
});

//7. From https://laravelsyd-fypfinalver.herokuapp.com/determineRoute 

//Route::post('/determineRoute', 'getBusInfoController@determineRoute');
Route::middleware('api')->group(function () {
    //Not completed
    // Route::get('/determineRoute', 'getBusInfoController@determineRouteV2'); 
    Route::get('/bus-services/{bus_service_number}/routes','getBusInfoController@newDetermineRoute');
});

//8. From https://laravelsyd-fypfinalver.herokuapp.com/getBusStop 
Route::middleware('api')->group(function () {
    Route::get('/routes/{route_id}/bus-stops', 'APIController@getBusStop');
});

//9. New Search API
Route::middleware('api')->group(function () {
    Route::get('/search', 'APIController@search');
});


Route::middleware('api')->group(function () {
    Route::get('/bus-services/scheduled-timing', 'APIController@getScheduleTiming');
});



Route::middleware('api')->group(function () {
    Route::get('/bus-stops/{bus_stop_id}/service', 'APIController@getBusStopServices');
});

Route::middleware('api')->group(function () {
    Route::get('/bus-stops/{bus_stop_id}/service/eta', 'APIController@getBusStopServicesETA');
});
