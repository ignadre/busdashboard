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
							->select('e.route_id','bus_route.bus_service_no','route_bus_stop.route_order')
							->selectraw('GROUP_CONCAT(DISTINCT eta) AS eta')
							->join('bus_route', function ($join)
							{
								$join->on('bus_route.bus_id', '=', 'e.bus_id')
									->on('bus_route.route_id','=', 'e.route_id');
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

		// Check if the data is available
		if ($array_BusService !== null) {
			return response()->json($array_BusService, 200);
		} else {
			return response()->json(['message' => 'No bus service found'], 400);
		}
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
			], 500);
		}
	}

    public function getTest(){
		$testdata = "test";

		return $testdata;
	}
}
