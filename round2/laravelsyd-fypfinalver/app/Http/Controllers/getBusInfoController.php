<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Config;

class getBusInfoController extends Controller
{
	/* 11 July 2020
	   Remove this test function

	public function getBusInfo()
	{

	$getBus = DB::table('bus')->get();
	//$getBus = "HEllo My World";
	return view('welcometest');
	}
	*/

	public function getTime()
	{
		date_default_timezone_set('Asia/Singapore');
		return date('Y-m-d H:i:s', time());
	}

	public function getBusRoute(Request $request)
	{

		$route_id = $request->input('route_id');
		$getBusRoute_Query = DB::table('route')
												->where('route_id', $route_id)
												->get();
		$dataset_busRoute = new Collection;

		foreach($getBusRoute_Query as $singleset)
		{
		$getBusRoute_singleset = [
					'route_id' => $singleset->route_id,
					'polyline' => $singleset->polyline
					];

		$dataset_busRoute->push($getBusRoute_singleset);
		}

		if($dataset_busRoute!=NULL)
			print(json_encode($dataset_busRoute));
		else
			return response( "No bus route found")->setStatusCode(400);

	/* return response()->json([
		'dataset_busRoute'=>$dataset_busRoute
		])->setStatusCode(200); */
	}

	public function getBusRouteInfo_method($bus_id, $bus_no)
	{
		$array_busRouteInfo = array();
		$bus_route_info_route_id = DB::table('bus_route')
							->select('bus_route.route_id')
							->join('bus', 'bus_route.bus_id', '=', 'bus.bus_id')
							->join('route', 'bus_route.route_id', '=', 'route.route_id')
							->where('bus.bus_id',$bus_id)
							->where('bus_service_no',$bus_no)
							->get();


		foreach($bus_route_info_route_id as $r_id)
		{

			$bus_route_info = DB::table('route_bus_stop')
							->select('route.route_id', 'bus_stop.name')
							->join('route', 'route_bus_stop.route_id', '=', 'route.route_id')
							->join('bus_stop', 'route_bus_stop.bus_stop_id', '=', 'bus_stop.bus_stop_id')
							->where('route.route_id', $r_id->route_id)
							->orderBy('bus_stop.bus_stop_id', 'desc')
							->limit(1)
							->get();

			foreach($bus_route_info as $singleset)
			{
				 /* $getBusRouteInfo_singleset = [
							'route_id' => $singleset->route_id,
							'name' => $singleset->name
							];  */
				array_push($array_busRouteInfo, $singleset);
				//$dataset_busRouteInfo->push($getBusRouteInfo_singleset);
			}
		}

		return $array_busRouteInfo;
	}

	public function getBusRouteInfo(Request $request)
	{

		$array_busRouteInfo_result = array();
		//$dataset_busRouteInfo = new Collection;
		$bus_id = $request->input('bus_id');
		$bus_no = $request->input('bus_service_no');

		$array_busRouteInfo_result = self::getBusRouteInfo_method($bus_id, $bus_no);


		if($array_busRouteInfo_result!=NULL)
			print(json_encode($array_busRouteInfo_result));
		else
			return response( "No bus route found")->setStatusCode(400);


		//print(json_encode($dataset_busRouteInfo));
		/* return response()->json([
		'dataset_busRouteInfo'=>$dataset_busRouteInfo
		])->setStatusCode(200); */
		//return view('welcometest',compact('bus_route_info'));
	}



	public function getBusStop_method($route_id)
	{

		$array_busstop = array();

		$getBusStop_Query = DB::table('bus_stop')
							->select(	'bus_stop.bus_stop_id',
										'bus_stop.name',
										'bus_stop.latitude',
										'bus_stop.longitude'
									)
							->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
							->join('route', 'route.route_id', '=', 'route_bus_stop.route_id')
							->where('route.route_id', $route_id)
							->get();
		foreach($getBusStop_Query as $singleset)
		{
			/* $getBusStop_singleset = [
						'bus_stop_id' => $singleset->bus_stop_id,
						'name' => $singleset->name,
						'latitude' => $singleset->latitude,
						'longitude' => $singleset->longitude
						];

			$dataset_busStop->push($getBusStop_singleset); */

			array_push($array_busstop, $singleset);
		}

		return $array_busstop;
	}

	public function getBusStop(Request $request)
	{
		//$dataset_busStop = new Collection;
		$array_busstop_result = array();
		$route_id = $request->input('route_id');

		$array_busstop_result = self::getBusStop_method($route_id);

		if($array_busstop_result!=NULL)
			print(json_encode($array_busstop_result));
		else
			return response( "No bus stop found")->setStatusCode(400);

	/* return response()->json([
		'dataset_busStop'=>$dataset_busStop
		])->setStatusCode(200); */

	}

	public function getBusstopRoute_method($route)
	{
		$getBusstopRoute_Query = DB::table('bus_stop')
									->select('bus_stop.bus_stop_id', 'bus_stop.name', 'bus_stop.latitude', 'bus_stop.longitude')
									->addselect(DB::raw('0 AS Distance'))
									->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
									->where('route_bus_stop.route_id', $route)
									->orderBy('route_bus_stop.route_order')
									->get();

		return $getBusstopRoute_Query;
	}

	public function getBusstopRoute(Request $request)
	{
		$route = $request->input('route');


		$getBusstopRoute_result = self::getBusstopRoute_method($route);



		if($getBusstopRoute_result!=NULL)
			print(json_encode($getBusstopRoute_result));
		else
			return response( "No nearby bus stop found")->setStatusCode(400);

	}

	public function getBusstopRoute_Test(Request $request)
	{
		$route = $request->input('route_id');
		$array_busstopRoute = array();


		$getBusstopRoute_Query = DB::table('bus_stop')
									->select('bus_stop.bus_stop_id', 'bus_stop.name', 'bus_stop.latitude', 'bus_stop.longitude')
									->addselect(DB::raw('0 AS Distance'))
									->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
									->where('route_bus_stop.route_id', $route)
									->orderBy('route_bus_stop.route_order')
									->get();

		//print(json_encode($getBusstopRoute_Query));
		foreach($getBusstopRoute_Query as $singleset)
		{
			array_push($array_busstopRoute, $singleset);
		}

		if($getBusstopRoute_Query!=NULL)
			print(json_encode($array_busstopRoute));
			// print(json_encode($getBusstopRoute_Query));
		else
			return response( "No nearby bus stop found")->setStatusCode(400);

	}

	public function getLocationData()
	{
		$getLocationData_Query = DB::table('location_data')
									->where('time', '>', '2015-09-11')
									->where('time', '<', '2015-09-13')
									->get();






		if($getLocationData_Query!=NULL)
			print(json_encode($getLocationData_Query));
		else
			return response( "No location data found")->setStatusCode(400);
		/* return response()->json([
			'dataset_locationdata'=>$getLocationData_Query
			])->setStatusCode(200); */
	}

	public function getNearbyBusStop_method($lat, $lng)
	{
		$getNearbyBusStop_Query = DB::table('bus_stop')
										->select('*')
										->selectraw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',[$lat,$lng,$lat])
										->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
										->having('distance', '<', 1)
										->orderBy('distance')
										->get();

		return $getNearbyBusStop_Query;
	}

	public function getNearbyBusStop(Request $request)
	{
		$lat = $request->input('lat');
		$lng = $request->input('lng');

		$getNearbyBusStop_Query = self::getNearbyBusStop_method($lat, $lng);

		if($getNearbyBusStop_Query!=NULL)
			print(json_encode($getNearbyBusStop_Query));
		else
			return response( "No nearby bus stop found")->setStatusCode(400);

		/* return response()->json([
			'dataset_NearbyBusStop'=>$getNearbyBusStop_Query
			])->setStatusCode(200); */
	}

