<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
date_default_timezone_set('Asia/Singapore');
class DatabaseController
{
	public function getTime()
	{
		date_default_timezone_set('Asia/Singapore');
		return date('Y-m-d H:i:s', time());
	}

	//tested
	public function insertLocationData($bus_id, $route_id, $imei, $newlocation, $newlocation1, $speedkmhr)
	//public function insertLocationData(Request $request)
	{

		/* $bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$imei = $request->input('imei');
		$newlocation = $request->input('newlocation');
		$newlocation1 = $request->input('newlocation1');
		$speedkmhr = $request->input('speedkmhr'); */

		$time = self::getTime();

		$insertLocationData_Query = DB::table('location_data')
								->insert([
								'bus_id'=>$bus_id,
								'route_id'=>$route_id,
								'imei'=>$imei,
								'latitude'=>$newlocation,
								'longitude'=>$newlocation1,
								'speed'=>$speedkmhr,
								'time'=>$time]);

	}



	public function uploadETA($bus_id,$route_id,$bus_stop_id,$eta,$time,$avgspeed)
	//tested
	//public function uploadETA(Request $request)
	{

		/* $bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$bus_stop_id = $request->input('bus_stop_id');
		$eta = $request->input('eta');
		$time = $request->input('time');
		$avgspeed = $request->input('avgSpeed');
		 */
		$uploadETA_Query = DB::table('eta')
						->insert([
						'bus_id'=>$bus_id,
						'route_id'=>$route_id,
						'bus_stop_id'=>$bus_stop_id,
						'eta'=>$eta,
						'time'=>$time,
						'avgspeed'=>$avgspeed
						]);

	}
	public function updateFlag($flag,$bus_id,$route_id,$time)
	//tested
	//public function updateFlag(Request $request)
	{
		/* $flag = $request->input('flag');
		$bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$time = $request->input('time'); */

		$updateFlag_Query = DB::table('location_data')
								->where('bus_id',$bus_id)
								->where('route_id',$route_id)
								->where('time',$time)
								->update(['flag'=>$flag]);
	}

	public function getLastRecord($bus_id,$routeno,$time)
	//tested
	//public function getLastRecord()
	{

		/* $bus_id = 1;
		$routeno = 1;
		$time = '2015-09-06 15:33:53'; */

		$currTime = self::getTime();
		$speed = 0;

		$getLastRecord_subQuery = DB::table('bus_route as br')
										->select('l.bus_id', 'l.latitude', 'l.longitude', 'l.speed', 'br.bus_service_no', 'l.flag', 'l.route_id', 'l.time', 'l.imei')
										->join('location_data as l', 'br.bus_id', '=', 'l.bus_id')
										->join('route as r', 'r.route_id', '=', 'br.route_id')
										->where('l.bus_id',$bus_id)
										->where('l.route_id',$routeno)
										->where('l.time', '>',$time)
										->where('l.time', '<',$currTime)
										->where('l.speed', '>', $speed)
										->orderBy('l.time', 'desc');

		$getLastRecord_Query = DB::table(DB::raw("({$getLastRecord_subQuery->toSql()}) as location") );
		$getLastRecord_Query->mergeBindings( $getLastRecord_subQuery );
		$getLastRecord_Query->groupBy('imei');
		$getLastRecord_Query = $getLastRecord_Query->get();

		$data = array();

		foreach($getLastRecord_Query as $singleset)
		{

			array_push($data,$singleset);
		}

		return $data;
	}


	public function getLastRecordV2($bus_id,$routeno,$time)
	//tested
	//public function getLastRecordV2()
	{

		/* $bus_id = 1;
		$routeno = 1;
		$time = '2015-09-06 15:33:53'; */

		$currTime = self::getTime();
		$speed = 0;

		$getLastRecordV2_subQuery = DB::table('bus_route as br')
										->select('l.bus_id', 'l.latitude', 'l.longitude', 'l.speed', 'br.bus_service_no', 'l.flag', 'l.route_id', 'l.time', 'l.imei')
										->join('location_datav2 as l', 'br.bus_id', '=', 'l.bus_id')
										//->join('route as r', 'r.route_id', '=', 'br.route_id')
										->where('l.bus_id',$bus_id)
										->where('l.route_id',$routeno)
										->where('l.time', '>',$time)
										->where('l.time', '<',$currTime)
										->where('l.speed', '>=', $speed)
										->orderBy('l.time', 'desc');

		$getLastRecordV2_Query = DB::table(DB::raw("({$getLastRecordV2_subQuery->toSql()}) as location") );
		$getLastRecordV2_Query->mergeBindings( $getLastRecordV2_subQuery);
		//Begin 9 August 2024
		//Groupby bus_id instead of imei as imei is no longer useful
		//$getLastRecordV2_Query->groupBy('imei');
		$getLastRecordV2_Query->groupBy('bus_id');
		//end 9 August 2024
		$getLastRecordV2_Query = $getLastRecordV2_Query->get();


		$data = array();

		foreach($getLastRecordV2_Query as $singleset)
		{
			array_push($data,$singleset);
		}

		return $data;
	}

