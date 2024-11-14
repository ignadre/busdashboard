<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['cors'])->group(function () {

Route::get('/', function () {
    return view('welcome');
});
Route::get('/bus_simulator', function () {
    return view('bus_simulator');
});
Route::get('/bus_stop_info', function () {
    return view('busStop_info');
});
//BEGIN 26th JUNE 2024
Route::get('/bus_terminal_info', function () {
    return view('busTerminal_info');
});
//END 26th JUNE 2024

Route::get('/input_bus_stop_859A', function () {
    return view('syd_test_bus_stop');
});

Route::get('/privacy_policy_android', function () {
    return view('privacy_policy_android');
});

Route::get('/privacy_policy_ios', function () {
    return view('privacy_policy_ios');
});

//BEGIN 20th October 2024
//display the dashboard by passing in bus stop as a parameter in URL
Route::get('/getBusStopInfo/{bus_stop_id?}', 'getBusInfoController@getBusStopInfo');
Route::get('/getBusTerminalInfo/{bus_stop_id?}', 'getBusInfoController@getBusTerminalInfo');
Route::get('/bus_stop_info_pi/{bus_stop_id?}', 'getBusInfoController@getBusStopInfo_pi');
//END 20th October 2024

Route::post('/getBusRoute', 'getBusInfoController@getBusRoute');
Route::post('/getBusRouteInfo', 'getBusInfoController@getBusRouteInfo');
Route::post('/getBusService', 'getBusInfoController@getBusService');
Route::post('/getBusStop', 'getBusInfoController@getBusStop');
Route::post('/getBusstopRoute', 'getBusInfoController@getBusstopRoute');
Route::post('/getLocationData', 'getBusInfoController@getLocationData');
Route::post('/getNearbyBusStop', 'getBusInfoController@getNearbyBusStop');
Route::post('/getBusstopList', 'getBusInfoController@getBusstopList');
Route::post('/getETA', 'getBusInfoController@getETA');
Route::post('/updateLocation', 'getBusInfoController@updateLocation');
Route::post('/getBusstopRoute_Test', 'getBusInfoController@getBusstopRoute_Test')->middleware('cors');
Route::post('/getAllBusStop', 'getBusInfoController@getAllBusStop');
Route::post('/getAllBus', 'getBusInfoController@getAllBus');
Route::post('/getBusLocations', 'userController@getBusLocations');
Route::post('/getBusInfo', 'getBusInfoController@getBusInfo');
Route::post('/getBusDailyTrips', 'getBusInfoController@getBusDailyTrips');
Route::post('/determineRoute', 'getBusInfoController@determineRoute');

Route::post('/getListBus', 'getBusInfoController@getListBus');
Route::post('/getmobile_nearbyStop', 'getBusInfoController@getmobile_nearbyStop');
Route::post('/getbus_stops_eta', 'getBusInfoController@getbus_stops_eta');
Route::post('/getbus_stop_bus_services', 'getBusInfoController@getbus_stop_bus_services');

Route::post('/getBusStopInfo', 'getBusInfoController@getBusStopInfo');
Route::post('/getBusStopInfo_refresh','getBusInfoController@getBusStopInfo_refresh');
Route::post('/getBusStopInfoRefresh', 'getBusInfoController@getBusStopInfoRefresh');
//BEGIN 26th JUNE 2024
Route::post('/getBusTerminalInfo', 'getBusInfoController@getBusTerminalInfo');
Route::post('/getBusTerminalInfoRefresh', 'getBusInfoController@getBusTerminalInfoRefresh');
//END 26th JUNE 2024

Route::get('/viewETATableGet', 'userController@viewETATableGet');

Route::get('/getHistoryETA', 'DatabaseController@getHistoryETA');

Route::get('/checkconnection', 'userController@checkconnection');


Route::post('/Ian_updateLocation','userController@Ian_updateLocation');
Route::post('/calculateETATest','userController@calculateETATest');
Route::post('/calculateETA','userController@calculateETA');
//BEGIN 10 Aug 2024
//To crawl fleet location data from PAJ
Route::post('/crawlFleetData','userController@crawlFleetData');
//END 10 Aug 2024
Route::post('/viewETATable','userController@viewETATable');
Route::post('/convertBustoptoNearestPolyLine','userController@convertBustoptoNearestPolyLine');
Route::post('/getData','userController@getData');
Route::post('/getKM','userController@getKM');
Route::post('/testgetKM','userController@testgetKM');
Route::post('/testCal','userController@testCal');
Route::post('/getBusRoute','userController@getBusRoute');
Route::post('/checkClosePointExist','userController@checkClosePointExist');
Route::post('/calculateETAWin','userController@calculateETAWin');
Route::post('/calculateHistoricDataAverage','userController@calculateHistoricDataAverage');


Route::post('/calculateHistoricData','userController@calculateHistoricData');
Route::post('/ianTest','userController@ianTest');
Route::post('/getETA_schedule','userController@getETA_schedule');
Route::post('/bus_insertlocation','userController@bus_insertlocation');
Route::post('/pi_insertlocation','userController@pi_insertlocation');
Route::post('/getAllBeaconInfo','userController@getAllBeaconInfo');
Route::post('/syd_Cal','userController@syd_Cal');
Route::post('/getKM_syd','userController@getKM_syd');
Route::post('/removephantomETA', 'userController@removephantomETA');

Route::get('/upload_json', 'CRUDController@upload_json');

Route::post('/pushCurrentData','userController@pushCurrentData');
//Route::post('/getETA','userController@getETA');
Route::post('/getBusStopServices','userController@getBusStopServices');

Route::post('/uploadData','CRUDController@uploadData');

Route::post('/testing','CRUDController@testing_files');

});