	public function getBusstopList(Request $request)
	{
		$route = $request->input('route');
		$bus_id = $request->input('bus_id');
		$time = self::getTime();
		//$time = date('Y/m/d H:i:s', time());
		//$time = '2014/12/29 10:19:48';
		$getBusstopList_Query = DB::table('location_datav2')
									->select('location_datav2.latitude','location_datav2.longitude')
									->where('location_datav2.route_id','=',$route)
									->where('location_datav2.bus_id','=',$bus_id)
									->whereraw('location_datav2.time > (? - INTERVAL 3600 SECOND)',[$time])
									->whereraw('location_datav2.time = ( SELECT MAX( v.time ) FROM location_datav2 v WHERE v.route_id =? AND v.bus_id =? )',[$route,$bus_id])
									->get();

		if(($getBusstopList_Query->count()) > 0)
		{
			foreach($getBusstopList_Query as $result)
			{
				$lat = $result->latitude;
				$lng = $result->longitude;
			}

			$getBusstopList_Query2 = DB::table('bus_stop')
										->select('bus_stop.bus_stop_id')
										->selectraw('(6371 * acos(cos(radians(?)) * cos(radians(bus_stop.latitude)) * cos(radians(bus_stop.longitude) - radians(?)) + sin(radians(?)) * sin(radians(bus_stop.latitude)))) AS distance',[$lat,$lng,$lat])
										->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
										->where('route_bus_stop.route_id', $route)
										->having('distance', '<', 0.5)
										->orderBy('distance')
										->limit(1)
										->get();


			if (($getBusstopList_Query2->count()) > 0)
			{
				foreach($getBusstopList_Query2 as $result2)
				{
					$bus_stop_id = $result2->bus_stop_id;
				}
			}
			else
			{
				$bus_stop_id = 0;
			}
		}
		else
		{
			$bus_stop_id = 0;
		}
		$route_order = 0;

		if ($bus_stop_id > 0)
		{
			$getBusStopID = DB::table('route_bus_stop')
											->select('route_order')
											->where('route_id', $route)
											->where('bus_stop_id', $bus_stop_id)
											->first();


			$route_order = $getBusStopID->route_order;
		}


		$getBusstopList_Query_Final = DB::table('bus_stop')
									->select('bus_stop.bus_stop_id', 'bus_stop.name', 'bus_stop.latitude', 'bus_stop.longitude')
									->addselect(DB::raw('0 AS Distance'))
									->join('route_bus_stop', 'bus_stop.bus_stop_id', '=', 'route_bus_stop.bus_stop_id')
									->where('route_bus_stop.route_id', $route)
									->where('route_bus_stop.route_order', '>', $route_order)
									->get();


		if($getBusstopList_Query_Final!=NULL)
			print(json_encode($getBusstopList_Query_Final));
		else
			return response( "No Bus stop list found")->setStatusCode(400);

		/* return response()->json([
			'dataset_BusstopList'=>$getBusstopList_Query_Final
			])->setStatusCode(200); */
	}




	public function getETA_method($bus_stop_id, $bus_id, $route_id,$status)
	{
		$array_ETA = array();
		$time = self::getTime();
		//$time = date('Y/m/d H:i:s', time());
		//$time = '2014-10-29 10:19:48';

		$getETA_Query = DB::table('bus_route')
						->select('bus_route.route_id', 'bus_route.bus_service_no', 'e.eta')
						->join('etav2 AS e', function ($join)
							{
								$join->on('bus_route.bus_id', '=', 'e.bus_id')
									->on('bus_route.route_id','=', 'e.route_id');
							})
						->where('e.bus_id', $bus_id)
						->where('e.route_id', $route_id)
						->where('bus_stop_id', $bus_stop_id)
						->where('e.eta', '>', $time)
						->whereraw('e.time = ( SELECT MAX( t.time ) FROM etav2 t WHERE t.bus_id = ? AND t.route_id = ?) ',[$bus_id,$route_id])
						->orderBy('e.time','desc')
						->get();

    // $firstRecord = $getETA_Query->first();
		// if (($firstRecord->eta==NULL) && $status) {
		// 	return response("No bus service found")->setStatusCode(400);
		// }
		// elseif (($firstRecord->eta==NULL) && !$status) {
		// 	return "No bus service found";
		// }

		//print($getETA_Query);
		$array_ETA = self::calculateEta($getETA_Query);
		$getETA_response = "".json_encode($array_ETA);

		if($status)
		{
			if($array_ETA!=NULL)
					return response($getETA_response)->setStatusCode(200);
			else
				return response("No bus service found")->setStatusCode(400);
		}
		else {
			if($array_ETA!=NULL)
					return $getETA_response;
			else
				return "No bus service found";
		}

	}

	public function getETA(Request $request)
	{
		$bus_stop_id = $request->input('bus_stop_id');
		$bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$status = true;

		return self::getETA_method($bus_stop_id, $bus_id, $route_id, $status);
			//return response( "No bus service found")->setStatusCode(400);


		/* print(response()->json([
			'dataset_ETA'=>$dataset_ETA
			])->setStatusCode(200)); */

	}