	public function avgSpeed($routeno,$bus_id,$timehigh,$timelow)
	//tested
	//public function avgSpeed(Request $request)
	{

		/* $routeno = $request->input('routeno');
		$bus_id = $request->input('bus_id');
		$timehigh = $request->input('timehigh');
		$timelow = $request->input('timelow'); */

		$speed = 0.0;

		$avgSpeed_Query = DB::table('location_data')
						->where('route_id',$routeno)
						->where('bus_id',$bus_id)
						->where('time','<=', $timehigh)
						->where('time', '>=', $timelow)
						->where('speed','>', $speed)
						->avg('speed');


		/* $data = array();
		foreach($avgSpeed_Query as $singleset)
		{
			$data = $singleset;
		} */
		return $avgSpeed_Query;
		//return $data[0];
	}

	public function checkHistoryExist($routeno,$bus_id)
	//tested
	//public function checkHistoryExist(Request $request)
	{
		/* $routeno = $request->input('routeno');
		$bus_id = $request->input('bus_id'); */
		$avgSpeed = -1;

		$checkHistoryExist_Query = DB::table('eta')
										->select(DB::raw('count(*),time'))
										->where('bus_id',$bus_id)
										->where('route_id',$routeno)
										->where('avgSpeed',$avgSpeed)
										->groupBy('time')
										->orderBy('time', 'desc')
										->limit(1)
										->get();
		/*
		$data = array();

		foreach($checkHistoryExist_Query as $singleset)
		{
			$data = $singleset;
		} */

		return $checkHistoryExist_Query;
		//return $data;
	}
	public function getHistoryETAV1($bus_id,$route_id,$bus_service_no,$busstop_id,$keepTime)
	//tested
	//public function getHistoryETAV1()
	{
		$getHistoryETAV1_Dataset = new Collection;
		$bus_stop_route_order = self::getroute_order_bybusstopid($busstop_id, $route_id);


		$route_order_next = DB::table('route_bus_stop')
									->select('bus_stop_id')
									->where('route_id',$route_id)
									->where('route_order', '>=',$bus_stop_route_order)
									->orderBy('route_order', 'asc')
									->get();

		foreach($route_order_next as $bus_stop_id_next)
		{

		 $getHistoryETAV1_Query = DB::table('avg_speed_calculated')
									->select('avg_time','bus_stop_id_next')
									->where('route_id',$route_id)
									->where('bus_service_no',$bus_service_no)
									->where('bus_stop_id_next',$bus_stop_id_next->bus_stop_id)
									->first();

			if($getHistoryETAV1_Query != null)
			{

				$getHistoryETA_Dataset->push($getHistoryETAV1_Query);
			}
		}

		if($keepTime != 0)
		{
			$time = $keepTime;
		}
		else
		{
			$time = 0;
		}

		foreach($getHistoryETAV1_Dataset as $singleset)
		{


			$time = $singleset->avg_time + $time;
			$avgspeed = -1;
			$calcTime = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
			$get_Time = self::getTime();
			self::uploadETA($bus_id,$route_id,$singleset->bus_stop_id_next,$calcTime,$get_Time,$avgspeed);

		}
	}



	public function retrieveLocationDataV2($routeno,$bus_id,$timehigh,$timelow)
	//tested
	//public function retrieveLocationData()
	{
		/* $routeno = 1;
		$bus_id = 1;
		$timehigh ='2016-09-07 15:17:58';
		$timelow ='2016-09-02 11:05:40'; */
		$retrieveLocationData_Query = DB::table('location_datav2')
											->select('latitude', 'longitude', 'time', 'speed' )
											->where('route_id',$routeno)
											->where('bus_id',$bus_id)
											->where('time','<=',$timehigh)
											->where('time','>=',$timelow)
											->orderBY('time', 'asc')
											->get();

		$data = array();
		$i = 0;

		foreach($retrieveLocationData_Query as $singleset)
		{

			$data[$i] = $singleset;
			$i++;
		}

		return $data;
	}

