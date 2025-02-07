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

class APIController extends Controller
{
	public function getDatabaseClass()
	{
		$DatabaseController = new DatabaseController();

		return $DatabaseController;
	}

	public function getTime()
	{
		date_default_timezone_set('Asia/Singapore');
		return date('Y-m-d H:i:s', time());
	}

public function bus_stops_eta_method($route_id)
{
    $route_busstops = self::getBusstopRoute_method($route_id);
    $route_busstops_array = [];

    foreach ($route_busstops as $singleset2) {
        $BusService = self::getBusService_method($singleset2->bus_stop_id);
        $etaList = [];

        foreach ($BusService as $singleset3) {
            $etaList[] = [
                'bus_service_no' => $singleset3['bus_service_no'],
                'eta' => $singleset3['eta']
            ];
        }

        if (empty($etaList)) {
            $etaList = [];
        }

        $dataset_busList = [
            'stop_id' => $singleset2->bus_stop_id,
            'stop_name' => $singleset2->name,
			'latitude' => $singleset2->latitude, // Added Latitude
            'longitude' => $singleset2->longitude, // Added Longitude
            'stop_eta' => $etaList
        ];

        array_push($route_busstops_array, $dataset_busList);
    }
    
    return $route_busstops_array;
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
			$etaList = explode(",", $result->eta);
	
			$processedEtaList = [];
			foreach ($etaList as $eta) {
				$processedEtaList[] = [
					"time" => $eta,
					"relative_time" => self::getRelativeTime($currentTime, strtotime($eta))
				];
			}
	
			$arr[] = [
				'bus_service_no' => $result->bus_service_no ?? null,
				'eta' => $processedEtaList
			];
		}
	