	public function getBusService_method($bus_stop_id)
	{
		$array_BusService = array();
		$time = self::getTime();
		//$time = date('Y/m/d H:i:s', time());
		//$currentTime = round(microtime(true));
		//$currentTime = '2015-12-28 15:41:00';

		$bus_service_Query = DB::table('etav2 AS e')
							->select('e.route_id','bus_route.bus_service_no')
							->selectraw('GROUP_CONCAT(DISTINCT eta) AS eta')
							->join('bus_route', function ($join)
							{
								$join->on('bus_route.bus_id', '=', 'e.bus_id')
									->on('bus_route.route_id','=', 'e.route_id');
							})
							->where('e.bus_stop_id',$bus_stop_id)
							->where('e.eta', '>', $time)
							->where('e.time', '>',function($query)
											{
												$query->selectraw('MAX( time ) - INTERVAL 30 SECOND
												FROM etav2 v
												WHERE v.bus_id = e.bus_id
												AND v.route_id = e.route_id');
											}
									)
							->groupBy('e.route_id', 'bus_route.bus_service_no')
							->orderBy('eta', 'desc')
							->get();

		$array_BusService = self::calculateEta($bus_service_Query);

		return $array_BusService;

	}

	public function getBusService(Request $request)
	{
		$bus_stop_id = $request->input('bus_stop_id');

		$array_BusService = self::getBusService_method($bus_stop_id);

		if($array_BusService!=NULL)
			print(json_encode($array_BusService));
		else
			return response("No bus service found")->setStatusCode(400);
			//response("400", "No bus service found");
			//print("No bus service found");
			//

		/* return response()->json([
			'dataset_BusService'=>$dataset_BusService
			])->setStatusCode(200); */
	}

	public function updateLocation(Request $request)
	{
		$bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$imei = $request->input('imei');
		$latitude = $request->input('latitude');
		$longitude = $request->input('longitude');
		$speed = $request->input('speed');

		$updateLocation_Query = DB::table('location_data')
								->insert(
								['bus_id' => $bus_id,
								'route_id' => $route_id,
								'imei' => $imei,
								'latitude' => $latitude,
								'longitude' => $longitude,
								'speed' => $speed,
								'time' => $currentTime
								]);
		if($updateLocation_Query)
			return response('Location data updated')->setStatusCode(200);
		else
			return response('Unable to update location data')->setStatusCode(400);
	}

	function calculateEta($calcETA_Result)
	{
		//$dataset_calcETA = new Collection;
		$arr = array();
		date_default_timezone_set('Asia/Singapore');
		$currentTime = round(microtime(true));
		//$currentTime = round(94727184073);
		//echo $currentTime;
		//dd(json_encode($getETA_Query));
		foreach($calcETA_Result as $result)
		{
			$result->eta = self::processEta($currentTime, $result->eta);

			array_push($arr,$result);
		}
		return $arr;
	}


	function processEta($t1, $etas)
	{
		$etaList = explode(",", $etas);

		for ($i = 0; $i < count($etaList); $i++) {
			$etaList[$i] = array(
			    "time" => $etaList[$i],
			    "relative_time" => self::getRelativeTime($t1, strtotime($etaList[$i]))
			);
		}

		return $etaList;
	}

	function getRelativeTime($t1, $t2) {
		$timediff = round(($t2-$t1)/60);

		return $timediff;
	}


	public function getBusStop_BusServices_method($bus_stop_id)
	{
		$busServices_array = array();
		$getRoute_id = DB::table('route_bus_stop')
							->select('route_bus_stop.route_id')
							->where('bus_stop_id',$bus_stop_id)
							->get();

		foreach ($getRoute_id as $singleset) {
			$getBusServiceList = DB::table('bus_route')
													->select('bus_route.bus_service_no','bus_route.route_id','route_bus_stop.route_order')
													->join('route_bus_stop','route_bus_stop.route_id', '=', 'bus_route.route_id')
													->where('bus_route.route_id', $singleset->route_id)
													->where('route_bus_stop.bus_stop_id',$bus_stop_id)
													->distinct()
													->get();
			array_push($busServices_array, $getBusServiceList);
		}

		return $busServices_array;

	}
	public function getAllBus(Request $request)
	{
		$status = 0;
		$bus_check=  $request->input('bus_check');
		$bus_id_last = $request->input('bus_id_last');
		$array_allBus = array();
		$getAllBus_check_Query = DB:: table('bus')
														->count();
		if ($getAllBus_check_Query > $bus_check)
		{
			if ($bus_id_last > 0)
			{
				$status = $bus_id_last + 1;
			}
			$array_allBus = self::getAllBus_method($status);
			if($array_allBus != NULL)
				print(json_encode($array_allBus));
			else
				return response( "No bus registered")->setStatusCode(400);

		}
		else
		{
			print(json_encode($array_allBus));
		}
	}

	public function getAllBusStop(Request $request)
	{
		$status = 0;
		$bus_stop_check =  $request->input('bus_stop_check');
		$bus_stop_id_last = $request->input('bus_stop_id_last');
		$getAllBusStop_check_Query = DB:: table('bus_stop')
														->count();
		$array_allBusStop = array();
		if ($getAllBusStop_check_Query > $bus_stop_check)
		{
			if($bus_stop_id_last > 0)
			{
				$status = $bus_stop_id_last + 1;
			}
			$array_allBusStop = self::getAllBusStop_method($status);
			if($array_allBusStop != NULL)
				print(json_encode($array_allBusStop));
			else
				return response( "No bus stop registered")->setStatusCode(400);
		}
		else {
			print(json_encode($array_allBusStop));
		}



	}

	public function getAllBus_method($status)
	{
		$array_allBus = array();

		if ($status > 0)
		{
			$getAllBus_Query = DB:: table('bus')
															->where('bus.bus_id' ,'>=', $status)
															->get();
		}
		else{
			$getAllBus_Query = DB:: table('bus')
															->get();
		}

		foreach ($getAllBus_Query as $singleset) {

			$dataset = [
				'bus_id' => $singleset->bus_id,
				'beacon_mac' => $singleset->beacon_mac
			];
			array_push($array_allBus, $dataset);
		}

		return $array_allBus;

	}

	public function getAllBusStop_method($status)
	{
		$array_allBusStop = array();


		if ($status > 0)
		{
			$getAllBusStop_Query = DB:: table('bus_stop')
															->where('bus_stop.bus_stop_id' ,'>=', $status)
															->get();
		}
		else{
			$getAllBusStop_Query = DB:: table('bus_stop')
															->get();
		}




		foreach($getAllBusStop_Query as $singleset)
		{

			$getBusService = self::getBusStop_BusServices_method($singleset->bus_stop_id);
			$busServices_array = array();
			foreach ($getBusService as $busService) {
				foreach ($busService as $bs) {
					$bus_service_no_and_route = $bs->bus_service_no.'_'.$bs->route_id.'-'.$bs->route_order;
					array_push($busServices_array, $bus_service_no_and_route);
				}

			}
			$dataset = [
				'bus_stop_id' => $singleset->bus_stop_id,
				'name' => $singleset->name,
				'latitude' => $singleset->latitude,
				'longitude' => $singleset->longitude,
				'bus_services' => $busServices_array
			];
			array_push($array_allBusStop, $dataset);
		}

		return $array_allBusStop;


	}


	public function getBusStopInfo_refresh(Request $request)
	{
		$bus_stop_id = $request->input('bus_stop_id');
		$refresh_array = $request->input('refresh_array');

		$refresh_return = array();
		foreach ($refresh_array as $value) {
			$array_refresh = self::getETA_method_BusStopInfo_refresh($bus_stop_id, $value[1]);
			$array_refresh_return = array();
			$stop_eta = "NA";
			$stop_eta2 = "NA";
				foreach ($array_refresh as $singleset) {
					$array_refresh_return = self::array_sort_by_column($singleset->eta);
					if (count($singleset->eta) > 0)
					{

						$stop_eta = $array_refresh_return[0]['relative_time'];
					}
					if(count($singleset->eta) > 1)
					{
							$stop_eta2 = $array_refresh_return[1]['relative_time'];
					}

				}


			$refresh_return_dataset = [
				"key" => $value[0],
				"stop_eta" => $stop_eta,
				"stop_eta2" => $stop_eta2
			];
			array_push($refresh_return,$refresh_return_dataset);
		}
		return $refresh_return;




	}

	public function getETA_method_BusStopInfo_refresh($bus_stop_id, $route_id)
	{
		$array_BusService = array();
		$time = self::getTime();
		//$time = date('Y/m/d H:i:s', time());
		//$currentTime = round(microtime(true));
		//$currentTime = '2015-12-28 15:41:00';

		$bus_service_Query = DB::table('etav2 AS e')
							->select('e.route_id','bus_route.bus_service_no')
							->selectraw('GROUP_CONCAT(DISTINCT eta) AS eta')
							->join('bus_route', function ($join)
							{
								$join->on('bus_route.bus_id', '=', 'e.bus_id')
									->on('bus_route.route_id','=', 'e.route_id');
							})
							->where('e.bus_stop_id',$bus_stop_id)
							->where('e.route_id', $route_id)
							->where('e.eta', '>', $time)
							->where('e.time', '>',function($query)
											{
												$query->selectraw('MAX( time ) - INTERVAL 30 SECOND
												FROM etav2 v
												WHERE v.bus_id = e.bus_id
												AND v.route_id = e.route_id');
											}
									)
							->groupBy('e.route_id', 'bus_route.bus_service_no')
							->orderBy('eta', 'desc')
							->get();

		$array_BusService = self::calculateEta($bus_service_Query);

		return $array_BusService;

	}

	public function getBusStopInfo_pi($bus_stop_id)
	{
		$data = self::getBusStopInfo_method($bus_stop_id);
		return view('bus_stop_info', ['data' => $data]);
	}

	public function getBusStopInfo_method($bus_stop_id)
	{
		$bus_service_list = self::getBusStop_BusServices_method($bus_stop_id);
		$bus_service_available = self::getBusService_method($bus_stop_id);
		$getBusStopInfo_array = array();
		$getBusStopName = DB::table('bus_stop')
											->select('name')
											->where('bus_stop_id', $bus_stop_id)
											->first();
		$stop_name = $getBusStopName->name;

		foreach ($bus_service_list as $singleset)
		{
			$getDestination_route_id = DB::table('bus_route')
												->select('bus_route.route_id')
												->join('route_bus_stop','route_bus_stop.route_id', '=', 'bus_route.route_id')
												->where('route_bus_stop.bus_stop_id', $bus_stop_id)
												->where('bus_route.bus_service_no', $singleset[0]->bus_service_no)
												->first();
			$getDestination_name = DB::table('bus_stop')
														->select('bus_stop.name')
														->join('route_bus_stop','route_bus_stop.bus_stop_id', '=', 'bus_stop.bus_stop_id')
														->where('route_bus_stop.route_id', $getDestination_route_id->route_id)
														->orderBy('route_bus_stop.route_order', 'desc')
														->first();
			$eta = NULL;
			//BEGIN 7th MAY 2024
			//to indicate whether the eta is live or not
			$eta_live_1 = 0;
			$eta_live_2 = 0;
			//END 7th MAY 2024

			foreach ($bus_service_available as $singleset2)
			{
				if($singleset[0]->bus_service_no == $singleset2->bus_service_no)
				{
					$eta = $singleset2->eta;
				}
			}
			if($eta != NULL)
			{
				//BEGIN 7 MAY 2024
				//If ETA is not null, then there is at least one live ETA
				$eta_live_1=1;
				//END 7 MAY 2024

				$eta = self::array_sort_by_column($eta);
				//$stop_eta = self::getstring_Time($eta[0]['time']);
				if (count($eta) > 1)
				{
					//BEGIN 7 MAY 2024
					//If the count of eta array is more than 1, then the second ETA is available too
					$eta_live_2 = 1;
					//END 7 MAY 2024

					if ($eta[1]['relative_time'] > 1)
					{
						$stop_eta2 = $eta[1]['relative_time']." mins";

					}
					else
					{
						$stop_eta2 = "Arriving";
					}
					$eta_date2 = $eta[1]['time'];

				}
				else
				{
					$stop_eta2 = "NA";
					$eta_date2 = "NA";
				}

				if($eta[0]['relative_time'] > 1)
				{
					$stop_eta = $eta[0]['relative_time']." mins";
				}
				else
				{
					$stop_eta = "Arriving";
				}
				$dataset_busList = [
					'bus_service_no' => $singleset[0]->bus_service_no,
					'stop_eta' => $stop_eta,
					'stop_eta2' => $stop_eta2,
					'Destination' => $getDestination_name->name,
					'eta_date' => $eta[0]['time'],
					'eta_date2' => $eta_date2,
					'route' => $getDestination_route_id->route_id,
					//BEGIN 7 MAY 2024
					//Added 2 attributes live and live2
					'live' => $eta_live_1,
					'live2' => $eta_live_2
					//END 7 MAY 2024
				];
			}
			else
			{
				$dataset_busList = [
					'bus_service_no' => $singleset[0]->bus_service_no,
					'stop_eta' => "NA",
					'stop_eta2' => "NA",
					'Destination' => $getDestination_name->name,
					'eta_date' => "NA",
					'eta_date2' => "NA",
					'route' => $getDestination_route_id->route_id,
					//BEGIN 7 MAY 2024
					//Added 2 attributes live and live2
					'live' => 0,
					'live2' => 0
					//END 7 MAY 2024
				];
			}
			array_push($getBusStopInfo_array, $dataset_busList);
		}

		//BEGIN 7 MAY 2024
		//To merge the schedule timetable with the live ETA
		$getBusStopInfo_array = self::merge_ETA($bus_stop_id, $getBusStopInfo_array);
		//END 7 MAY 2024
		$getBusStopInfo_array_sorted = self::sort_bus_service($getBusStopInfo_array);

		$data = array(
		'stop_name' => $stop_name,
		'bus_stop_id' => $bus_stop_id,
		"bus_data" => $getBusStopInfo_array_sorted
		);

		return $data;
	}

	public function getBusStopInfo(Request $request)
	{

		$bus_stop_id = $request->input('bus_stop_id');

		$data = self::getBusStopInfo_method($bus_stop_id);
		//BEGIN 23 March 2024
		//Test Dashboard
		//return view('bus_stop_info', ['data' => $data]);
		return view('bus_stop_new', ['data' => $data]);
		//END 23 March 2024
	}

	//7th MAY 2024
	//For refresh ETA data, only to return the <div>, not the entire page
	public function getBusStopInfoRefresh(Request $request) {

		$bus_stop_id = $request->input('bus_stop_id');

		$data = self::getBusStopInfo_method($bus_stop_id);
		return view('bus_stop_refresh', ['data' => $data]);
	}
	//END 7th MAY 2024

	public function getBusTerminalInfo_method($bus_stop_id) {

		$bus_terminal = $bus_stop_id - 1;
		$arrival_data = self::getBusStopInfo_method($bus_terminal);
		$departure_data = self::getBusStopInfo_method($bus_stop_id);

		for ($i=0; $i<sizeof($arrival_data['bus_data']); $i++)
		{
				$arrival_data['bus_data'][$i] += ['departure_date' => $departure_data['bus_data'][$i]['eta_date']];
				$arrival_data['bus_data'][$i] += ['departure_date2' => $departure_data['bus_data'][$i]['eta_date2']];
				$arrival_data['bus_data'][$i] += ['departure' => $departure_data['bus_data'][$i]['stop_eta']];
				$arrival_data['bus_data'][$i] += ['departure2' => $departure_data['bus_data'][$i]['stop_eta2']];
				$arrival_data['bus_data'][$i]['Destination'] = $departure_data['bus_data'][$i]['Destination'];
				$arrival_data['bus_data'][$i]['route'] = $departure_data['bus_data'][$i]['route'];
				$arrival_data['bus_stop_id'] = $departure_data['bus_stop_id'];
				if (strcmp($arrival_data['bus_data'][$i]['departure'], "Arriving") == 0) {
						$arrival_data['bus_data'][$i]['departure'] = "Departing";
				}
				if (strcmp($arrival_data['bus_data'][$i]['departure2'], "Arriving") == 0) {
						$arrival_data['bus_data'][$i]['departure2'] = "";
				}
				if (strcmp($arrival_data['bus_data'][$i]['stop_eta'], "NA") == 0) {
						$arrival_data['bus_data'][$i]['eta_date'] = "";
						$arrival_data['bus_data'][$i]['stop_eta'] = "";
				}
		}
		return $arrival_data;
	}

	//25th JUNE 2024
	//For Bus Terminal
	public function getBusTerminalInfo(Request $request)
	{
		$bus_stop_id = $request->input('bus_stop_id');
		$arrival_data = self::getBusTerminalInfo_method($bus_stop_id);
		return view('bus_terminal_new', ['data' => $arrival_data]);
	}

	public function getBusTerminalInfoRefresh(Request $request) {

		$bus_stop_id = $request->input('bus_stop_id');
		$arrival_data = self::getBusTerminalInfo_method($bus_stop_id);
		return view('bus_terminal_refresh', ['data' => $arrival_data]);
	}
	//END 26th JUNE 2024

	 function sort_bus_service($arr) {
		 usort($arr, function ($a, $b) {
				  return strcmp($a['bus_service_no'],$b['bus_service_no']);
		 });
		 return $arr;
	 }
	 function array_sort_by_column($arr) {

			usort($arr, function ($a, $b) {
			    return strtotime($a['time']) - strtotime($b['time']);
			});
			return $arr;
	}

	function getstring_Time($string) {
		$tring_search   = ' ';
		$pos = strpos($string, $tring_search);

		return substr($string, $pos+1);
	}

	//mobile APP

	public function getbus_stop_bus_services(Request $request)
	{
		$bus_stop_id = $request->input('bus_stop_id');

		return self::getBusStop_BusServices_method($bus_stop_id);
	}

	public function getbus_stops_eta(Request $request)
	{

		$bus_service_no = $request->input('bus_service');
		$route_id = $request->input('route_id');

		return self::bus_stops_eta_method($route_id,$bus_service_no);

	}

	public function bus_stops_eta_method($route_id,$bus_service_no)
	{

			$route_busstops = self::getBusstopRoute_method($route_id);
			$route_busstops_array = array();
			foreach ($route_busstops as $singleset2)
			{
				$BusService = self::getBusService_method($singleset2->bus_stop_id);
				$eta = NULL;

				foreach ($BusService as $singleset3)
				{
					if($singleset3->bus_service_no == $bus_service_no)
					{
						$eta = $singleset3->eta;
					}

				}

				if($eta != NULL)
				{
					$dataset_busList = [
						'stop_id' => $singleset2->bus_stop_id,
						'stop_name' => $singleset2->name,
						'stop_eta' => $eta
					];
				}
				else
				{
					$dataset_busList = [
						'stop_id' => $singleset2->bus_stop_id,
						'stop_name' => $singleset2->name,
						'stop_eta' => "NA"
					];
				}

			 array_push($route_busstops_array, $dataset_busList);

			}


			return $route_busstops_array;


	}

	public function getListBus(Request $request)
	{
		$listBus_array = array();
		$bus_service_no = $request->input('bus_service');

		$bus_route_info_route_id = DB::table('bus_route')
							->select('bus_route.bus_id')
							->where('bus_service_no',$bus_service_no)
							->first();

		$bus_id = $bus_route_info_route_id->bus_id;

		$getBusRouteInfo = self::getBusRouteInfo_method($bus_id, $bus_service_no);

		foreach ($getBusRouteInfo as $singleset)
		{
			$route_busstops_array = self::bus_stops_eta_method($singleset->route_id,$bus_service_no);

			$dataset_busList = [
				'routeInfo' => $singleset,
				'route_busstops' => $route_busstops_array
			];

			array_push($listBus_array, $dataset_busList);

		}


			return $listBus_array;



	}

	public function getmobile_nearbyStop(Request $request)
	{
		$lat = $request->input('lat');
		$lng = $request->input('lng');

		$nearbyBusStop = self::getNearbyBusStop_method($lat,$lng);
		$array_getmobile_nearbyStop = array();
		$array_getmobile_nearbyStop_return = array();

		foreach($nearbyBusStop as $singleset)
		{
			if(count($array_getmobile_nearbyStop) > 2)
			{
				break;
			}
			else {

				array_push($array_getmobile_nearbyStop, $singleset);

			}

		}


		foreach ($array_getmobile_nearbyStop as $singleset2) {
				$array_mobileBusService = self::getBusService_method($singleset2->bus_stop_id);
				// if($array_mobileBusService!=NULL)
				// {
				// 	$array_mobileBusService = json_encode($array_mobileBusService);
				// }

				$bus_stop_route_order_query = DB::table('route_bus_stop')
									->select('route_bus_stop.route_order','route_bus_stop.route_id')
									->where('route_bus_stop.bus_stop_id',$singleset2->bus_stop_id)
									->get();



				$dataset_getmobile_nearbyStop = [
					'stop_id' => $singleset2->bus_stop_id,
					'bus_stop_name' => $singleset2->name,
					'route_order' => $bus_stop_route_order_query,
					'busService' => $array_mobileBusService
				];
				array_push($array_getmobile_nearbyStop_return, $dataset_getmobile_nearbyStop);
		}

		return $array_getmobile_nearbyStop_return;

	}

	/* 11 July 2020
 	   Function to obtain the bus plate number, bus_id, beacon_mac, and bus_service_no, as the driver app will detect the beacon in the bus.
	*/
	public function getBusInfo()
	{

		$getBusInfo_Query = DB::table('bus as b')
				    ->select ('b.bus_id', 'plate_no', 'beacon_mac', 'bus_service_no')
						->join('bus_route as br', 'br.bus_id', 'b.bus_id')
						->groupby('b.bus_id', 'plate_no', 'beacon_mac', 'bus_service_no')
				    -> get();


		$data = array();

		foreach($getBusInfo_Query as $singleset)
		{
			array_push($data, $singleset);

		}

		return response(json_encode($data), 200);

	}

	/* 16 July 2020
		 Function to provide daily trip analysis
		 Return a a collection Key:{bus_id}
		 											 Values []: [{Plate_no, route_id, Departure_time, Duration}]
	*/
	public function getBusDailyTrips(Request $request)
	{
			//get Today's date - for testing purposes, use a specific date 2020 Jan 1.
			if ($request->input("today") == 1)
					$date = date('Y-m-d', time());
			else
					$date = "2020-01-01";

			$today = $date . " 00:00:00";
			$endDay = $date . " 23:59:59";

			$userController = new userController();

			/* Query to get the bus location_datav2, sort by bus_id, location time.

			select bus_id, route_id, latitude, longitude, time from location_datav2
			where time > '2020-01-01 00:00:00' and time < '2020-01-01 10:00:00'
			order by bus_id, time asc
			*/
			$getBusDailyTrips_Query = DB::table('location_datav2 as l')
																->select('l.bus_id', 'route_id', 'latitude', 'longitude', 'time', 'b.plate_no')
																->join('bus as b', 'l.bus_id', 'b.bus_id')
																->whereraw("time > '{$today}' and time < '{$endDay}'")
																->orderby('bus_id', 'time', 'asc')
																->get();

			$data = array();
			//Initialize an object for each trip made.
			$output = json_decode('{"plate_no": "0000", "route_id": "0", "departure_time":"0", "duration": 0}');
			$previousSet = null;
			$response = collect();

			// foreach($getBusDailyTrips_Query as $test)
			// {
			// 		array_push($data, $test);
			// }
			//
			// print_r($data);
			// return;

			foreach($getBusDailyTrips_Query as $singleset)
			{
					//this is the first record
					if (is_null($previousSet))
					{
							$previousSet = $singleset;

							//Assign the departure_time
							$output->plate_no = $singleset->plate_no;
							$output->route_id = $singleset->route_id;
							$output->bus_id = $singleset->bus_id;
							$output->departure_time = $singleset->time;
							$output->duration = -1;
					}
					else {
							//find the last last record of the journey.
							if (($previousSet->bus_id == $singleset->bus_id) && ($previousSet->route_id != $singleset->route_id))
							{

									//check whether the lat and lng is close to the terminal station? if yes, then calculate duration,.
									$previousLocation = array($previousSet->latitude, $previousSet->longitude);
									//calculate the journey duration $singleset->time - $output->departure_time
									//Route 1, there is no data record inserted into the DB at JB Sentral Terminal
									if ($previousSet->route_id == 1)
									{
											//map the lat,long to the closest point on the polyline route
											$pointOnPolyline = $userController->closepointonroute("7", "1", $previousLocation, 0.1);
											//get the destination bus stop polyline  (JB Terminal location - 1.463400,103.764932)
											$destinationPoint = $userController->closepointonroute("7", "1", array(1.463400,103.764932), 0.06);
											//calculate distance to the final destination
											$source = explode(',', $pointOnPolyline);
											$terminal = explode(',', $destinationPoint);
											$distance = $userController->caldistance($source, $terminal);
											// print_r("Point on Polyline: " . $pointOnPolyline . "\n");
											// print_r("JB Sentral point: " . $destinationPoint . "\n");
											// print_r("Distance calculated: " . $distance . "\n");

											//very close to terminal JB, then use the last record of route 1
											if ($distance < 0.7)
													$duration = strtotime($previousSet->time) - strtotime($output->departure_time);
										  //else use the departure time from JB Sentral
											else
													$duration = strtotime($singleset->time) - strtotime($output->departure_time);
									}
									//Route 2, Kulai Mara Arked will update the DB record when the bus arrives at Pekan Kulai.
									elseif ($previousSet->route_id == 2)
									{
											//map the lat,long to the closest point on the polyline route
											$pointOnPolyline = $userController->closepointonroute("7", "2", $previousLocation, 0.1);
											//get the destination bus stop polyline  (Kulai Terminal - 1.662585,103.598608)
											$destinationPoint = $userController->closepointonroute("7", "2", array(1.662585,103.598608), 0.06);

											//calculate distance to the final destination
											$source = explode(',', $pointOnPolyline);
											$terminal = explode(',', $destinationPoint);
											$distance = $userController->caldistance($source, $terminal);
											// print_r("Point on Polyline: " . $pointOnPolyline . "\n");
											// print_r("Kulai Terminal point: " . $destinationPoint . "\n");
											// print_r("Distance calculated: " . $distance . "\n");

										  if ($distance < 0.7)
													$duration = strtotime($previousSet->time) - strtotime($output->departure_time);
											elseif ($distance > 0.7)
													$duration = -1;
								  }
									$output->duration = $duration;

									//add to the output
									if (!is_null($output))
									{
											//copy trip info in output to tripInfo object to be inserted into $data
											$tripInfo = json_decode('{"plate_no": "0000", "route_id": "0", "bus_id":0, "departure_time":"0", "duration": -1}');
											$tripInfo->plate_no = $output->plate_no;
											$tripInfo->route_id = $output->route_id;
											$tripInfo->bus_id = $output->bus_id;
											$tripInfo->departure_time = $output->departure_time;
											$tripInfo->duration = $output->duration;

											//Trip identified, and insert into data[]
											array_push($data, $tripInfo);
									}
									$previousSet = $singleset;

									// Reset output with the next journey
									$output->plate_no = $singleset->plate_no;
									$output->route_id = $singleset->route_id;
									$output->departure_time = $singleset->time;
									$output->bus_id = $singleset->bus_id;
									$output->duration = 0;

							}
							elseif (($previousSet->bus_id == $singleset->bus_id) && ($previousSet->route_id == $previousSet->route_id))
							{
									//In JB Bus Terminal, it could be detected twice thinking that the bus already departed, but it was not.
									//check if location is the same and duration > 1 mins?
									if (($previousSet->latitude == $singleset->latitude) && ($previousSet->longitude == $singleset->longitude))
									{
											//check the difference in departure time
											if ($previousSet->route_id == 2)
											{
													$differenceInTime = strtotime($singleset->time) - strtotime($previousSet->time);
													if ($differenceInTime > 60)
															$output->departure_time = $singleset->time;
											}
											/* if the location is the same and timing is greater than 1 hour, then there must be two trips made.
											   to deal with mis-detection of the beacon on the return route..
												 Example records:

												 bus_id, route_id, lat, lng, time
												 1, 1, 1.66, 103.603, 7:40  (previousSet)
												 1, 1, 1.66, 103.603, 8:03  (singleset)
												 1, 1, 1.66, 103.603, 8:03

											*/
											elseif ($previousSet->route_id == 1)
											{
													$differenceInTime = strtotime($previousSet->time) - strtotime($output->departure_time) ;
													if ($differenceInTime > 3600)
													{
															//insert the previous trip with -1 as the Duration
															$output->duration = -1;
															//add to the output
															if (!is_null($output))
															{
																	//copy trip info in output to tripInfo object to be inserted into $data
																	$tripInfo = json_decode('{"plate_no": "0000", "route_id": "0", "bus_id":0, "departure_time":"0", "duration": -1}');
																	$tripInfo->plate_no = $output->plate_no;
																	$tripInfo->route_id = $output->route_id;
																	$tripInfo->bus_id = $output->bus_id;
																	$tripInfo->departure_time = $output->departure_time;
																	$tripInfo->duration = $output->duration;

																	//Trip identified, and insert into data[]
																	array_push($data, $tripInfo);
															}

															// Reset output with the next journey starting with singleset
															$output->plate_no = $singleset->plate_no;
															$output->route_id = $singleset->route_id;
															$output->departure_time = $singleset->time;
															$output->bus_id = $singleset->bus_id;
															$output->duration = 0;
													}
											}
									}
									//go to next record
									$previousSet = $singleset;

							}
							elseif ($previousSet->bus_id != $singleset->bus_id)
							{
									//add the data to Collection
									/**************
									also need to calculate the duration
									***************/
									$duration = strtotime($previousSet->time) - strtotime($output->departure_time);
									//this means that the previousset's departure time is the same.
									if ($duration == 0)
											$output->duration = -1;
									else {
											$output->duration = $duration;
									}

									if (!is_null($output))
									{
											$tripInfo = json_decode('{"plate_no": "0000", "route_id": "0", "bus_id":0, "departure_time":"0", "duration": -1}');
											$tripInfo->plate_no = $output->plate_no;
											$tripInfo->route_id = $output->route_id;
											$tripInfo->bus_id = $output->bus_id;
											$tripInfo->departure_time = $output->departure_time;
											$tripInfo->duration = $output->duration;
											array_push($data, $tripInfo);
									}
									$response->put($previousSet->bus_id, $data);
									//print_r("bus_id " . $previousSet->bus_id . "\n");
									//print_r($data);
									$previousSet = $singleset;

									//reset the output object with a new departure_time
									$output->plate_no = $singleset->plate_no;
									$output->route_id = $singleset->route_id;
									$output->departure_time = $singleset->time;
									$output->bus_id = $singleset->bus_id;
									$output->duration = -1;

									//reinitialize the $data for the next bus_id
									$data = array();
							}
					}
					$response->put($previousSet->bus_id, $data);
			}

			print_r($data);
			/* 23 Jul 2020
				 When the first bus departed, there is no other buses en route, therefore this departure needs to be added.
			*/
			if (empty($data)) {
					array_push($data, $output);
					$response->put($previousSet->bus_id, $data);
			}
			print_r($response);
			// END 23 Jul 2020

			/*  22 Jul 2020
			    Added a section to return JSON file in a format accepted by react-vis graph / bar chart.
			*/
			//convert response to a format to be displayed using React JS
			$graph_1 = array();
			$graph_2 = array();
			$tripInformation = array();
			foreach ($response as $key => $value) {
				foreach ($value as $trip) {

					//$graph=json_decode('{"label": "0000", "route_id"="0", "bus_id":"0", "x0":"0", "x":"0", "y":"0", "rotation"=-90}');
					$graph = json_decode('{"label": "0000", "route_id": "0", "bus_id":0, "x0":0, "x":"0", "y": 0, "rotation":-90}');
					$graph->label = $trip->plate_no;
					$graph->route_id = $trip->route_id;
					$graph->bus_id = $trip->bus_id;
					$graph->x = strtotime($trip->departure_time)*1000;
					$graph->x0 = $graph->x - 250000;
					if ($trip->duration > 0)
						$graph->y = $trip->duration / 60;
					else {
						$graph->y = $trip->duration;
					}

					//print_r(strtotime($trip->departure_time) . "\n");
					if ($trip->route_id == 1)
					{
							array_push($graph_1, $graph);
					}
					elseif ($trip->route_id == 2)
					{
							array_push($graph_2, $graph);
					}
				}
			}
			array_push($tripInformation, $graph_1);
			array_push($tripInformation, $graph_2);
			return response(json_encode($tripInformation), 200);
			/* END 22 Jul 2020 */

			// print_r("graph 2: " . "\n");
			// print_r($graph_2);
			/* 22 Jul 2020
				 Originally returning a collection.
			//return response(json_encode($response), 200);
			   END 22 Jul 2020 */
	}

	/*  21 July 2020
		  Function to determine the route of the bus given the following parameters:
			parameters:
			bus_service			Mandatory
			first_point 		Mandatory
			last_point 			Optional -- Can be NULL

			Return value:
			one record: {"bus_service_no":0, "route_id": "-1"}, when route_id = -1, then it means that the route cannot be determined.

			if only one point is sent, then check whether it is close to the Terminal station of the route, i.e., the first stop in the bus stop list.
			if both points are sent, then determine which bus route it is on.
	*/

	public function determineRoute(Request $request)
	{
			$bus_service_no = $request->input('bus_service');
			$startPoint = $request->input('first_point');
			$endPoint = $request->input('last_point');
			$result = json_decode('{"bus_service_no":0, "route_id": "-1"}');
			$result->bus_service_no = $bus_service_no;

			$userController = new userController();

			$source = explode(',', $startPoint);
			$destination = explode(',', $endPoint);

			//get all the routes of the bus service number
			/*
					select route_id from bus_route
					where bus_service_no = 7
					group by route_id
			*/
			$getBusRoutes_Query = DB::table('bus_route')
															->select('route_id')
															->where('bus_service_no', $bus_service_no)
															->groupBy('route_id')
															->get();

		  // for each route, check the distance between two points to determine the route
			foreach($getBusRoutes_Query as $singleset)
			{
						//check whether it is at the departure point
						$busStopList = self::getBusstopRoute_method($singleset->route_id);

						//print_r($busStopList);
						$terminalStation = array($busStopList[0]->latitude, $busStopList[0]->longitude);
						// print_r("Route ID: " . $singleset->route_id . "\n");
						// print_r("Start Point: " . $startPoint . "\n");
					  //print_r("First Element in Bus Stop List: " . $terminalStation . "\n");
						$distToTerminal = $userController->caldistance($source, $terminalStation);
						//print_r("Distance to terminal: " . $distToTerminal . "\n");

						//if the distance to terminal is less than 200 meter,
						if ($distToTerminal < 0.1)
						{
								$result->route_id = $singleset->route_id;
								return response(json_encode($result), 200);
						}

						//If only one lat,lon is sent as an argument (first_point), then we can't check the distance between two points.
						if (!is_null($endPoint))
						{
									//the closepointonrtoue return lat,lon,[key] -> key is the index number of the coordinates of the bus routes.
									$s = $userController->closepointonroute($bus_service_no, $singleset->route_id, $source, 0.06);
									$d = $userController->closepointonroute($bus_service_no, $singleset->route_id, $destination, 0.06);

									 // print_r("route ID: " . $singleset->route_id . "\n");
									 // print_r("source: " . $s . "\n");
									 // print_r("destination: " . $d . "\n");
									if ((!is_null($s)) && (!is_null($d)))
									{
											  //due to the returned coordinate in the form of lat,lon,[key], we needed to reconstruct the lat,lon (1.66,103.45)
												$point1 = explode(',', $s);
												$point2 = explode(',', $d);
												$startPoint = $point1[0] . "," . $point1[1];
												$endPoint = $point2[0] . "," . $point2[1];
												// print_r("Start Point:" .  $startPoint . "\n");
											  // print_r("End Point: " . $endPoint . "\n");
												$distance = $userController->getDistanceOnRoute($bus_service_no, $singleset->route_id, $startPoint, $endPoint);

												// print_r($distance);
												if ($distance > 0)
												{
													  $result->route_id = $singleset->route_id;
														break;
												}
												else
														$result->route_id = -1;
									}
									else
												$result->route_id = -1;
							}
			}
			return response(json_encode($result), 200);
	}

		public function newDetermineRoute(Request $request, $bus_service_number)
		{
			$first_lat = $request->query('first_lat');
			$first_lng = $request->query('first_lng');
			$last_lat = $request->query('last_lat');
			$last_lng = $request->query('last_lng');
		
			$result = json_decode('{"bus_service_number":0, "route_id": "-1"}');
			$result->bus_service_number = $bus_service_number;
		
			$userController = new userController();
		
			$source = [$first_lat, $first_lng];
			$destination = ($last_lat && $last_lng) ? [$last_lat, $last_lng] : null;
		
			$getBusRoutes_Query = DB::table('bus_route')
				->select('route_id')
				->where('bus_service_no', $bus_service_number)
				->groupBy('route_id')
				->get();
		
			foreach ($getBusRoutes_Query as $singleset) {
				$busStopList = self::getBusstopRoute_method($singleset->route_id);
				$terminalStation = [$busStopList[0]->latitude, $busStopList[0]->longitude];
		
				$distToTerminal = $userController->caldistance($source, $terminalStation);
		
				if ($distToTerminal < 0.1) {
					$result->route_id = $singleset->route_id;
					return response()->json($result);
				}
		
				if ($destination) {
					$s = $userController->closepointonroute($bus_service_number, $singleset->route_id, $source, 0.06);
					$d = $userController->closepointonroute($bus_service_number, $singleset->route_id, $destination, 0.06);
		
					if ($s && $d) {
						$point1 = explode(',', $s);
						$point2 = explode(',', $d);
						$startPoint = $point1[0] . "," . $point1[1];
						$endPoint = $point2[0] . "," . $point2[1];
		
						$distance = $userController->getDistanceOnRoute($bus_service_number, $singleset->route_id, $startPoint, $endPoint);
		
						if ($distance > 0) {
							$result->route_id = $singleset->route_id;
							break;
						} else {
							$result->route_id = -1;
						}
					} else {
						$result->route_id = -1;
					}
				}
			}
		
			return response()->json($result);
		}
		
	// Return the combined schedule of the arrival time both live arrival time, and schedule arrival time.
	//
	// 21 April 2024
	public function merge_ETA($bus_stop_id, $eta_live) {

		//T30 Schedule
		//$schedule1 = [647, 707, 727, 747, 807, 827, 847, 907, 927, 947, 1007, 1027, 1047, 1107, 1127, 1147, 1207, 1227, 1247, 1307, 1327, 1347, 1407, 1427, 1447, 1507, 1527, 1547, 1607, 1627, 1647, 1707, 1727, 1747, 1807, 1827, 1847, 1907, 1927, 1947, 2007, 2027, 2047, 2107, 2127, 2147, 2207, 2227, 2247, 2307, 2327, 2347];
		//P411 Schedule
		//$schedule2 = [650, 915, 1215, 1445, 1715, 1945];

		$nextETA = array();
		$liveETA = array();
		$data_eta = array();
		$getBusStopInfo_array = array();

		//get current time
		$currentTime = self::getTime();
		$timestamp = date_parse($currentTime);

		//echo date_format($date,"Y/m/d");
		$live_eta = null;
		$xy = null;

		//For each bus service at the particular bus stop, check the bus schedule according to its route
		foreach ($eta_live as $bus_service) {

			//bus schedule (to be retrieved from DB)
			$schedule = array();

			//get bus schedule from the DB, this should only return 1 schedule
			$bus_stop_schedule_query = DB::table('bus_schedule')
								->select('schedule')
								->where('bus_stop_id',$bus_stop_id)
								->where('route_id', $bus_service['route'])
								->get();


			if (count($bus_stop_schedule_query) > 0) {
				$schedule = explode(',', $bus_stop_schedule_query[0]->schedule);
				//print("\n*****");
				//print($bus_service['route']);
				//print("*****\n");
				//print_r($schedule);

				//BEGIN 23th Oct 2024
				//compute the interval between schedule, this is the threshold to display the live arrival time, as the live arrival time could be delayed up to $interval minutes
				$interval = (intdiv($schedule[1],100)*60 + ($schedule[1]%100)) - (intdiv($schedule[0],100)*60 + ($schedule[0]%100));
				$interval = $interval/2;
				if ($interval >= 45) {
					$interval = 45;
				}
				//END 23th Oct 2024

				//find the next ETA for the bus stop based on the current time
				foreach ($schedule as $singleset) {
						if (count($nextETA) < 2) {
							$scheduled_eta_1 = date_create();
							date_date_set($scheduled_eta_1,$timestamp['year'],$timestamp['month'],$timestamp['day']);
							date_time_set($scheduled_eta_1,intdiv($singleset, 100),$singleset % 100);
							$index = $bus_service['bus_service_no']."_".$bus_service['route']. "_".$bus_stop_id."_".intval($singleset);
							//check the current live ETA, if there is, use the current live ETA to decide whether to add.
							if ((strcmp($bus_service['eta_date'],"NA") != 0) and ((abs((date_create($bus_service['eta_date'], timezone_open('Asia/Singapore'))->getTimestamp() - $scheduled_eta_1->getTimestamp()))/60) <= $interval))  {
									$eta = json_decode('{"eta": "0"}');
									$eta->eta = date_format($scheduled_eta_1,"Y-m-d H:i:s");
									array_push($nextETA, $eta);
							}
							else {
									//The scheduled ETA is in the future, or the scheduled ETA is within +/- 5 mins with the current time.
									if ((($scheduled_eta_1->getTimestamp() >= time()) or ((abs($scheduled_eta_1->getTimestamp() - time()) / 60) <= $interval))) {
												$last_arrival = self::getLastETA($bus_stop_id, $bus_service['route'], date_format($scheduled_eta_1,"Y-m-d H:i:s"));
												$schedule_eta = self::getSchedule($index);
												if (count($last_arrival) > 0) {
														//recorded live ETA past current time, and scheduled ETA is in the future, then do not need to add.
														if ((date_create($last_arrival[0]->time, timezone_open('Asia/Singapore'))->getTimestamp()) < time()) {
															//scheduled ETA is in the future
															if ($scheduled_eta_1->getTimestamp() > time()){
																continue;
															}
														}
												}
												//no actual arrival recorded, then use the estimated ETA.
												else {
													if ($schedule_eta[0]->eta != null) {
														//recorded live ETA past current time, and scheduled ETA is in the future, then do not need to add.
														if ((date_create($schedule_eta[0]->eta, timezone_open('Asia/Singapore'))->getTimestamp()) < time()) {
															//scheduled ETA is in the future
															if ($scheduled_eta_1->getTimestamp() > time()){
																continue;
															}
														}
														else {
															$eta = json_decode('{"eta": "0"}');
															$eta->eta = date_format($scheduled_eta_1,"Y-m-d H:i:s");
															array_push($nextETA, $eta);
														}
													}
													//There is no live ETA recorded in db,
													elseif ($schedule_eta[0]->eta == null) {
														//if no current live eta, then this is based on the schedule, then only display the arrival time according to the current time.
															if (($scheduled_eta_1->getTimestamp()) >= time()) {
																$eta = json_decode('{"eta": "0"}');
																$eta->eta = date_format($scheduled_eta_1,"Y-m-d H:i:s");
																array_push($nextETA, $eta);
															}
														}
											  }
									}
								}
						}
						else {
									break;
						}
				}
			}
			else {
				return $eta_live;
			}

			if (count($nextETA) > 0) {
					//get relative time
					$data_eta = self::calculateEta($nextETA);

					$live_eta_1 = 0;
					$live_eta_2 = 0;

					if ((strcmp($bus_service['eta_date'],"NA") != 0) and ($data_eta[0] != null)) {
						//live ETA - schedule ETA < 10 mins
						if((abs(date_create($bus_service['eta_date'], timezone_open('Asia/Singapore'))->getTimestamp() - strtotime($data_eta[0]->eta[0]['time']))/60) <= $interval) {
							$timestamp = date_parse($data_eta[0]->eta[0]['time']);
							$element = $timestamp['hour']*100 + $timestamp['minute'];
							$index = $bus_service['bus_service_no']. "_".$bus_service['route']. "_".$bus_stop_id. "_".$element;
							$data_eta[0]->eta[0]['time'] = $bus_service['eta_date'];
							$data_eta[0]->eta[0]['relative_time'] = $bus_service['stop_eta'];
							$live_eta_1 = 1;
							//$schedule_eta = self::getSchedule($index);
							//if (count($schedule_eta) < 2)
							//update the schedule when it is close to arrival, and only update if the eta is less than the scheduled ETA.
							//if (date_create($bus_service['eta_date'], timezone_open('Asia/Singapore'))->getTimestamp() <= strtotime($data_eta[0]->eta[0]['time'])) {
							if (((date_create($bus_service['eta_date'], timezone_open('Asia/Singapore'))->getTimestamp() - time())/60) < 5) {
									self::updateSchedule($index, $bus_service['eta_date']);
							}
						}
						//BEGIN 9th MAY 2024
						//change to check the count of next_ETA
						//elseif ($data_eta[1] != null) {
						elseif (count($data_eta) > 1) {
						//END 9th MAY 2024
							if((abs(date_create($bus_service['eta_date'], timezone_open('Asia/Singapore'))->getTimestamp() - strtotime($data_eta[1]->eta[0]['time']))/60) <= $interval) {
								//$timestamp = date_parse($data_eta[1]->eta[0]['time']);
								//$element = $timestamp['hour']*100 + $timestamp['minute'];
								//$index = $bus_service['bus_service_no']. "_".$bus_service['route']. "_".$bus_stop_id. "_".$element;
								//$schedule_eta = self::getSchedule($index);
								//if (count($schedule_eta) < 2)
								//self::updateSchedule($index, $bus_service['eta_date']);
								//$this->timetable[$index] = $bus_service['eta_date'];
								$data_eta[1]->eta[0]['time'] = $bus_service['eta_date'];
								$data_eta[1]->eta[0]['relative_time'] = $bus_service['stop_eta'];
								$live_eta_2 = 1;
							}
						}
					}
					//the second live ETA should corresponds to the second scheduled ETA
					if (strcmp($bus_service['eta_date2'],"NA") != 0){
						//second live ETA matches the first scheduled ETA - this is the case where the bus already passed the bus stop, but the live ETA is not updated yet.
						if((abs(date_create($bus_service['eta_date2'], timezone_open('Asia/Singapore'))->getTimestamp() - strtotime($data_eta[0]->eta[0]['time']))/60) <= $interval) {
							//$eta->eta = $bus_service['eta_date2'];
							//$timestamp = date_parse($data_eta[0]->eta[0]['time']);
							//$element = $timestamp['hour']*100 + $timestamp['minute'];
							//$index = $bus_service['bus_service_no']. "_".$bus_service['route']. "_".$bus_stop_id. "_".$element;
							//$schedule_eta = self::getSchedule($index);
							//if (count($schedule_eta) < 2)
							//self::updateSchedule($index, $bus_service['eta_date2']);
							//$this->timetable[$index] = $bus_service['eta_date2'];
							$data_eta[0]->eta[0]['time'] = $bus_service['eta_date2'];
							$data_eta[0]->eta[0]['relative_time'] = $bus_service['stop_eta2'];
							$live_eta_1 = 1;
						}
						//check if live eta 2 is 0 (false), if so, then this would could be the second arrival time.. otherwise ignore the live eta
						if (($live_eta_2 == 0) and ($data_eta[1] != null)) {
								if((abs(date_create($bus_service['eta_date2'], timezone_open('Asia/Singapore'))->getTimestamp() - strtotime($data_eta[1]->eta[0]['time']))/60) <= $interval) {
									//$eta->eta = $bus_service['eta_date2'];
									//$timestamp = date_parse($data_eta[1]->eta[0]['time']);
									//$element = $timestamp['hour']*100 + $timestamp['minute'];
									//$index = $bus_service['bus_service_no']. "_".$bus_service['route']. "_".$bus_stop_id. "_".$element;
									//$schedule_eta = self::getSchedule($index);
									//if (count($schedule_eta) < 2)
									//self::updateSchedule($index, $bus_service['eta_date2']);
									//$this->timetable[$index] = $bus_service['eta_date2'];
									$data_eta[1]->eta[0]['time'] = $bus_service['eta_date2'];
									$data_eta[1]->eta[0]['relative_time'] = $bus_service['stop_eta2'];
									$live_eta_2 = 1;
								}
						}
					}
			}

			$dataset_busList = [
						'bus_service_no' => $bus_service['bus_service_no'],
						//if fixed schedule, print the actual time, instead of relative time.
						'stop_eta' => "NA",
						'stop_eta2' => "NA",
						'Destination' => $bus_service['Destination'],
						'eta_date' => "NA",
						'eta_date2' => "NA",
						'route' => $bus_service['route'],
						'live' => 0,
						'live2' => 0,//means it is from the timetable
					];

			//set the eta time of the schedule, as it could be that there's no service after the operation hours.
			if (count($data_eta) > 0) {
					if ($data_eta[0] != null) {
							if (($data_eta[0]->eta[0]['relative_time'] > 1) and ($data_eta[0]->eta[0]['relative_time'] < 60))
								$dataset_busList['stop_eta'] = $data_eta[0]->eta[0]['relative_time']. " mins";
						  elseif ($data_eta[0]->eta[0]['relative_time'] >= 60)
								$dataset_busList['stop_eta'] = date("H:i", strtotime($data_eta[0]->eta[0]['time']));
							else {
								$dataset_busList['stop_eta'] = "Arriving";
							}
			 				$dataset_busList['eta_date'] = $data_eta[0]->eta[0]['time'];
			 		}
					if (count($data_eta) >= 2) {
							if (($data_eta[1]->eta[0]['relative_time'] > 1) and ($data_eta[1]->eta[0]['relative_time'] < 60))
								$dataset_busList['stop_eta2'] = $data_eta[1]->eta[0]['relative_time']. " mins";
							elseif ($data_eta[1]->eta[0]['relative_time'] >= 60)
									$dataset_busList['stop_eta2'] = date("H:i", strtotime($data_eta[1]->eta[0]['time']));
							else {
								$dataset_busList['stop_eta2'] = "Arriving";
							}
							$dataset_busList['eta_date2'] = $data_eta[1]->eta[0]['time'];
					}

					//use the live arrival timing is the live flag is set.
					if ($live_eta_1 == 1) {
						$dataset_busList['live'] = 1;
						$dataset_busList['stop_eta'] = $data_eta[0]->eta[0]['relative_time'];
					}
					if ($live_eta_2 == 1) {
							$dataset_busList['live2'] = 1;
							$dataset_busList['stop_eta2'] = $data_eta[1]->eta[0]['relative_time'];
					}
			}

			//add to the bus list next arrival time
			array_push($getBusStopInfo_array, $dataset_busList);

			$dataset_busList = array();
			$live_eta_1 = 0;
			$live_eta_2 = 0;

			//BEGIN 20 Aug 2024
			//reset nextETA and data_ETA
			$nextETA = array();
			$data_eta = array();
			//END 20 Aug 2024
		}

		return $getBusStopInfo_array;

	}
	//END 21 April 2024

	//4 MAY 2024
	//Update the database that a particular schedule at the bus stop has live eta, hence do not show the scheduled timetable if the bus came early
	public function updateSchedule($id, $eta)
	{
		$time = self::getTime();

		$insertLocationData_Query = DB::table('schedule_status')
								->insert([
								'id'=>$id,
								'eta'=>$eta,
								'time'=>$time]);
	}

	public function getSchedule($id)
	{
		//get Today's date - for testing purposes, use a specific date 2020 Jan 1.
		$date = date('Y-m-d', time());

		$today = $date . " 00:00:00";

		//get bus schedule from the DB, this should only return 1 schedule
		//8 MAY 2024
		//Modified the query such that the condition is to find the latest ETA
		$bus_schedule_query = DB::table('schedule_status')
							//->select('id', 'eta')
							->selectraw('id, eta, max(time)')
							->where('id',$id)
							->whereraw("time > '{$today}' and time = (SELECT MAX(t.time) FROM schedule_status t WHERE t.id = '{$id}')")
							->get();

		return $bus_schedule_query;
	}
	//END 4 MAY 2024

	//BEGIN 19th MAY 2024
	//check whether the bus has passed the station?
	public function getLastETA($bus_stop_id, $route_id, $schedule_eta)
	{
		//get Today's date - for testing purposes, use a specific date 2020 Jan 1.
		$date = date('Y-m-d H:i:s', time());

		//21th MAY 2024
		//check the actual arrival time of the bus (provided that the detection is accurate
		$latitude = 0;
		if ($bus_stop_id == 1065)
				$latitude = 1.63944;
		elseif ($bus_stop_id == 1012)
				$latitude = 1.60117;
		elseif ($bus_stop_id == 1006)
				$latitude = 1.63972;
		elseif ($bus_stop_id == 1057)
				$latitude = 1.60057;

		//$today = $date . " 00:00:00";

		$bus_eta_query = DB::table('location_datav2')
							//->select('id', 'eta')
							->select('bus_id', 'time')
							->where('latitude',$latitude)
							->where('route_id', $route_id)
							->whereraw("time < '{$schedule_eta}' + INTERVAL 660 SECOND")
							->whereraw("time > '{$schedule_eta}' - INTERVAL 540 SECOND")
							//->whereraw("time = (SELECT MAX(t.time) FROM etav2 t where t.bus_stop_id = '{$bus_stop_id}')")
							->orderby('time', 'asc')
							->limit(1)
							->get();

		//print_r($schedule_eta);
		//print_r($bus_eta_query);
		return $bus_eta_query;
		//END 21th MAY 2024
	}
	//END 4 MAY 2024

	// Route::get('/bus-services/{bus_serivce_number}/routes','getBusInfoController@determineRoute');
	public function determineRouteV2(Request $request, $bus_service_no)
	{
			// $bus_service_no = $request->input('bus_service');
			$startPoint = $request->input('first_point');
			$endPoint = $request->input('last_point');
			
			$result = json_decode('{"bus_service_no":0, "route_id": "-1"}');
			$result->bus_service_no = $bus_service_no;

			$userController = new userController();

			$source = explode(',', $startPoint);
			$destination = explode(',', $endPoint);

			//get all the routes of the bus service number
			/*
					select route_id from bus_route
					where bus_service_no = 7
					group by route_id
			*/
			$getBusRoutes_Query = DB::table('bus_route')
															->select('route_id')
															->where('bus_service_no', $bus_service_no)
															->groupBy('route_id')
															->get();

		  // for each route, check the distance between two points to determine the route
			foreach($getBusRoutes_Query as $singleset)
			{
						//check whether it is at the departure point
						$busStopList = self::getBusstopRoute_method($singleset->route_id);

						//print_r($busStopList);
						$terminalStation = array($busStopList[0]->latitude, $busStopList[0]->longitude);
						// print_r("Route ID: " . $singleset->route_id . "\n");
						// print_r("Start Point: " . $startPoint . "\n");
					  //print_r("First Element in Bus Stop List: " . $terminalStation . "\n");
						$distToTerminal = $userController->caldistance($source, $terminalStation);
						//print_r("Distance to terminal: " . $distToTerminal . "\n");

						//if the distance to terminal is less than 200 meter,
						if ($distToTerminal < 0.1)
						{
								$result->route_id = $singleset->route_id;
								return response(json_encode($result), 200);
						}

						//If only one lat,lon is sent as an argument (first_point), then we can't check the distance between two points.
						if (!is_null($endPoint))
						{
									//the closepointonrtoue return lat,lon,[key] -> key is the index number of the coordinates of the bus routes.
									$s = $userController->closepointonroute($bus_service_no, $singleset->route_id, $source, 0.06);
									$d = $userController->closepointonroute($bus_service_no, $singleset->route_id, $destination, 0.06);

									 // print_r("route ID: " . $singleset->route_id . "\n");
									 // print_r("source: " . $s . "\n");
									 // print_r("destination: " . $d . "\n");
									if ((!is_null($s)) && (!is_null($d)))
									{
											  //due to the returned coordinate in the form of lat,lon,[key], we needed to reconstruct the lat,lon (1.66,103.45)
												$point1 = explode(',', $s);
												$point2 = explode(',', $d);
												$startPoint = $point1[0] . "," . $point1[1];
												$endPoint = $point2[0] . "," . $point2[1];
												// print_r("Start Point:" .  $startPoint . "\n");
											  // print_r("End Point: " . $endPoint . "\n");
												$distance = $userController->getDistanceOnRoute($bus_service_no, $singleset->route_id, $startPoint, $endPoint);

												// print_r($distance);
												if ($distance > 0)
												{
													  $result->route_id = $singleset->route_id;
														break;
												}
												else
														$result->route_id = -1;
									}
									else
												$result->route_id = -1;
							}
			}
			return response(json_encode($result), 200);
	}

}