	public function retrieveLocationData($routeno,$bus_id,$timehigh,$timelow)
	//tested
	//public function retrieveLocationData()
	{
		/* $routeno = 1;
		$bus_id = 1;
		$timehigh ='2016-09-07 15:17:58';
		$timelow ='2016-09-02 11:05:40'; */
		$retrieveLocationData_Query = DB::table('location_data')
											->select('latitude', 'longitude', 'time', 'speed' )
											->where('route_id',$routeno)
											->where('bus_id',$bus_id)
											->where('time','<=',$timehigh)
											->where('time','>=',$timelow)
											->orderBY('time', 'asc')
											->get();

		$data = array();
		$i = 0;

		foreach($retrieveLocationData_Query as $singleset)
		{

			$data[$i] = $singleset;
			$i++;
		}

		return $data;
	}

	public function checkHistoryExistV2($routeno,$bus_id)
	//tested
	//public function checkHistoryExistV2()
	{

		/* $routeno = 1;
		$bus_id = 1; */
		$avgSpeed = -1;

		$checkHistoryExistV2_Query = DB::table('etav2')
										->select(DB::raw('count(*),time'))
										->where('bus_id',$bus_id)
										->where('route_id',$routeno)
										->where('avgSpeed',$avgSpeed)
										->groupBy('time')
										->orderBy('time', 'desc')
										->limit(1)
										->get();

		/* $data = array();
		$i=0;
		foreach($checkHistoryExistV2_Query as $singleset)
		{
			var_dump($singleset);
			die();
			$data[$i] = $singleset;
			$i++;
		} */

		return $checkHistoryExistV2_Query;
		//return $data;
	}

	public function uploadETAV2($bus_id,$route_id,$bus_stop_id,$eta,$time,$avgspeed)
	//tested
	//public function uploadETAV2()
	{
		/* $bus_id = 1;
		$route_id = 1;
		$bus_stop_id = 1002;
		$eta ='2018-01-12 10:37:32';
		$time = '2018-01-12 10:33:08';
		$avgspeed = -1; */
		var_dump("time : ".$time." eta : ".$eta);
		$uploadETAV2_Query = DB::table('etaV2')
						->insert([
						'bus_id'=>$bus_id,
						'route_id'=>$route_id,
						'bus_stop_id'=>$bus_stop_id,
						'eta'=>$eta,
						'time'=>$time,
						'avgspeed'=>$avgspeed
						]);


	}

	public function insertLocationDataV2($bus_id, $route_id, $imei, $newlocation, $newlocation1, $speedkmhr)
	//tested
	//public function insertLocationDataV2()
	{
		/* $bus_id = 1;
		$route_id = 1;
		$imei = 358672054574474;
		$newlocation = 1.448880;
		$newlocation1 =103.820102;
		$speedkmhr = 20.0; */

		$time = self::getTime();

		$insertLocationDataV2_Query = DB::table('location_datav2')
							->insert([
							'bus_id'=>$bus_id,
							'route_id'=>$route_id,
							'imei'=>$imei,
							'latitude'=>$newlocation,
							'longitude'=>$newlocation1,
							'speed'=>$speedkmhr,
							'time'=>$time]);
	}
	public function updateFlagV2($flag,$bus_id,$route_id,$time)
	//tested
	//public function updateFlagV2()
	{
		/* $flag = 1;
		$bus_id = 1;
		$route_id = 1;
		$time = '2016-09-06 15:33:53'; */

		$updateFlag_Query = DB::table('location_datav2')
								->where('bus_id',$bus_id)
								->where('route_id',$route_id)
								->where('time',$time)
								->update(['flag' => $flag]);
	}
	public function getHistoryETA($bus_id,$route_id,$bus_service_no,$busstop_id,$keepTime)
	//tested
	//public function getHistoryETA()
	{


		$getHistoryETA_Dataset = new Collection;
		$bus_stop_route_order = self::getroute_order_bybusstopid($busstop_id, $route_id);


		$route_order_next = DB::table('route_bus_stop')
									->select('bus_stop_id')
									->where('route_id',$route_id)
									->where('route_order', '>=',$bus_stop_route_order)
									->orderBy('route_order', 'asc')
									->get();

		foreach($route_order_next as $bus_stop_id_next)
		{

		 $getHistoryETA_Query = DB::table('avg_speed_calculated')
									->select('avg_time','bus_stop_id_next')
									->where('route_id',$route_id)
									->where('bus_service_no',$bus_service_no)
									->where('bus_stop_id_next',$bus_stop_id_next->bus_stop_id)
									->first();

			if($getHistoryETA_Query != null)
			{

				$getHistoryETA_Dataset->push($getHistoryETA_Query);
			}
		}

		if($keepTime != 0)
		{
			$time = $keepTime;
		}
		else
		{
			$time = 0;
		}

		foreach($getHistoryETA_Dataset as $singleset)
		{


			$time = $singleset->avg_time + $time;
			$avgspeed = -1;
			$calcTime = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
			$get_Time = self::getTime();
			self::uploadETAV2($bus_id,$route_id,$singleset->bus_stop_id_next,$calcTime,$get_Time,$avgspeed);

		}


	}