		return $arr;
	}

	function processEta($currentTime, $etas)
	{
		$etaList = explode(",", $etas); // Split the ETA string into individual times
	
		// Initialize etaList to store processed times and relative times
		$etaList = [];
	
		foreach ($etaList as $eta) {
			$etaTime = trim($eta); // Clean any spaces around the ETA time
			if ($etaTime) {
				// Calculate relative time from the current time
				$relativeTime = self::getRelativeTime($currentTime, strtotime($etaTime));
				
				// Store the time and relative time in the etaList array
				$etaList[] = [
					"time" => $etaTime,
					"relative_time" => $relativeTime
				];
			}
		}
	
		return $etaList;
	}
	

	function getRelativeTime($t1, $t2) {
		$timediff = round(($t2-$t1)/60);

		return $timediff;
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

	public function getBusInfoController()
	{
		$BusInfoController = new getBusInfoController();
		return $BusInfoController;

	}

    public function getNearbyBusStop(Request $request)
	{
		$lat = $request->input('lat');
		$lng = $request->input('lng');
	
		// Fetch nearby bus stop data
		$getNearbyBusStop_Query = self::getNearbyBusStop_method($lat, $lng);
	
		if ($getNearbyBusStop_Query->isEmpty()) {
			return response("No nearby bus stop found")->setStatusCode(400);
		}
	
		// Transform the collection
		$data = $getNearbyBusStop_Query->map(function ($busStop) {
			return [
				'bus_stop_id' => $busStop->bus_stop_id,
				'bus_stop_name' => $busStop->name,
				'latitude' => $busStop->latitude,
				'longitude' => $busStop->longitude,
				'distance' => $busStop->distance,
			];
		});
	
		return response()->json($data, 200);
	}

    public function getBusInfo()
	{

		$getBusInfo_Query = DB::table('bus as b')
				    ->select ('b.bus_id', 'plate_no', 'beacon_mac', 'bus_service_no')
						->join('bus_route as br', 'br.bus_id', 'b.bus_id')
						->groupby('b.bus_id', 'plate_no', 'beacon_mac', 'bus_service_no')
				    -> get();


		$data = [];

		foreach($getBusInfo_Query as $singleset)
		{
			$data[]=[
				'bus_id'=> $singleset->bus_id,
				'plate_number'=>$singleset->plate_no,
				'beacon_mac'=>$singleset->beacon_mac,
				'bus_service_number'=>$singleset->bus_service_no,
			];

		}

		return response(json_encode($data), 200);

	}

    public function getBusService_method($bus_stop_id)
	{
		$array_BusService = array();
		$time = self::getTime();

		$bus_service_Query = DB::table('etav2 AS e')
							->select('e.route_id', 'bus_route.bus_service_no', 'route_bus_stop.route_order', 'e.eta', 'e.time')
							->join('bus_route', function ($join) {
								$join->on('bus_route.bus_id', '=', 'e.bus_id')
									->on('bus_route.route_id', '=', 'e.route_id');
							})
							->join('route_bus_stop', function ($join) {
								$join->on('route_bus_stop.route_id', '=', 'e.route_id')
									 ->on('route_bus_stop.bus_stop_id', '=', 'e.bus_stop_id');
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

	public function getBusService($bus_stop_id)
	{
		// Call your method to get bus service information
		$array_BusService = self::getBusService_method($bus_stop_id);

		 // Ensure it's an array instead of stdClass
		 $array_BusService = json_decode(json_encode($array_BusService), true);

	
		// Check if there are bus services
		$formattedData = [];

		foreach ($array_BusService as $service) {
			$formattedData[] = [
				'bus_service_number' => $service['bus_service_no'] ?? null,
				'route_id' => $service['route_id'] ?? null,
				'route_order' => $service['route_order'] ?? null,
				'eta' => isset($service['eta']) ? array_values($service['eta']) : [] // Ensure eta remains an array
			];
		}

		if(!$array_BusService){
			return response()->json(['message' => 'No bus service found'], 400);
		}

		return response()->json($formattedData, 200);
		
	}


    public function getBusLocations(Request $request)
    {
        $busCurrentLocation = array();
        $getDatabaseClass = self::getDatabaseClass();
        $busCurrentLocation = $getDatabaseClass->getBusCurrentLocations();

        $returndata = []; // Initialize an empty array to hold the transformed data

        foreach ($busCurrentLocation as $location) {
          $returndata[] = [
            'bus_id' => $location->bus_id,
            'plate_number' => $location->plate_no,
            'route_id' => $location->route_id,
            'time' => $location->time,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
            'estimated' => $location->estimated,
        ];
        }
		if (!$returndata){
			return response()->json([
				'message' => 'An error occurred returned data is empty.'
			], 400);
		}
        return $returndata;
    }

    public function getBusStop($route_id)
	{
		try {
			// Fetch bus stop information using the provided route_id
			$array_busstop_result = self::getBusStop_method($route_id);

			// Check if bus stops are found
			if (!empty($array_busstop_result)) {
				// Convert the array to a collection and map the results
				$result = collect($array_busstop_result)->map(function ($busStop) {
					return [
						'bus_stop_id' => $busStop->bus_stop_id, 
						'bus_stop_name' => $busStop->name,   
						'latitude' => $busStop->latitude,
						'longitude' => $busStop->longitude,
					];
				});

				return response()->json($result, 200);
			}

			// If no bus stops are found, return a 404 response
			return response("No bus stop found", 404);

		} catch (\Exception $e) {
			// Handle unexpected errors gracefully
			return response()->json([
				'message' => 'An error occurred while fetching bus stops.',
				'error' => $e->getMessage()
			], 400);
		}
	}

	public function getbus_stops_eta($route_id)
	{
		// Call method to fetch bus stop ETA details
		$bus_stops_eta = self::bus_stops_eta_method($route_id);

		// Check if data exists
		if (!$bus_stops_eta) {
			return response()->json(['message' => 'No bus stop data found'], 404);
		}

    // Format the response
    $formattedData = [];

    foreach ($bus_stops_eta as $stop) {
        $formattedData[] = [
            'bus_stop_id' => $stop['stop_id'] ?? null,
            'bus_stop_name' => $stop['stop_name'] ?? null,
			'latitude' => $stop['latitude'] ?? null, // Added Latitude
            'longitude' => $stop['longitude'] ?? null, // Added Longitude
            'eta' => $stop['stop_eta'] ?? []
        ];
    }

    return response()->json($formattedData, 200);
	}

	// public function testgetKM(Request $request)
	// {
	// 				$busserviceno = $request->input('busserviceno');
	// 				$routeno = $request->input('routeno');
	// 				$arg1 = $request->input('arg1');
	// 				$arg2 = $request->input('arg2');

	// 				//13 Aug 2020
	// 				//map the coordinate to a location on the polyline
	// 				$sourcePolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg1), 0.06);
	// 				$destinationPolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg2), 0.06);
	// 				$source = explode(',', $sourcePolyLine);
	// 				$destination = explode(',', $destinationPolyLine);
	// 				$arg1 = $source[0] . "," . $source[1];
	// 				$arg2 = $destination[0] . "," . $destination[1];
	// 				//End

	// 				$totaldistance = self::getDistanceOnRoute($busserviceno, $routeno, $arg1, $arg2);

	// 				return response(json_encode($totaldistance), 200);
	// }

	public function search(Request $request) {
		$query = $request->query('q');

		// Check if query is missing or empty
		if (!$query || trim($query) === '') {
			return response()->json([
				'error' => 'Missing or empty search parameter.'
			], 400);
		}

		// Search for bus stops by name or ID
		$busStops = DB::table('bus_stop')
			->where('name', 'LIKE', "%{$query}%")
			->orWhere('bus_stop_id', 'LIKE', "%{$query}%")
			->select('bus_stop_id', 'name')
			->get();


		$formattedBusStops = [];
		foreach($busStops as $stops){
			$formattedBusStops []=[
			'bus_stop_id' => $stops->bus_stop_id,
			'bus_stop_name' => $stops->name
			];
		}


		// Search for bus services and route order
		// $busServices = DB::table('bus_route')
		// 	->join('route_bus_stop', 'bus_route.route_id', '=', 'route_bus_stop.route_id')
		// 	->where('bus_route.bus_service_no', 'LIKE', "%{$query}%")
		// 	->distinct()
		// 	->select('bus_route.bus_service_no', 'bus_route.route_id', 'route_bus_stop.route_order')
		// 	->orderBy('bus_route.bus_service_no')
		// 	->orderBy('route_bus_stop.route_order')
		// 	->get();
		$busServices = DB::table('bus_route')
			->join('route_bus_stop', 'bus_route.route_id', '=', 'route_bus_stop.route_id')
			->where('bus_route.bus_service_no', 'LIKE', "%{$query}%")
			->distinct()
			->select('bus_route.bus_service_no', 'bus_route.route_id')
			->orderBy('bus_route.bus_service_no')
			->orderBy('route_bus_stop.route_order')
			->get(); 

		

		$formattedBusServices = [];
		foreach($busServices as $services){
			$formattedBusServices []=[
			'bus_service_number' => $services->bus_service_no,
			'route_id' => $services->route_id
			];
		}
	
		return response()->json([
			'bus_stops' => $formattedBusStops ,
			'bus_services' => $formattedBusServices
		], 200);
	}

	public function getScheduleTiming(Request $request)
  {
    // Get inputs from the request (bus_stop_id and route_id)
    $bus_stop_id = $request->input('bus_stop_id');
    $route_id = $request->input('route_id');

    // Validate the input parameters
    if (is_null($bus_stop_id) || is_null($route_id)) {
      return response("Both bus_stop_id and route_id are required.", 400);
    }

    // Query the bus_schedule table to get the schedule for the given bus_stop_id and route_id
    $scheduleQuery = DB::table('bus_schedule')
              ->SELECT ('*')
              ->where('bus_stop_id', $bus_stop_id)
              ->where('route_id', $route_id)
              ->get();

    // Check if any schedule is found
    if ($scheduleQuery->isEmpty()) {
      return response("No schedule found for the given bus stop and route.", 404);
    }

    // Prepare the response data
    $scheduleData = [];
    foreach ($scheduleQuery as $schedule) {
      $scheduleData[] = $schedule->schedule;
    }

    // Return the schedule timings
    return response()->json([
      'bus_stop_id' => $bus_stop_id,
      'route_id' => $route_id,
      'schedules' => $scheduleData
    ], 200);
  }

    public function getTest(){
		$testdata = "test";

		return $testdata;
	}

	
	// This search function returns busstopservices at busstop
	public function getBusStopServices($bus_stop_id) {

		// Find all buses that stop at these bus stops
		$busServices = DB::table('bus_route')
			->join('route_bus_stop', 'bus_route.route_id', '=', 'route_bus_stop.route_id')
			->where('route_bus_stop.bus_stop_id','=', $bus_stop_id)
			->distinct()
			->select('bus_route.bus_service_no', 'bus_route.route_id', 'route_bus_stop.route_order')
			->orderBy('bus_route.bus_service_no')
			->orderBy('route_bus_stop.route_order')
			->get();

		$formattedBusServices = [];

		
		foreach($busServices as $services){
			$formattedBusServices []= [
				"bus_service_number" =>$services->bus_service_no,
            	"route_id"=>$services->route_id,
            	"route_order"=>$services->route_order
			];
		}

		return response()->json([
			
			'bus_services' => $formattedBusServices
		], 200);
	}

	public function getBusStopServicesETA($bus_stop_id) {
		// Find all buses that stop at this bus stop
		$busServices = DB::table('bus_route')
			->join('route_bus_stop', 'bus_route.route_id', '=', 'route_bus_stop.route_id')
			->where('route_bus_stop.bus_stop_id', '=', $bus_stop_id)
			->distinct()
			->select('bus_route.bus_service_no', 'bus_route.route_id', 'route_bus_stop.route_order')
			->orderBy('bus_route.bus_service_no')
			->orderBy('route_bus_stop.route_order')
			->get();
	
		$formattedBusServices = [];
	
		foreach ($busServices as $services) {
			// Fetch the latest ETA for this service at the bus stop
			$etaData = DB::table('etav2')
				->where('bus_stop_id', '=', $bus_stop_id)
				->where('route_id', '=', $services->route_id)
				->select('eta', 'time')
				->orderByDesc('time') // Get the latest record
				->limit(1)
				->get();
	
			$etaList = [];
	
			foreach ($etaData as $eta) {
				$processedETA = $this->calculateEta([$eta]); // Calculate relative time
	
				$etaList[] = [
					"time" => $eta->eta,
					"relative_time" => $processedETA[0]['eta'][0]['relative_time']
				];
			}
	
			// If no ETA data exists, add a default "N/A" entry
			if (empty($etaList)) {
				$etaList = [];
			}
	
			$formattedBusServices[] = [
				"bus_service_number" => $services->bus_service_no,
				"route_id" => $services->route_id,
				"route_order" => $services->route_order,
				"eta" => $etaList // Store only the latest ETA
			];
		}
	
		return response()->json([
			'bus_services' => $formattedBusServices
		], 200);
	}
	


}