	public function getFirstBusstopIDFromRoute($bus_id,$route_id)
	//tested
	//public function getFirstBusstopIDFromRoute()
	{
		/* $bus_id = 1;
		$route_id =1; */
		$getFirstBusstopIDFromRoute_Query = DB::table('route')
												->select('route_bus_stop.bus_stop_id')
												->join('bus_route', 'bus_route.route_id', '=', 'route.route_id')
												->join('route_bus_stop', 'route.route_id', '=', 'route_bus_stop.route_id')
												->where('bus_route.bus_id',$bus_id)
												->where('bus_route.route_id',$route_id)
												->where('route_bus_stop.route_order',1)
												->first();
		/* var_dump($getFirstBusstopIDFromRoute_Query);
		die();
		$totalbus = array();

		foreach($getFirstBusstopIDFromRoute_Query as $singleset)
		{
			$totalbus = $singleset;
		} */

		//return $totalbus[0];
		return $getFirstBusstopIDFromRoute_Query->bus_stop_id;
	}

	public function getbusstopid_byroute_order($route_order,$route_id)
	{

		$getbusstopid_byroute_order_Query = DB::table('route_bus_stop')
												->select('bus_stop_id')
												->where('route_id',$route_id)
												->where('route_order',$route_order)
												->first();

		return $getbusstopid_byroute_order_Query->bus_stop_id;
	}
	public function getroute_order_bybusstopid($bus_stop_id,$route_id)
	{

		$getroute_order_bybusstopid_Query = DB::table('route_bus_stop')
												->select('route_order')
												->where('route_id',$route_id)
												->where('bus_stop_id',$bus_stop_id)
												->first();


		return $getroute_order_bybusstopid_Query->route_order;
	}




	//tested
	public function getTotalBus()
	{
		$totalbus = array();

		$getTotalBus_Query = DB::table('bus')
								->select('bus_id')
								->get();

		foreach($getTotalBus_Query as $singleset)
		{
			array_push($totalbus,$singleset->bus_id);
		}

		return $totalbus;
	}



	public function getBusIDByBeacon($beacon_mac)
	{
		$getBusIDByBeacon_Query = DB::table('bus')
										->select('bus_id')
										->where('beacon_mac',$beacon_mac)
										->limit(1)
										->first();

		if($getBusIDByBeacon_Query != null)
		{
			return $getBusIDByBeacon_Query->bus_id;
		}
		else
		{
			return null;
		}
	}

	public function getAllBusIDByBeacon($pi_id)
	{
		$getAllBusIDByBeacon_Query = DB::table('bus')
									->select('bus.bus_id','bus.beacon_mac')
									->join('bus_route','bus_route.bus_id','=','bus.bus_id')
									->join('route','route.route_id','=','bus_route.route_id')
									->join('route_pi','route_pi.route_id','=','route.route_id')
									->where('route_pi.pi_id',$pi_id)
									->get();

		return $getAllBusIDByBeacon_Query;
	}

	public function rmPhantomETA($bus_id)
	{
		$time = self::getTime();
		$rmPhantomETAV2_Query = DB::table('etav2')
													->where('eta', '>', $time)
													->where('bus_id', $bus_id)
													->delete();
		// $rmPhantomETA_Query = DB::table('eta')
		// 											->where('eta', '>', $time)
		// 											->where('bus_id', $bus_id)
		// 											->delete();

		return $rmPhantomETAV2_Query;
	}

	public function getBusServiceNo($route_id,$bus_id)
	//tested
	//public function getBusServiceNo()
	{
		/* $route_id = 1;
		$bus_id = 1; */
		$getBusServiceNo_Query = DB::table('bus_route')
										->select('bus_route.bus_service_no')
										->join('bus','bus.bus_id','=','bus_route.bus_id')
										->join('route','route.route_id','=','bus_route.route_id')
										->where('route.route_id',$route_id)
										->where('bus_route.bus_id',$bus_id)
										->get();

		$data = array();

		foreach($getBusServiceNo_Query as $singleset)
		{
			$data= $singleset->bus_service_no;
		}

		if($data == null)
		{
			return null;
		}

		return $data;
	}

	public function getlatlongByPi($pi_id)
	{
		$getlatlongByPi_Query = DB::table('pi_info')
										->where('pi_id',$pi_id)
										->limit(1)
										->first();

		return $getlatlongByPi_Query;
	}

	public function getpi_routeid($pi_id)
	{
		$getpi_routeid_Query = DB::table('route_pi')
									->where('pi_id',$pi_id)
									->get();
		$route_id = array();

		foreach($getpi_routeid_Query as $singleset)
		{
			array_push($route_id,$singleset->route_id);
		}

		return $route_id;
	}

	public function getRouteID($bus_id)
	//tested
	//public function getRouteID()
	{
		/* $bus_id =2; */
		$getRouteID_Query = DB::table('bus_route')
								->select('route_id')
								->where('bus_id',$bus_id)
								->get();

		$route_id = array();

		foreach($getRouteID_Query as $singleset)
		{
			array_push($route_id,$singleset->route_id);
		}

		return $route_id;
	}

  /*04-07-2020
	  Function to retrieve the bus location currently operating on the day. It looks into the location_datav2 table to get real-time locations
		as well as the estimated arrial time at the bus stop if the current location is not updated.

		outputs: bus_id, plate_no, time, latitude, longitude, estimated (0=real bus location, 1=estimated location).
	*/

	/*
		19-08-2022
		Function replaced by getBusCurrentLocations_v2() as was not needed to estimate the current bus location anymore
	*/
	public function getBusCurrentLocations()
	{

			//getcurrent time
			$currentTime = self::getTime();
			//get time two hours ago
			$twoHoursAgo = date("Y-m-d H:i:s", strtotime("-7200 seconds"));

			//retrieve the bus stop coordinates, and put them into a collection.
			//Can possibility load just one time, rather than keep querying the database.
			$getBusStop_Query = DB::table('bus_stop')
										->select('bus_stop.bus_stop_id', 'bus_stop.latitude', 'bus_stop.longitude')
										->get();

			$busStopCoordinates = collect();
			foreach($getBusStop_Query as $singleset)
			{
					$latlong = strval($singleset->latitude) .",". strval($singleset->longitude);
					$busStopCoordinates->put($singleset->bus_stop_id, $latlong);
			}
			//End retrieval of bus coordinates.


			/* Equivalent SQL for getting the location from etav2 table

			select a.bus_id, bus_stop_id, a.eta from etav2 as a
				inner join (
						select bus_id, max(eta) as eta from etav2
						where eta <= '2020-07-05 21:40:00' and eta > '2020-07-05 10:35:00'
						group by bus_id) as b
				on b.eta = a.eta
				group by bus_id, a.eta
			*/
			$sub = DB::table('etav2 as e')
							->select('bus_id', DB::raw('MAX(eta) as eta'))
							->where('e.eta', '<=', $currentTime)
							->where('e.eta', '>', $twoHoursAgo)
							->groupBy('e.bus_id');

			$estimatedBusLocation_Query = DB::table('etav2 as e')
																		  ->select('e.bus_id', 'b.plate_no', 'e.route_id', DB::raw('MAX(e.bus_stop_id) as bus_stop_id'), 'e.eta as time')
																			->join('bus as b', 'b.bus_id', '=', 'e.bus_id')
																			->join(DB::raw("({$sub->toSql()}) as sub"), 'e.eta', '=', 'sub.eta')
																			->mergeBindings($sub)
																			->groupby('e.bus_id', 'e.route_id', 'e.eta')
																			->get();

			$data = collect();
			foreach($estimatedBusLocation_Query as $singleset)
			{
					$latlong = $busStopCoordinates->get($singleset->bus_stop_id);
					$location = explode(",", $latlong);
					$singleset->latitude = $location[0];
					$singleset->longitude = $location[1];
					$singleset->estimated = 1;

					unset($singleset->bus_stop_id);
					$data->put($singleset->bus_id, $singleset);
					// array_push($data,$singleset);
			}

			//print(json_encode($data));

			/* Get the bus location reported by drivers or raspberry_pi in real-time if available:
			 subquery to get last record update of location per bus_id

			 select a.bus_id, a.route_id, a.latitude, a.longitude, a.time from location_datav2 as a
			 inner join (
			 			select bus_id, max(time) as time from location_datav2
						where time <= '2020-07-05 21:16:00' and time > '2020-07-05 10:35:00'
						group by bus_id) as b
			 on a.time = b.time
			 group by a.bus_id, a.route_id, a.latitude, a.longitude, a.time;
			*/
			$sub = DB::table('location_datav2 as l')
							->select('bus_id', DB::raw('MAX(time) as time'))
							->where('l.time', '<=', $currentTime)
							->where('l.time', '>', $twoHoursAgo)
							->groupBy('l.bus_id');

			$realBusLocation_Query = DB::table('location_datav2 as l')
																->select('l.bus_id', 'b.plate_no', 'l.route_id', 'l.time', 'l.latitude', 'l.longitude')
																->join('bus as b', 'b.bus_id', '=', 'l.bus_id')
																->join(DB::raw("({$sub->toSql()}) as sub"), 'l.time', '=', 'sub.time')
																->mergeBindings($sub)
																->groupby('l.bus_id', 'l.route_id', 'l.time')
																->get();

			foreach($realBusLocation_Query as $singleset)
			{
						$singleset->estimated = 0;
					  //retrieve estimated location data and compare the time
						$row = $data->get($singleset->bus_id);

						if (!is_null($row))
						{
								//estimated time from ETA is older than present time, then replace the row with real time
								if ((strtotime($row->time) < strtotime($singleset->time)) and ($row->route_id == $singleset->route_id))
								{
										$row->time = $singleset->time;
										$row->latitude = $singleset->latitude;
										$row->longitude = $singleset->longitude;
										$row->estimated = 0;
										$data->put($row->bus_id, $row);
								}
						}
						else {
								$data->put($singleset->bus_id, $singleset);
						}
			}
			//print(json_encode($data));
			return $data;

	}

	public function getBusCurrentLocations_v2()
	{

			//getcurrent time
			$currentTime = self::getTime();
			//get time two hours ago
			$twoHoursAgo = date("Y-m-d H:i:s", strtotime("-7200 seconds"));

			$data = collect();

			//print(json_encode($data));

			/* Get the bus location reported by drivers or raspberry_pi in real-time if available:
			 subquery to get last record update of location per bus_id

			 select a.bus_id, a.route_id, a.latitude, a.longitude, a.time from location_datav2 as a
			 inner join (
			 			select bus_id, max(time) as time from location_datav2
						where time <= '2020-07-05 21:16:00' and time > '2020-07-05 10:35:00'
						group by bus_id) as b
			 on a.time = b.time
			 group by a.bus_id, a.route_id, a.latitude, a.longitude, a.time;
			*/
			$sub = DB::table('location_datav2 as l')
							->select('bus_id', DB::raw('MAX(time) as time'))
							->where('l.time', '<=', $currentTime)
							->where('l.time', '>', $twoHoursAgo)
							->groupBy('l.bus_id');

			$realBusLocation_Query = DB::table('location_datav2 as l')
																->select('l.bus_id', 'b.plate_no', 'l.route_id', 'l.time', 'l.latitude', 'l.longitude')
																->join('bus as b', 'b.bus_id', '=', 'l.bus_id')
																->join(DB::raw("({$sub->toSql()}) as sub"), 'l.time', '=', 'sub.time')
																->mergeBindings($sub)
																->groupby('l.bus_id', 'l.route_id', 'l.time')
																->get();

			foreach($realBusLocation_Query as $singleset)
			{
						$singleset->estimated = 0;
					  //retrieve estimated location data and compare the time
						$row = $data->get($singleset->bus_id);

						if (!is_null($row))
						{
								//estimated time from ETA is older than present time, then replace the row with real time
								if ((strtotime($row->time) < strtotime($singleset->time)) and ($row->route_id == $singleset->route_id))
								{
										$row->time = $singleset->time;
										$row->latitude = $singleset->latitude;
										$row->longitude = $singleset->longitude;
										$row->estimated = 0;
										$data->put($row->bus_id, $row);
								}
						}
						else {
								$data->put($singleset->bus_id, $singleset);
						}
			}
			//print(json_encode($data));
			return $data;

	}
}
