<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DatabaseController;
Use App\Http\Controllers\getBusInfoController;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Cache;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
date_default_timezone_set('Asia/Singapore');

class userController extends Controller
{
	public function getDatabaseClass()
	{
		$DatabaseController = new DatabaseController();

		return $DatabaseController;
	}

	//BEGIN 10 Aug 2024
	//Need to call the function from BusInfoController
	public function getBusInfoController()
	{
		$BusInfoController = new getBusInfoController();
		return $BusInfoController;

	}
	//END 10 Aug 2024

	public function setRadius()
	{


		$radius_dataset = [
						'busradius' => 0.06,
						'busstopradius' => 1
						];


		return $radius_dataset;

	}

	public function Ian_updateLocation(Request $request)
	{
		$bus_id = $request->input('bus_id');
		$route_id = $request->input('route_id');
		$imei = $request->input('imei');
		$previouslocation = $request->input('previouslocation');
		$currentlocation = $request->input('currentlocation');
		$timediff = $request->input('timediff');
		$getDatabaseClass = self::getDatabaseClass();

		$bus_service_no = $getDatabaseClass->getBusServiceNo($route_id,$bus_id);

		$busradius = self::setRadius()['busradius'];

		$busstopradius = self::setRadius()['busstopradius'];

		if($bus_service_no != null)
		{
			$currentlocation = explode(',',$currentlocation);
			$newlocation = self::Ian_closepointonroute($bus_service_no,$route_id,$currentlocation,$busradius);

			if($newlocation != null)
			{
				$previouslocation = explode(',',$previouslocation);
				$newlocation1 = self::Ian_closepointonroute($bus_service_no,$route_id,$previouslocation,$busradius);

				if ($newlocation1 !=null)
				{
					if($previouslocation == $currentlocation)
					{
						$distance = 0;
					}
					else
					{
						$distance = self::Ian_distancebetweenpointsonroute($bus_service_no,$route_id,$previouslocation,$currentlocation);
					}

					if($distance == 0 || $timediff == 0)
					{
						$speedkmhr = 0;
					}
					else
					{
						$speedkmhr = $distance / $timediff;
					}

					$newlocation = explode(',',$newlocation);
					$getDatabaseClass->insertLocationData($bus_id, $route_id, $imei, $newlocation[0], $newlocation[1], $speedkmhr);
					$getDatabaseClass->insertLocationDataV2($bus_id, $route_id, $imei, $newlocation[0], $newlocation[1], $speedkmhr);

					return response("Sucessfully Upload")->setStatusCode(200);

				}
			}

			else
			{
				return response("Current location not on polyline")->setStatusCode(400);
			}

		}

		else
		{
			return response("Bus not found")->setStatusCode(400);

		}
	}

	public function firstRecordUpdate($busroutecoords,$nextBusStop,$busroutecoordsUserPosition,$routeID,$bus_id,$routeNo,$spdDate)
	{
		$keepTime = 0;
		$caltotaldistance =0;

		print($routeNo);

		$location_data = retrieveLocationData($routeNo,$bus_id,$spdDate,date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($spdDate))));
		$bus_service_no = getBusServiceNo($routeNo,$bus_id);
		$speed = self::calculateAverageSpeed($bus_service_no, $routeNo, $location_data);

		for($z = $busroutecoordsUserPosition; $z <sizeof($busroutecoords); $z++)
		{
			$busstop1 = explode(",", trim($busroutecoords[$z]));
			$busstop2 = explode(",", trim($busroutecoords[$z+1]));
			$caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);

			if(trim($busroutecoords[$z+1]) == trim($nextBusStop))
			{
				$time = $caltotaldistance /$speed;
				$time = $time * 3600;
				$keepTime = $time;
				$time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));

				$getDatabaseClass->uploadETAV2($bus_id,$routeNo,$routeID+$nextBusStop,$time,date('Y-m-d H:i:s', time()),$speed);
				return $keepTime;
			}
		}
	}

	public function calculateETATest(Request $request)
	{


		$getDatabaseClass = self::getDatabaseClass();
		$totalbus = $getDatabaseClass->getTotalBus();

		for($q = 0; $q <sizeof($totalbus); $q++)
		{
			$routeNo = $getDatabaseClass->getRouteID($totalbus[$q]);

			for($g = 0;  $g <sizeof($routeNo); $g++)
			{
				$routeID = $getDatabaseClass->getFirstBusstopIDFromRoute($totalbus[$q],$routeNo[$g]);

				$data = self::getFurthestRecordV2($totalbus[$q],$routeNo[$g]);

				if(!empty($data))
				{

					//BEGIN 20th MAY 2024
					//Use Global variable to read the polyline content once only
					//$filecontent = file_get_contents('../data/'.$data->bus_service_no.'.json');
					$globalVariable = view()->shared('bus_polyline');
					$filecontent = $globalVariable[$data->bus_service_no];
					//end 20th MAY 2024


					$json1 = json_decode($filecontent, true);
					$busroutecoords = $json1[$routeNo[$g]]['route'];

					$busstop = self::getRoute($routeNo[$g],1);
					$check = false;

                                                                                                 $userUploadData = array_search(trim($data->latitude.",".$data->longitude),$busroutecoords);
                                                                                                 $fifthBusstop = array_search(trim($busstop[4]),$busroutecoords);
                                                                                                 $checkHistoryExist = $getDatabaseClass->checkHistoryExistV2($routeNo[$g],$data->bus_id);

                                                                                                 if($data->flag == 0)
                                                                                                 {
                                                                                                           $location_data = $getDatabaseClass->retrieveLocationData($routeNo[$g],$data->bus_id,$data->time,date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($data->time))));
                                                                                                           $bus_service_no = $getDatabaseClass->getBusServiceNo($routeNo[$g],$data->bus_id);

                                                                                                           $speed = self::calculateAverageSpeed($bus_service_no, $routeNo[$g], $location_data);

                                                                                                           $totaldistance = array();

                                                                                                           $busstopKM = self::getRoute($routeNo[$g],2);
                                                                                                           $point[0] = $data->latitude;
                                                                                                           $point[1] = $data->longitude;
                                                                                                           $continue = 1;
                                                                                                           $caltotaldistance = 0;

                                                                                                           $distances = array_map(function($item) use($point)
                                                                                                                                                            {
                                                                                                                                                                     $item = explode(',',$item);
                                                                                                                                                                     return self::caldistance($item, $point);
                                                                                                                                                            }, $busroutecoords);
                                                                                                           asort($distances);



                                                                                                           for ($i=0; $i<sizeof($busroutecoords); $i++)
                                                                                                           {
                                                                                                                    if (trim($busroutecoords[key($distances)]) == trim($busroutecoords[$i]))
                                                                                                                    {

                                                                                                                               $uploadedlocation = $i;
                                                                                                                    }
                                                                                                           }

                                                                                                           for ($z=$uploadedlocation; $z<sizeof($busroutecoords); $z++)
                                                                                                           {
                                                                                                                    if ($z+1 < sizeof($busroutecoords))
                                                                                                                    {
                                                                                                                              $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                                              $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                                                                                              $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);


                                                                                                                              for ($x = 0;$x<sizeof($busstop); $x++)
                                                                                                                              {
                                                                                                                                        $counter =1;

                                                                                                                                        if(trim($busstop[$x])==trim($busroutecoords[$z]))
                                                                                                                                        {
                                                                                                                                                  $continue = 0;

                                                                                                                                                  if ($speed == -2)
                                                                                                                                                  {
                                                                                                                                                            break;
                                                                                                                                                  }

                                                                                                                                                  else if ($speed == -1)
                                                                                                                                                  {
                                                                                                                                                           $getDatabaseClass->getHistoryETA($data->bus_id,$routeNo[$g],$data->bus_service_no,$x+$routeID-1, 0);
                                                                                                                                                           break;
                                                                                                                                                  }

                                                                                                                                                  else if ($speed > 4)
                                                                                                                                                  {
                                                                                                                                                            $time = $caltotaldistance / $speed;
                                                                                                                                                            $time = $time * 3600;

                                                                                                                                                            $time = date("Y-m-d H:i:s", $time +strtotime("-15 seconds"));
                                                                                                                                                            $hi1=$x+$routeID;
                                                                                                                                                            $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$hi1,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                                                  }

                                                                                                                                                  $keeptime = 0;

                                                                                                                                                  for ($a = $x; $a<sizeof($busstopKM); $a++)
                                                                                                                                                  {
                                                                                                                                                            $counter++;

                                                                                                                                                            if ($counter < 4)
                                                                                                                                                            {

																																									 $caltotaldistance = $caltotaldistance + (float)$busstopKM[$a];
																																									 $hi = $a+$routeID+1;
                                                                                                                                                                     $time = $caltotaldistance / $speed;
                                                                                                                                                                     $time = $time * 3600;
                                                                                                                                                                     $ETA = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));

                                                                                                                                                                     $keeptime = $time;
                                                                                                                                                                     $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$hi,$ETA,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                                                            }

                                                                                                                                                            else
                                                                                                                                                            {
                                                                                                                                                                     $hi = $a+$routeID;

                                                                                                                                                                     $getDatabaseClass->getHistoryETA($data->bus_id,$routeNo[$g],$data->bus_service_no,$hi,$keeptime);
                                                                                                                                                                     break;
                                                                                                                                                            }
                                                                                                                                                  }

                                                                                                                                                  break;
                                                                                                                                        }
                                                                                                                              }

                                                                                                                              if($continue ==0)
                                                                                                                              {
                                                                                                                                  break;
                                                                                                                              }
                                                                                                                    }

                                                                                                                    else
                                                                                                                     {
                                                                                                                              $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                                              $caltotaldistance = $caltotaldistance + self::caldistance($point,$busstop1);
                                                                                                                              $time = $caltotaldistance / $speed;
                                                                                                                              $time = $time * 3600;
                                                                                                                              $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
                                                                                                                              $hi1=$routeID+sizeof($busstopKM);

                                                                                                                              $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$hi1,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                     }
                                                                                                           }
                                                                                                 }

                                                                                                 $getDatabaseClass->updateFlagV2(1,$data->bus_id,$data->route_id,$data->time);
				}


			}
		}
	}
					public function getETA_schedule(Request $request)
					{
						$getDatabaseClass = self::getDatabaseClass();
						$bus_id = $request->input('bus_id');
						$route_id = $request->input('route_id');
						$bus_stop_id = $request->input('bus_stop_id');
						$currtime = $getDatabaseClass->getTime();
						$getETAschedule_Query = DB::table('eta')
											->where('bus_id',$bus_id)
											->where('route_id',$route_id)
											->where('bus_stop_id',$bus_stop_id)
											->where('eta', '>', $currtime)
                                                                            ->get();

                             $i = 0;

                             foreach($getETAschedule_Query as $singleset)
                             {

									   print_r("Bus ID : ".$singleset->bus_id." Bus Stop ID : ".$singleset->bus_stop_id." ETA : ".$singleset->eta."\n");
                                       $i++;
                             }

                             print("Total Records : ".$i);
					}
                    /* public function calculateETA()
                    {
                             //date_default_timezone_set('Asia/Singapore');
                             $getDatabaseClass = self::getDatabaseClass();

                             $totalbus = $getDatabaseClass->getTotalBus();

                             for ($q = 0; $q <sizeof($totalbus); $q++)
                             {
                                       $routeNo = $getDatabaseClass->getRouteID($totalbus[$q]);

                                       for ($g = 0;  $g <sizeof($routeNo); $g++)
                                       {
                                                 //$routeID = $getDatabaseClass->getFirstBusstopIDFromRoute($totalbus[$q],$routeNo[$g]);
                                               //  print(sizeof($routeNo));
                                                 $routeOrder = 1;
												 $data = self::getFurthestRecord($totalbus[$q],$routeNo[$g]);

                                                // print_r($data);
                                                  var_dump($data);
                                                 if (!empty($data))
                                                 {
                                                          if($data->flag == 0 && $data->bus_service_no > 0)
                                                          {
                                                                    $speed = $getDatabaseClass->avgSpeed($routeNo[$g],$data->bus_id,$data->time,date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($data->time))));
                                                                    $totaldistance = array();
                                                                    $busstop = self::getRoute($routeNo[$g],1);
                                                                    $busstopKM = self::getRoute($routeNo[$g],2);

                                                                    $filecontent = file_get_contents('../data/'.$data->bus_service_no.'.json');
                                                                    $json1 = json_decode($filecontent, true);
                                                                    $busroutecoords = $json1[$routeNo[$g]]['route'];

                                                                    $point[0] = $data->latitude;
                                                                    $point[1] = $data->longitude;

                                                                    $distances = array_map(function($item) use($point)
                                                                                                                    {
                                                                                                                              $item = explode(',',$item);
                                                                                                                              return self::caldistance($item, $point);
                                                                                                                    },        $busroutecoords);

                                                                    asort($distances);
                                                                    $continue =1;
                                                                    $uploadedlocation = -1;
                                                                    $caltotaldistance = 0;
                                                                    var_dump($distances);
                                                                    if(array_values($distances)[0] < 0.06)
                                                                    {
																		var_dump("success");
                                                                              for ($i=0; $i<sizeof($busroutecoords); $i++)
                                                                              {

                                                                                        if (trim($busroutecoords[key($distances)]) == trim($busroutecoords[$i]))
                                                                                        {
                                                                                                 $uploadedlocation = $i;
                                                                                        }
                                                                              }

                                                                              for ($z=$uploadedlocation; $z<sizeof($busroutecoords); $z++)
                                                                              {
                                                                                        if ($z+1 < sizeof($busroutecoords))
                                                                                       {
                                                                                                 $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                 $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                                                                 $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);

                                                                                                 for ($x = 0;$x<sizeof($busstop); $x++)
                                                                                                 {
                                                                                                           if(trim($busstop[$x])==trim($busroutecoords[$z]))
                                                                                                           {
                                                                                                                     print("busstop ".sizeof($busstop)."\r\n");
                                                                                                                     $continue = 0;

                                                                                                                     $time = $caltotaldistance / $speed;
                                                                                                                     $time = $time * 3600;

                                                                                                                     print("Speed ".$speed);

                                                                                                                     $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));


                                                                                                                     //$hi1=$x+$routeID;
																													 $hi1=$x+$routeOrder;
																													 $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi1,$routeNo[$g]);

                                                                                                                     $getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$bus_stop_id,$time,date('Y-m-d H:i:s', time()),$speed);

                                                                                                                     for ($a = $x; $a<sizeof($busstopKM); $a++)
                                                                                                                     {
                                                                                                                              $caltotaldistance = $caltotaldistance+ (float)$busstopKM[$a];
                                                                                                                              //$hi = $a+$routeID+1;
																															  $hi = $a+$routeOrder+1;
																															  $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi,$routeNo[$g]);
                                                                                                                              $time = $caltotaldistance / $speed;
                                                                                                                              $time = $time * 3600;
                                                                                                                              $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));

                                                                                                                              $getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$bus_stop_id,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                     }

                                                                                                                     break;
                                                                                                           }
                                                                                                 }

                                                                                                 if($continue ==0)
                                                                                                 {
                                                                                                           break;
                                                                                                 }
                                                                                       }

                                                                                       else
                                                                                       {
                                                                                                 $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                 $caltotaldistance = $caltotaldistance + self::caldistance($point,$busstop1);
                                                                                                 $time = $caltotaldistance / $speed;
                                                                                                 $time = $time * 3600;
                                                                                                 $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));

                                                                                                 //$hi1=$routeID+sizeof($busstopKM);
																								 $hi1=$routeOrder+sizeof($busstopKM);
                                                                                                 $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi1,$routeNo[$g]);
                                                                                                 $getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$bus_stop_id,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                       }
                                                                              }
                                                                    }

                                                                    $getDatabaseClass->updateFlag(1,$data->bus_id,$data->route_id,$data->time);
                                                          }
                                                 }
                                       }
                             }
                    }
                     */
	public function calculateETA()
	{

		$getDatabaseClass = self::getDatabaseClass();
		$totalbus = $getDatabaseClass->getTotalBus();
    //$ML_TRAINED_SERVICES = explode(',', 'P411,P211');
		$ML_TRAINED_SERVICES = explode(',', getenv('ML_TRAINED_SERVICES'));
    $ML_timeout = Cache::get('ml_timeout');
    
		for($q = 0; $q <sizeof($totalbus); $q++)
		{
			//print($totalbus[$q]);
			$routeNo = $getDatabaseClass->getRouteID($totalbus[$q]);
			for($g = 0;  $g <sizeof($routeNo); $g++)
			{
				$routeOrder = 1;
				$data = self::getFurthestRecordV2($totalbus[$q],$routeNo[$g]);
				//print($totalbus[$q]);
				//print(" -- ");
				//print($routeNo[$g]);
				//print("\r\n");
				if(!empty($data))
				{

					//BEGIN 20th MAY 2024
					//Change the reading of polyline using Global variable, such that the polyline is only read once, not everytime
					$globalVariable = view()->shared('bus_polyline');
					$filecontent = $globalVariable[$data->bus_service_no];
					//$filecontent = file_get_contents('../data/'.$data->bus_service_no.'.json');
					//END 20th MAY 2024
					$json1 = json_decode($filecontent, true);
					$busroutecoords = $json1[$routeNo[$g]]['route'];

					$busstop = self::getRoute($routeNo[$g],1);

          if(in_array($data->bus_service_no, $ML_TRAINED_SERVICES) && is_null($ML_timeout))
          {
            self::updateETAwithML($data, $routeNo[$g], $busstop);
          }
					$check = false;

                                                                                                 $userUploadData = array_search(trim($data->latitude.",".$data->longitude),$busroutecoords);
                                                                                                 $fifthBusstop = array_search(trim($busstop[4]),$busroutecoords);
                                                                                                 $checkHistoryExist = $getDatabaseClass->checkHistoryExistV2($routeNo[$g],$data->bus_id);

                                                                                                 if($data->flag == 0)
                                                                                                 {
                                                                                                           $location_data = $getDatabaseClass->retrieveLocationDataV2($routeNo[$g],$data->bus_id,$data->time,date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($data->time))));
                                                                                                           $bus_service_no = $getDatabaseClass->getBusServiceNo($routeNo[$g],$data->bus_id);
                                                                                                           $speed = self::calculateAverageSpeed($bus_service_no, $routeNo[$g], $location_data);

                                                                                                           $totaldistance = array();

                                                                                                           $busstopKM = self::getRoute($routeNo[$g],2);
                                                                                                           $point[0] = $data->latitude;
                                                                                                           $point[1] = $data->longitude;
                                                                                                           $continue = 1;
                                                                                                           $caltotaldistance = 0;

                                                                                                           $distances = array_map(function($item) use($point)
                                                                                                                                                            {
                                                                                                                                                                     $item = explode(',',$item);
                                                                                                                                                                     return self::caldistance($item, $point);
                                                                                                                                                            }, $busroutecoords);
                                                                                                           asort($distances);



                                                                                                           for ($i=0; $i<sizeof($busroutecoords); $i++)
                                                                                                           {
                                                                                                                    if (trim($busroutecoords[key($distances)]) == trim($busroutecoords[$i]))
                                                                                                                    {

                                                                                                                               $uploadedlocation = $i;
                                                                                                                    }
                                                                                                           }

                                                                                                           for ($z=$uploadedlocation; $z<sizeof($busroutecoords); $z++)
                                                                                                           {
                                                                                                                    if ($z+1 < sizeof($busroutecoords))
                                                                                                                    {
                                                                                                                              $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                                              $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                                                                                              $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);


                                                                                                                              for ($x = 0;$x<sizeof($busstop); $x++)
                                                                                                                              {
                                                                                                                                        $counter =1;

                                                                                                                                        if(trim($busstop[$x])==trim($busroutecoords[$z]))
                                                                                                                                        {
                                                                                                                                                  $continue = 0;

                                                                                                                                                  if ($speed == -2)
                                                                                                                                                  {
                                                                                                                                                            break;
                                                                                                                                                  }

                                                                                                                                                  else if ($speed == -1)
                                                                                                                                                  {
																																							$routeNum = $x+$routeOrder+1;
																																						   $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($routeNum,$routeNo[$g]);
                                                                                                                                                           print($bus_stop_id);
																																						   $getDatabaseClass->getHistoryETA($data->bus_id,$routeNo[$g],$data->bus_service_no,$bus_stop_id, 0);
                                                                                                                                                           break;
                                                                                                                                                  }

                                                                                                                                                  else if ($speed > 4)
                                                                                                                                                  {
                                                                                                                                                            $time = $caltotaldistance / $speed;
                                                                                                                                                            $time = $time * 3600;

                                                                                                                                                            $time = date("Y-m-d H:i:s", $time +strtotime("-15 seconds"));

                                                                                                                                                            //$hi1=$x+$routeID;
																																																																														$hi1=$x+$routeOrder;
																																																																														$bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi1,$routeNo[$g]);

                                                                                                                                                            $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$bus_stop_id,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                                                  }

                                                                                                                                                  $keeptime = 0;

                                                                                                                                                  for ($a = $x; $a<sizeof($busstopKM); $a++)
                                                                                                                                                  {
                                                                                                                                                            $counter++;

																																																																														//calculate the ETA for the next 5 stops based on the speed.
																																																																														//BEGIN 2 JUNE 2024
																																																																														//change the code to use only the historical data, and the first stop using speed
                                                                                                                                                            if ($counter < 2)
                                                                                                                                                            {

																																									 																																									 $caltotaldistance = $caltotaldistance + (float)$busstopKM[$a];
																																																																																		 //$hi = $a+$routeID+1;
																																																																																		 $hi = $a+$routeOrder+1;
																																																																																	   $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi,$routeNo[$g]);
							                                                                                                                                                       $time = $caltotaldistance / $speed;
                                                                                                                                                                     $time = $time * 3600;
																																																																																		 // $sim_date = mktime(18, 12, 00, 2, 7, 2018);
                                                                                                                                                                     $ETA = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
                                                                                                                                                                     //$ETA = date("Y-m-d H:i:s", $time +$sim_date);

                                                                                                                                                                     $keeptime = $time;
                                                                                                                                                                     $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$bus_stop_id,$ETA,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                                                                     //$getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$bus_stop_id,$ETA,date('Y-m-d H:i:s', $sim_date),$speed);
                                                                                                                                                            }
																																																																														//END 2 JUNE 2024
                                                                                                                                                            else
                                                                                                                                                            {
                                                                                                                                                                     //$hi = $a+$routeID;
                                                                                                                                                                     $hi = $a+$routeOrder+1;
																																									 																																								   $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi,$routeNo[$g]);

                                                                                                                                                                     $getDatabaseClass->getHistoryETA($data->bus_id,$routeNo[$g],$data->bus_service_no,$bus_stop_id,$keeptime);
                                                                                                                                                                     break;
                                                                                                                                                            }
                                                                                                                                                  }

                                                                                                                                                  break;
                                                                                                                                        }
                                                                                                                              }

                                                                                                                              if($continue ==0)
                                                                                                                              {
                                                                                                                                  break;
                                                                                                                              }
                                                                                                                    }

                                                                                                                    else
                                                                                                                     {
                                                                                                                              $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                                              $caltotaldistance = $caltotaldistance + self::caldistance($point,$busstop1);
                                                                                                                              $time = $caltotaldistance / $speed;
                                                                                                                              $time = $time * 3600;
                                                                                                                              $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
                                                                                                                              //$hi1=$routeID+sizeof($busstopKM);
                                                                                                                              $hi1=$routeOrder+sizeof($busstopKM);
																															  $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($hi1,$routeNo[$g]);
                                                                                                                              $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo[$g],$bus_stop_id,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                     }
                                                                                                           }
                                                                                                 }

                                                                                                 $getDatabaseClass->updateFlagV2(1,$data->bus_id,$data->route_id,$data->time);
				}


			}
		}
	}

  private function updateETAwithML(&$data, $routeNo, $busstop)
	{
    try {
      if($data->flag == 0) {
        $getDatabaseClass = self::getDatabaseClass();

        // arranging the array to get the closet bus stop to the provided coordinates
        $point[0] = $data->latitude;
        $point[1] = $data->longitude;
        $distances = array_map(function($item) use($point)
        {
          $item = explode(',',trim($item));
          return self::caldistance($item, $point);
        }, $busstop);

        asort($distances);

        $idx = key($distances);

        if($distances[$idx] > 0.1) {
          print("updateETAwithML() WARNING: detaction point is too far from any bus stop");
          return;
        }

        // get the closet bus stop id
        $bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($idx + 1, $routeNo); // + 1 as the route order starts from 1

        // get the remainding bus stop id on the route
        $remainingStops = array();
        for ($x = $idx + 1; $x < sizeof($busstop); ++$x)
        {
          $temp_bus_stop_id = $getDatabaseClass->getbusstopid_byroute_order($x + 1, $routeNo);
          array_push($remainingStops, $temp_bus_stop_id);
        }

        // http post request to ml prediction
        #$client = new Client();
        //$URL = "https://p411johor-0ea50c701576.herokuapp.com/predictArivalTime";
				$URL = getenv('ML_PREDICT_API_URL');


				// $response = file_get_contents($URL, false, stream_context_create(['http' => ['header' => 'api-key: ' . $api_key]]));
				// $fleet = json_decode($response, true);
        // $response = $client->post($URL, [
        //   'json' => [
        //     'bus_service' => $data->bus_service_no,
        //     'from_stop_id' => $bus_stop_id,
        //     'arival_datetime' => $data->time,
        //     'stops_to_predict' => $remainingStops
        //   ],
        //   'http_errors' => false,
        //   'timeout' => 10,  // Set the timeout to 10 seconds
        // ]);
				$params = [
    			'bus_service' => $data->bus_service_no,
    			'from_stop_id' => $bus_stop_id,
    			'arival_datetime' => $data->time,
    			'stops_to_predict' => $remainingStops,
				];

				// Encode the data to JSON
				$options = [
				    'http' => [
				        'header'  => "Content-type: application/json\r\n",
				        'method'  => 'POST',
				        'content' => json_encode($params),
				        'timeout' => 10,  // Set the timeout to 10 seconds
				    ],
				];

				// Create the stream context
				$context  = stream_context_create($options);

				// Make the POST request
				$response = file_get_contents($URL, false, $context);

				if ($response === FALSE) {
    			// Handle error
    			// You can add your error handling code here
					Log::warning('Request made in function updateETAwithML() failed with NULL response');
				}
				else {
						$body = json_decode($response, true);

		        //$statusCode = $response->getStatusCode();
		        //$body = json_decode($response->getBody(), true);
		        //if ($statusCode == 200) {
		          $results = $body['data'];
		          $dateTime = Carbon::parse($data->time);
		          // update ETA for each of the folowing bus stops on the route
		          foreach($results as $row)
		          {
		            $sec = $row['sec_taken'];
		            $dateTime->addSeconds($sec + 30); // add 30sec for each stop - as the bus dwell time
		            $eta = $dateTime->format("Y-m-d H:i:s");
				  	    $avgspeed = -1;
		            $get_Time = $getDatabaseClass->getTime();
		            $getDatabaseClass->uploadETAV2($data->bus_id,$routeNo,$row['stop_id'],$eta,$get_Time,$avgspeed);
		          }
		          // update the location data flag to signify sucess
		          $getDatabaseClass->updateFlagV2(1,$data->bus_id,$data->route_id,$data->time);
		          $data->flag = true;
		        //} else {
		        //  Log::warning('Request made in function updateETAwithML() failed with response: ', ['response' => $body]);
		        //}
				}
      }
    }
    catch(\Exception $e) {
      if(strpos($e->getMessage(), "Connection")) {
        Cache::put('ml_timeout', 'true', now()->addMinutes(15)); // add a tracker such that function will not be called for 15 mins when timeout occurs
      }
      Log::warning($e);
    }
  }


                    public function Ian_distancebetweenpointsonroute($busserviceno,$routeno,$point1,$point2)
                    {
							 $busradius = self::setRadius()['busradius'];
                             $pointA = self::closepointonroute($busserviceno,$routeno,$point1,$busradius);
                             $pointB = self::closepointonroute($busserviceno,$routeno,$point2,$busradius);

                             if($pointA == null || $pointB == null || $pointA == $pointB )
                             {
                                       return 0;
                             }

                             $pointALocation = explode(',',$pointA)[2];
                             $pointBLocation = explode(',',$pointB)[2];

                             if($pointALocation>$pointBLocation)
                             {
                                       return 0;
                             }

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
                             //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
									 					 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

                             $json = json_decode($filecontent, true);
                             $busroutecoords = $json[$routeno]['route'];

                             $totaldistance = floatval(0);

                             if($pointBLocation>$pointALocation)
                             {
                                       for($i = $pointALocation; $i < $pointBLocation;$i++ )
                                       {
                                                 $a = explode(',',$busroutecoords[$i]);
                                                 $b = explode(',',$busroutecoords[$i+1]);
                                                 $totaldistance = $totaldistance + self::caldistance($a,$b);
                                       }
                             }

                             return $totaldistance;
                    }

                    public function Ian_closepointonroute($busserviceno,$routeno,$point,$radius)
                    {

                             //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
                             //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 //echo "\n*****". $busserviceno . "*****\n";
														 $globalVariable = view()->shared('bus_polyline');
									 					 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024
                             $json = json_decode($filecontent, true);
                             $busroutecoords = $json[$routeno]['route'];

                             $distances = array_map(function($item) use($point)
                                                                             {
                                                                                       $item = explode(',',$item);
                                                                                       return self::caldistance($item, $point);
                                                                             },        $busroutecoords);
                             asort($distances);
                             if(array_values($distances)[0] < $radius)
                             {
                                       return $busroutecoords[key($distances)] . ',' . key($distances);
                             }

                             else
                             {
                                       return null;
                             }
                    }

					public function pi_getBusRouteNo_newlocation($bus_id,$routeID,$point)
					{

						$radius = self::setRadius()['busradius'];
						$getDatabaseClass = self::getDatabaseClass();
						foreach($routeID as $route_id)
						{
							$busserviceno = $getDatabaseClass->getBusServiceNo($route_id,$bus_id);
							$latlong = explode(',', $point);
							$result = self::Ian_closepointonroute($busserviceno,$route_id,$latlong,$radius);



							if($result !=null)
							{
									$dataset_pi_getBusRouteNo_newlocation = [
								'route_id' => $route_id,
								'newlocation' => $result
								];



								return $dataset_pi_getBusRouteNo_newlocation;
							}
						}

						return null;
					}

                    public function viewETATable()
                    {
                             $viewETATable_Query = DB::table('etav2')
                                                                            ->get();

                             $i = 0;

                             foreach($viewETATable_Query as $singleset)
                             {
                                       print_r($singleset);
                                       $i++;
                             }

                             print("Total Records : ".$i);
                    }

					public function viewETATableGet()
                    {
                             $viewETATable_Query = DB::table('etav2')
                                                                            ->get();

                             $i = 0;

                             foreach($viewETATable_Query as $singleset)
                             {
                                       print_r($singleset);
                                       $i++;
                             }

                             print("Total Records : ".$i);
                    }

                    public function ianTest(Request $request)
                    {
                             $bus_id = $request->input('bus_id');
                             $route_id = $request->input('route_id');
                             $imei = $request->input('imei');
                             $latlong = explode(',', $request->input('latlong'));
                             $speed = $request->input('speed');
                             $time = date('Y-m-d H:i:s', time());

                             print($speed);

                             $insertlocation_datav2_Query = DB::table('location_datav2')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $imei,
                                                                                                 'latitude' => $latlong[0],
                                                                                                 'longitude' => $latlong[1] ,
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);

                             $insertlocation_datav_Query = DB::table('location_data')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $imei,
                                                                                                 'latitude' => $latlong[0],
                                                                                                 'longitude' => $latlong[1] ,
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);


                    }

					//BEGIN 10th AUG 2024
					//Decouple the insertLocation function from the HTTP request
				  private function insertLocation($bus_id, $route_id, $imei, $speed, $time, $latlong)
					{
								$getDatabaseClass = self::getDatabaseClass();
								//if use simulator
								if($time == null)
								{
						 				$time = $getDatabaseClass->getTime();
								}

								$bus_service_no = $getDatabaseClass->getBusServiceNo($route_id,$bus_id);
								$busradius = self::setRadius()['busradius'];
								$newlocation = self::Ian_closepointonroute($bus_service_no,$route_id,$latlong,$busradius);
								if($newlocation != null)
								{
										print($speed."\r\n");
										print("<br>");
										print("insert success \r\n");
										print("<br>");
										print("time : ".$time);
										print("<br>");
										$newlocation = explode(',',$newlocation);

										print($newlocation[0].",".$newlocation[1]);
										$insertlocation_datav2_Query = DB::table('location_datav2')
																 ->insert([
																	'bus_id' => $bus_id,
																	'route_id' => $route_id,
																	'imei' => $imei,
																	'latitude' => $newlocation[0],
																	'longitude' => $newlocation[1],
																	'speed' => $speed ,
																	'time' => $time
										]);

										$insertlocation_datav_Query = DB::table('location_data')
																 ->insert([
																	'bus_id' => $bus_id,
																	'route_id' => $route_id,
										 						  'imei' => $imei,
																	'latitude' => $newlocation[0],
																	'longitude' => $newlocation[1],
																	'speed' => $speed ,
																	'time' => $time
										]);
								 		//self::calculateETA();
							  }
								else
								{
										print("Too far from route");
										return 0;
								}
								return 1;
					}
					//END 10 Aug 2024

					public function bus_insertlocation(Request $request)
                    {
                             $bus_id = $request->input('bus_id');
                             $route_id = $request->input('route_id');
                             $imei = $request->input('imei');
                             $latlong = explode(',', $request->input('latlong'));
                             $speed = $request->input('speed');
							 $time = $request->input('date');
							 $getDatabaseClass = self::getDatabaseClass();
							 //if use simulator
							 if($time == null)
							 {
								$time = $getDatabaseClass->getTime();
							 }

							 $bus_service_no = $getDatabaseClass->getBusServiceNo($route_id,$bus_id);
                             $busradius = self::setRadius()['busradius'];
							 $newlocation = self::Ian_closepointonroute($bus_service_no,$route_id,$latlong,$busradius);
							 if($newlocation != null)
							 {
							 print($speed."\r\n");
							 print("<br>");
               print("insert success \r\n");
							 print("<br>");
							 print("time : ".$time);
							 print("<br>");
							 $newlocation = explode(',',$newlocation);

							 print($newlocation[0].",".$newlocation[1]);
                             $insertlocation_datav2_Query = DB::table('location_datav2')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $imei,
                                                                                                 'latitude' => $newlocation[0],
                                                                                                 'longitude' => $newlocation[1],
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);

                             $insertlocation_datav_Query = DB::table('location_data')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $imei,
                                                                                                 'latitude' => $newlocation[0],
                                                                                                 'longitude' => $newlocation[1],
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);

										//self::calculateETA();
                             }
							 else
							 {
								 print("Too far from route");
							 }

                    }

					public function pi_insertlocation(Request $request)
                    {

						$beacon_mac = $request->input('beacon_mac');
						$getDatabaseClass = self::getDatabaseClass();

						if($beacon_mac != null)
						{
							$bus_id = $getDatabaseClass->getBusIDByBeacon($beacon_mac);
						}
						else
						{
							$bus_id = null;
						}

						if($bus_id != null)
						{

                             $pi_id = $request->input('pi_id');


							 $getlatlong = $getDatabaseClass->getlatlongByPi($pi_id);
							 $lat = $getlatlong->latitude;
							 $long = $getlatlong->longitude;
							 $latlong = $lat.','.$long;
							 $latlong = explode(',', $latlong);
							 //$getpi_getBusRouteNo_newlocation = self::pi_getBusRouteNo_newlocation($bus_id,$routeID,$latlong);
							 $speed = 10.0;
							 $time = $getDatabaseClass->getTime();
							 //$route_id = $getpi_getBusRouteNo_newlocation['route_id'];
							 $route_id_pi = $getDatabaseClass->getpi_routeid($pi_id);
							 $routeID = $getDatabaseClass->getRouteID($bus_id);
							 $route_id = null;
							 foreach($routeID as $rid)
							 {
								 foreach($route_id_pi as $rid_pi)
								 if($rid == $rid_pi)
								 {
									 $route_id = $rid;
								 }
							 }


							 if($route_id != null)
							 {
								 $bus_service_no = $getDatabaseClass->getBusServiceNo($route_id,$bus_id);

								 if($bus_service_no != null)
								 {


									 $busradius = self::setRadius()['busradius'];

									 $newlocation = self::Ian_closepointonroute($bus_service_no,$route_id,$latlong,$busradius);

									 if($newlocation != null)
									 {
										 $newlocation = explode(',',$newlocation);

										 print("insert success \n");
										 $insertlocation_datav2_Query = DB::table('location_datav2')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $beacon_mac,
                                                                                                 'latitude' => $newlocation[0],
                                                                                                 'longitude' => $newlocation[1],
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);

										 $insertlocation_datav_Query = DB::table('location_data')
                                                                                        ->insert([
                                                                                                 'bus_id' => $bus_id,
                                                                                                 'route_id' => $route_id,
                                                                                                 'imei' => $beacon_mac,
                                                                                                 'latitude' => $newlocation[0],
                                                                                                 'longitude' => $newlocation[1],
                                                                                                 'speed' => $speed ,
                                                                                                 'time' => $time
                                                                                        ]);

										 print("Bus No : ".$bus_service_no." of route : ".$route_id." detected"." at : ".$time);
									 }
								 }
                             }
							 else
							 {
								 print("no route found");
							 }

						}
						else
						{
							print("No Bus Found");
						}

                    }

						public function removephantomETA(Request $request )
						{
							$bus_id = $request->input('bus_id');
							//print($request->input('bus_id'));
							$getDatabaseClass = self::getDatabaseClass();
							$delete_stats = $getDatabaseClass->rmPhantomETA($bus_id);

							//return $bus_id;
							return $delete_stats.' records removed';

						}

					 public function getAllBeaconInfo(Request $request)
					{
						$pi_id = $request->input('pi_id');
						$getDatabaseClass = self::getDatabaseClass();
						$getAllBeaconInfo = $getDatabaseClass->getAllBusIDByBeacon($pi_id);

						return $getAllBeaconInfo;

					}

					/* 04-07-2020
					   New function to return current locations of buses to the app/dashboard

					*/
					public function getBusLocations(Request $request)
				  {
							$busCurrentLocation = array();

					 		$getDatabaseClass = self::getDatabaseClass();
					 		$busCurrentLocation = $getDatabaseClass->getBusCurrentLocations();

							//Do we need to return as a HTTP response?
					 		return $busCurrentLocation;

							//$pi_id = $request->input('pi_id');
							//$getDatabaseClass = self::getDatabaseClass();
							//$getAllBeaconInfo = $getDatabaseClass->getAllBusIDByBeacon(5);

							//return $getAllBeaconInfo;
				 	}


                    public function convertBustoptoNearestPolyLine(Request $request)
                    {
                             $routeno = $request->input('routeno');
                             $busserviceno = $request->input('busserviceno');

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
                             //$filecontent1 = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
									 					 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024
                             $json1 = json_decode($filecontent1, true);
                             $busroutecoords = $json1[$routeno]['route'];

                             $filecontent = file_get_contents('../data/jtk.json');
                             $json = json_decode($filecontent, true);

                             for ($i =0; $i<sizeof($json); $i++)
                             {
                                       $point[0] = $json[$i]["lat"];
                                       $point[1] = $json[$i]["lng"];

                                       $distances = array_map(function($item) use($point)
                                                                                        {
                                                                                                 $item = explode(',',$item);
                                                                                                 return self::caldistance($item, $point);
                                                                                        },       $busroutecoords  );
                                       asort($distances);

                                       if(array_values($distances)[0] < 0.07)
                                       {
                                                 print($busroutecoords[key($distances)] );
                                                 print("\r\n");
                                       }

                                       else
                                       {
                                                 print_r("Error \t".$distances[0]."\t".$point[0].",".$point[1]);
                                                 print("\r\n");
                                       }
                             }
                    }

                    public function getData(Request $request)
                    {
                             $routeno = $request->input('route_id');

                             $getData_Query = DB::table('bus_route')
                                                                    ->select('location_data.bus_id','location_data.latitude','location_data.longitude', 'location_data.speed', 'bus_route.bus_service_no')
                                                                    ->join('location_data', 'bus_route.bus_id', '=', 'location_data.bus_id')
                                                                    ->join('route', 'route.route_id','=', 'bus_route.route_id')
                                                                    ->where('location_data.route_id',$routeno)
                                                                    ->orderBy('time', 'desc')
                                                                    ->limit(1)
                                                                    ->get();

                             foreach($getData_Query as $singleset)
                             {
                                       print_r($singleset);
                             }
                    }

                    public function getKM(Request $request)
                    {
                             $busserviceno = $request->input('busserviceno');
                             $routeno = $request->input('routeno');

                             $totaldistance = array();
                             $busstop = self::getRoute($routeno,1);

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
														 //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
														 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

														 $json1 = json_decode($filecontent, true);
                             $busroutecoords = $json1[$routeno]['route'];

                             for ($i =0; $i <sizeof($busstop); $i ++)
                             {
                                       if($i == sizeof($busstop)-1)
                                       {
                                                 break;
                                       }

                                       for ($g=0; $g<sizeof($busroutecoords); $g++)
                                       {
                                                 if (trim($busstop[$i])==trim($busroutecoords[$g]))
                                                 {
                                                          $busCMP1 =$g;
                                                 }

                                                 if (trim($busstop[$i+1])==trim($busroutecoords[$g]))
                                                 {
                                                          $caltotaldistance = 0 ;

                                                          for ($z = $busCMP1 ; $z<$g-1; $z++)
                                                          {
                                                                    $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                    $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                                    $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);
                                                          }

                                                          array_push($totaldistance,$caltotaldistance);
                                                          break;
                                                 }
                                       }
                             }

                             print_r($totaldistance);
                    }

					public function getKM_syd(Request $request)
                    {
                             $busserviceno = $request->input('busserviceno');
                             $routeno = $request->input('routeno');

                             $totaldistance = array();
                             $busstop = self::getRoute($routeno,1);

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
														 //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
														 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

														 $json1 = json_decode($filecontent, true);
                             $busroutecoords = $json1[$routeno]['route'];

                             for ($i =0; $i <sizeof($busstop); $i ++)
                             {
                                       if($i == sizeof($busstop)-1)
                                       {
                                                 break;
                                       }

                                       for ($g=0; $g<sizeof($busroutecoords); $g++)
                                       {
                                                 if (trim($busstop[$i])==trim($busroutecoords[$g]))
                                                 {
                                                          $busCMP1 =$g;
                                                 }
                                                 if($g > 0)
												 {


													 if (trim($busstop[$i+1])==trim($busroutecoords[$g]))
													 {


															  $caltotaldistance = 0 ;

															  for ($z = $busCMP1 ; $z<$g; $z++)
															  {
																		$busstop1 = explode(",", trim($busroutecoords[$z]));
																		$busstop2 = explode(",", trim($busroutecoords[$z+1]));
																		$caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);


																		print($busroutecoords[$z]."\t".$busroutecoords[$z+1]."\r\n");
																		print("<br>");
																		print("Dist : ".$caltotaldistance);
																		print("<br>");
																		print("--------------------------");
																		print("<br>");
															  }


															  print("<br>");
															  print("============================");
															  print("<br>");

															  array_push($totaldistance,$caltotaldistance);
															  break;
													 }
												 }
                                       }
                             }

                             print_r($totaldistance);
							 print("++++++++++++++++++++++++++++++++");
							 print("<br>");
                    }


										/* 	21 Jul 2020
												new function testgetKM(Request $request)
												$arg1 = first point in string format "1.66,103.45"
												$arg2 = second point in string format "1.66,103.45"
										*/
										public function testgetKM(Request $request)
										{
														$busserviceno = $request->input('busserviceno');
														$routeno = $request->input('routeno');
														$arg1 = $request->input('arg1');
														$arg2 = $request->input('arg2');

                            
														//13 Aug 2020
														//map the coordinate to a location on the polyline
														$sourcePolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg1), 0.06);
														$destinationPolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg2), 0.06);
														$source = explode(',', $sourcePolyLine);
														$destination = explode(',', $destinationPolyLine);
														$arg1 = $source[0] . "," . $source[1];
														$arg2 = $destination[0] . "," . $destination[1];
														//End

														$totaldistance = self::getDistanceOnRoute($busserviceno, $routeno, $arg1, $arg2);

														return response(json_encode($totaldistance), 200);
										}

										/* 	21 Jul 2020
												Change from testgetKM(Request $request) to getDistanceOnRoute($busserviceno, $routeno, $arg1, $arg2)
												$arg1 = first point in string format "1.66,103.45"
												$arg2 = second point in string format "1.66,103.45"
										*/
                    public function getDistanceOnRoute($busserviceno, $routeno, $arg1, $arg2)
                    {
                             // $busserviceno = $request->input('busserviceno');
                             // $routeno = $request->input('routeno');
                             // $arg1 = $request->input('arg1');
                             // $arg2 = $request->input('arg2');

                             $totaldistance = array();
                             $busstop = self::getRoute($routeno,1);

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
														 //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
														 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

														 $json1 = json_decode($filecontent, true);
                             $busroutecoords = $json1[$routeno]['route'];
														 $busCMP1 = -1;

                             for ($g=0; $g<sizeof($busroutecoords); $g++)
                             {
                                       if (trim($arg1) == trim($busroutecoords[$g]))
                                       {
                                                $busCMP1 = $g;
                                                // print("Start : ".$g);
                                       }

                                       if (trim($arg2) == trim($busroutecoords[$g]))
                                       {
                                                // print(" End :   ".$g."\r\n");

																								//This means that the first point was not found.
																								if ($busCMP1 == -1)
																										return -1;

                                                $caltotaldistance = 0 ;

                                                for ($z = $busCMP1 ; $z<$g-1; $z++)
                                                {
                                                          $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                          $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                          $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);

														  //print($busroutecoords[$z]."\t".$busroutecoords[$z+1]."\r\n");
														  //print("<br>");
														  //print("Dist : ".$caltotaldistance);
														  //print("<br>");
                                                }

                                                array_push($totaldistance,$caltotaldistance);
                                       }
                             }

                             //print(json_encode($totaldistance));
                             //print_r($totaldistance);
                             //return response(json_encode($totaldistance), 200);
														 return $totaldistance;

                    }

                    public function testCal(Request $request)
                    {
                             $km = 0;
                             $latlong1 = $request->input('latlong1');
                             $latlong2 = $request->input('latlong2');

                             $a = explode(",", trim($latlong1));
                             $b = explode(",", trim($latlong2));

                             list($lat1, $lon1) = $a;
                             list($lat2, $lon2) = $b;
                             //print("Lat :".$lat1." Lon :".$lon1."\r\n");
                             //print("Lat :".$lat2." Lon :".$lon2."\r\n");

                             $theta = $lon1 - $lon2;
                             $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
                             $dist = acos($dist);
                             $dist = rad2deg($dist);
                             $km = ($dist * 60 * 1.1515) * 1.609344;
                             //print($dist."\n");
							 						 	 print($km);

                    }

					public function syd_Cal(Request $request)
                    {
                             $km = 0;
                             $latlong1 = $request->input('latlong1');
                             $latlong2 = $request->input('latlong2');

                             $a = explode(",", trim($latlong1));
                             $b = explode(",", trim($latlong2));

                             list($lat1, $lon1) = $a;
                             list($lat2, $lon2) = $b;

                             $theta = $lon1 - $lon2;
                             $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
                             $dist = acos($dist);
                             $dist = rad2deg($dist);
                             $km = ($dist * 60 * 1.1515) * 1.609344;
							 print($km);

                    }


                    public function getRoute($routeno,$id)
                    {
                             $busstop = array();

                             if ($id == 1)
                             {
                                       $myfile = fopen("../data/busstopPolylinePositions/".$routeno.".txt", "r") or die("Unable to open file!");
                             }

                             else
                             {
                                       $myfile = fopen("../data/busstopPolylinePositions/".$routeno." to KM.txt", "r") or die("Unable to open file!");
                             }

                             while (!feof($myfile) )
                             {
                                       $data = fgets($myfile);

                                       if (!empty($data))
                                       {
                                                 array_push($busstop,$data);
                                       }
                             }

                             fclose($myfile);
                             return $busstop;
                    }

                    public function getFurthestRecord($bus_id,$route_id)
                    {
							$getDatabaseClass = self::getDatabaseClass();
                             //$sim_date = mktime(18, 12, 00, 2, 7, 2018);
                             $a = $getDatabaseClass->getLastRecord($bus_id,$route_id,date("Y-m-d H:i:s",time() -300));
							 //$a = $getDatabaseClass->getLastRecord($bus_id,$route_id,date("Y-m-d H:i:s",$sim_date -300));

							 print_r($a);
                             if (sizeof($a) >0 )
                             {
															 				 //BEGIN 20th MAY 2024
																			 //Use Global variable to read the polyline content once only
																			 //$filecontent = file_get_contents('../data/'.$a[0]->bus_service_no.'.json');
																			 $globalVariable = view()->shared('bus_polyline');
																			 $filecontent = $globalVariable[$a[0]->bus_service_no];
																			 //end 20th MAY 2024

                                       $json1 = json_decode($filecontent, true);
                                       $busroutecoords = $json1[$a[0]->route_id]['route'];
                                       $furthestID = -1;
                                       $furthestLatLongID;

                                       for ($j=0; $j < sizeof($a); $j++)
                                       {
                                                $g = array_search(trim($a[$j]->latitude.",".$a[$j]->longitude),$busroutecoords);

                                                if($furthestID < $g)
                                                {
                                                          $furthestID = $g;
                                                          $furthestLatLongID =$j;
                                                }
                                       }

                                       return $a[$furthestLatLongID];
                             }
                    }

                    public function getFurthestRecordV2($bus_id,$route_id)
                    {
							 $getDatabaseClass = self::getDatabaseClass();
                             $time = date("Y-m-d H:i:s",time() - 300);

							 $a = $getDatabaseClass->getLastRecordV2($bus_id,$route_id,$time);

                             if (sizeof($a) > 0 )
                             {

															 				 //BEGIN 20th MAY 2024
															 				 //Use Global variable to read the polyline content once only
															 				 //$filecontent = file_get_contents('../data/'.$a[0]->bus_service_no.'.json');
															 				 $globalVariable = view()->shared('bus_polyline');
															 				 $filecontent = $globalVariable[$a[0]->bus_service_no];
															 				 //end 20th MAY 2024

                                       $json1 = json_decode($filecontent, true);
                                       $busroutecoords = $json1[$a[0]->route_id]['route'];
                                       $furthestID = -1;
                                       $furthestLatLongID;

                                       for ($j=0; $j < sizeof($a); $j++)
                                       {
                                                 $g = array_search(trim($a[$j]->latitude.",".$a[$j]->longitude),$busroutecoords);

                                                 if($furthestID < $g)
                                                 {
                                                          $furthestID = $g;
                                                          $furthestLatLongID =$j;

												 }

                                       }

                                       return $a[$furthestLatLongID];
                             }


                    }

                    public function checkNearest5BusStop($userLocation,$busroutecoords,$busstop,$routeID)
                    {
                             $userPosition;

                             for ($g = 0; $g <sizeof($busroutecoords); $g++)
                             {
                                       if (trim($userLocation)==trim($busroutecoords[$g]))
                                       {
                                                $userPosition = $g;
                                                break;
                                       }
                             }

                             $nearestBusStopID = 0;

                             $continue =1;

                             for ($k = $userPosition; $k <sizeof($busroutecoords); $k++)
                             {
                                       if ($continue == 0)
                                       {
                                                break;
                                       }

                                       for ($i =0; $i < 5; $i++)
                                       {
                                                 if (trim($busstop[$i])==trim($busroutecoords[$k]))
                                                 {
                                                          $nearestBusStopID = $i;

                                                          $continue =0;
                                                 }

                                                 if($continue == 0)
                                                 {
                                                          break;
                                                 }
                                       }
                             }

                             return $nearestBusStopID + $routeID.",".$userPosition;
                    }
                    /*
                    public function pushCurrentData(Request $request)
                    {
                             $busid = $request->input('busid');
                             $busserviceno = $request->input('busserviceno');
                             $previouslocation = $request->input('previouslocation');
                             $previouslocation = explode(',',$previouslocation);
                             $routeno = $request->input('routeno');
                             $currentlocation = $request->input('currentlocation');
                             $currentlocation = explode(',',$currentlocation);
                             $timediff = $request->input('timediff');
                             $currenttime = $request->input('currenttime');
                             $busradius = self::setRadius()['busradius'];

                             if($busid == "" || $busserviceno=="" || $previouslocation == "" || $routeno == "" || $currentlocation == "" || $timediff == "" || $currenttime == "")
                             {

                                       return response("false",200);
                             }

                             if($previouslocation == $currentlocation)
                             {
                                       $distance = 0;
                             }

                             else
                             {
                                       $distance = self::distancebetweenpointsonroute($busserviceno,$routeno,$previouslocation,$currentlocation);
                             }

                             if($distance == 0 || $timediff == 0)
                             {
                                       $speedkmhr = 0;
                                       return response("false",200);
                             }

                             else
                             {
                                       $speedkmhr = $distance / $timediff;
                             }

                             $newlocation = self::closepointonroute($busserviceno,$routeno,$currentlocation,$busradius);

                             if($newlocation == null)
                             {
                                       return response("false",200);
                             }

                             $newlocation = explode(',',$newlocation);

                             //need changes
                             // $this->load->model('User_model');
                             //$result = $this->User_model->pushCurrentData($busid,$busserviceno,$routeno,$newlocation[0],$newlocation[1],$newlocation[2],$speedkmhr,$currenttime);

							  $result = 'true';
                             return response($result,200);
                    }
                    */
                    /* public function getETA(Request $request)
                    {
                             $busstopno = $request->input('busstopno');
                             $busserviceno = $request->input('busserviceno');

                             if($busserviceno == ""|| $busstopno == "")
                             {
                                       return response("false",200);
                             }

                             $data = self::getNextBusTiming($busstopno, $busserviceno);

                             if(is_null($data))
                             {
                                       return response("false",200);
                             }

                             else
                             {
                                       return response($data,200);
                             }
                    } */

                    public function getBusServiceRouteNo($busstopno,$busserviceno)
                    {
														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
														 //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
														 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

                             $json = json_decode($filecontent, true);
                             $routeno = 0;

                             for($j=0;$j< sizeof($json[1]['stops']);$j++)
                             {
                                       if($json[1]['stops'][$j]==$busstopno)
                                       {
                                                $routeno=1;
                                       }
                             }

                             if($routeno == 0)
                             {
                                       for($j=0;$j< sizeof($json[2]['stops']);$j++)
                                       {
                                                if($json[2]['stops'][$j]==$busstopno)
                                                {
                                                          $routeno=2;
                                                }
                                       }
                             }

                             return $routeno;
                    }

                    /* public function getNextBusTiming($busstopno,$busserviceno)
                    {
                             $postdata = null;
                             $routeno = self::getBusServiceRouteNo($busstopno,$busserviceno);
                             $busstopradius = self::setRadius()['busstopradius'];
                             if($routeno == 0)
                             {
                                       return $postdata;
                             }

                             $filecontent = file_get_contents('../data/bus-stop.json');
                             $json = json_decode($filecontent, true);

                             $busstopdetails = array_filter($json, function ($bsd) use($busstopno)
                                                                                                 {
                                                                                                           return $bsd['no'] == $busstopno;
                                                                                                 });
                             $busstopcoords = array($json[key($busstopdetails)]['lat'],$json[key($busstopdetails)]['lng']);
                             $busstopcoords = self::closepointonroute($busserviceno,$routeno,$busstopcoords,$busstopradius);

                             if($busstopcoords == null)
                             {
                                       return null;
                             }

                             //need changes
                            //  $this->load->model('User_model');
                             //$nextbuses = $this->User_model->getBusRecordsByServiceNo($busserviceno,$routeno,explode(',',$busstopcoords)[2]);

							  $nextbuses = null;
                             if($nextbuses == null)
                             {
                                       return null;
                             }

                             $nearestbusindex = 0;

                             for($i = 0;$i<sizeof($nextbuses);$i++)
                             {
                                       if($nextbuses[$i]->PosInJSON >= $nearestbusindex)
                                       {
                                                $nearestbusindex = $i;
                                       }
                             }

                             $beforedatetime = date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($nextbuses[$nearestbusindex]->Time)));
                             //need changes
                             $objavgspeed = $this->User_model->getByIdAvgSpeed($nextbuses[$nearestbusindex]->BusID,$nextbuses[$nearestbusindex]->Time,$beforedatetime);

                             $ccoord = array($nextbuses[$nearestbusindex]->CurrentLat,$nextbuses[$nearestbusindex]->CurrentLong);

                             $distance = self::distancebetweenpointsonroute($busserviceno,$nextbuses[$nearestbusindex]->RouteNo,$ccoord,explode(',',$busstopcoords));

                             if($distance == 0 ||$objavgspeed->avgspeed == 0 )
                             {
                                       return null;
                             }

                             else
                             {
                                       $time = $distance / $objavgspeed->avgspeed;
                                       settype($time, "float");

                                        $postdata = array(
                                                         $busserviceno => array( 'Time'=>$nextbuses[$nearestbusindex]->Time,
                                                          'TravelTime'=>$time
                                                          )
                                                );
                                                return $postdata;
                             }
                    }
                     */
                    /* public function getBusStopServices(Request $request)
                    {
                             $busstopno = $request->input('busstopno');

                             if($busstopno == "")
                             {
                                       return response("false",200);
                             }

                             //need changes
                             // $this->load->model('User_model');
                             //$busservices = $this->User_model->getBusStopServices($busstopno);


                             $filecontent = file_get_contents('../data/bus-stop.json');
                             $json = json_decode($filecontent, true);

                             $busstopdetails = array_filter($json, function ($bsd) use($busstopno)
                                                                              {
                                                                                       return $bsd['no'] == $busstopno;
                                                                              });
                             $postdata = array(
                                                'busstopname' => $json[key($busstopdetails)]['name'],
                                                'busservices'=>$busservices
                                                );

                             return response($postdata,200);

                    }
                     */
                    /* public function getCoordsViaId(Request $request)
                    {
                             $busid = $request->input('busid');

                             //need changes
                             $this->load->model('User_model');
                             $buscoords = $this->User_model->getCoordsViaId($busid);
                             return response(json_encode($buscoords),200);

                    }
                     */

										//BEGIN 12 Aug 2024
										//Function to crawl PAJ location data
										public function crawlFleetData(Request $request)
										{

											$bus_service_no = $request->input('bus_service_no');
											$getBusInfoController = self::getBusInfoController();
											$p101_bus = array();

											//PAJ API key
											$api_key = getenv('PAJ_API_KEY');
											$headers = ['api-key' => $api_key];

											$url = 'https://dataapi.paj.com.my/api/v1/bus-live/route/'.$bus_service_no;
											//$insertLocationURL = 'https://laravelsyd-fypfinalver.herokuapp.com/bus_insertlocation';
											$insertLocation_data = ['latlong'=>array(1,2), 'speed' => 0, 'imei' => "123345", 'date' => '2024-01-01 00:00:00', 'route_id' => 16, 'bus_id' => 35];

											//$removephantomETA = 'https://laravelsyd-fypfinalver.herokuapp.com/removephantomETA';
											//$phantom_data = ['bus_id' => 35];

											//$laravelETA = 'https://laravelsyd-fypfinalver.herokuapp.com/getETA';
											$laravelETA_headers = ['content-type' => 'application/json'];
											$laravelETA_data = ['bus_stop_id' => 1036, 'route_id' => 16, 'bus_id' => 35, 'service_no' => 'P101'];

											//Get the bus information
											$getBusInfo_Query = DB::table('bus_route as br')
																	->selectraw('distinct b.plate_no, br.bus_id')
																	->join('bus as b', 'br.bus_id', '=', 'b.bus_id')
																	->where('br.bus_service_no', $bus_service_no)
																	->get();

											foreach($getBusInfo_Query as $singleset)
											{
													$p101_bus[$singleset->plate_no] = $singleset->bus_id;
													//get route ID
													$routeNo = self::getDatabaseClass()->getRouteID($singleset->bus_id);
											}

											// $location_data = ['latitude' => 0, 'longitude' => 0, 'speed' => 0, 'bus' => "JSN0000", 'route' => 16, 'bus_id' => 0, 'time' => "2024-01-01 00:00:00"];

											// $p101 = [
											//     "JSN7055" => $location_data,
											//     "JSN5814" => $location_data,
											//     "JSN4410" => $location_data,
											// ];

											//GetBusStop Info, departure terminal, and final terminal.
											$busStopInfo = self::getBusInfoController()->getBusstopRoute_method($routeNo[0]);

											$bus_terminal[$busStopInfo[0]->bus_stop_id] = [$busStopInfo[0]->latitude, $busStopInfo[0]->longitude];
											$bus_terminal[$busStopInfo[sizeof($busStopInfo)-1]->bus_stop_id] = [$busStopInfo[sizeof($busStopInfo)-1]->latitude, $busStopInfo[sizeof($busStopInfo)-1]->longitude];

											//print_r($bus_terminal1);

											// $bus_terminal = [
											//     "Larkin" => [1.495144, 103.742662],
											//     "Kulai" => [1.662585, 103.598608],
											//     "JB" => [1.463400, 103.764932],
											//     "TamanUniversiti" => [1.538506, 103.628711],
											// ];

											// $p101_bus = [
											//     "JSN7055" => 35,
											//     "JSN5814" => 36,
											//     "JSN4410" => 37,
											// ];

											//Only insert location during bus operation hours
											$hour = date('H');
											$min = date('i');
											//echo $min;

											if (($hour < 6) or ($hour > 23))
													return;

											$response = file_get_contents($url, false, stream_context_create(['http' => ['header' => 'api-key: ' . $api_key]]));
											$fleet = json_decode($response, true);

											foreach ($fleet["data"] as $bus) {
											    //$info = $p101[$bus["bus"]];
											    $info["latitude"] = $bus["latitude"];
											    $info["longitude"] = $bus["longitude"];
											    $info["speed"] = $bus["speed"];
											    $info["time"] = $bus["timestamp"];
											    $info["bus_id"] = $p101_bus[$bus['bus']];
											    $currentLocation = [$bus["latitude"], $bus["longitude"]];
											    //print_r($currentLocation);
													$curr_timestamp = time();
											    $prev_timestamp = strtotime($info["time"]);

											    if ($curr_timestamp - $prev_timestamp < 300) {
															$insertLocation_data['speed'] = $info['speed'];
															$insertLocation_data['date'] = $info['time'];
															//insert as an array
															$insertLocation_data['latlong'] = [$bus["latitude"], $bus["longitude"]];
															$insertLocation_data['bus_id'] = $info['bus_id'];

											        if (($bus['route'] == [$bus_service_no]) && ($bus["speed"] != 0)) {
											            $laravelETA_data["route_id"] = $routeNo[0];
											            $laravelETA_data["service_no"] = $bus_service_no;
											            $laravelETA_data["bus_id"] = $p101_bus[$bus['bus']];
											            $laravelETA_data["bus_stop_id"] = array_key_last($bus_terminal);

																	$route  = self::getBusInfoController()->getETA_method($laravelETA_data["bus_stop_id"], $laravelETA_data["bus_id"], $laravelETA_data["route_id"], false);
																	//print("output of route ETA");
																	//print_r($route);
											            //$route = file_get_contents($laravelETA . '?' . http_build_query($laravelETA_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
											            //echo $route . "\n";

											            if (($route != null) and (strcmp($route, "No bus service found") == 0)) {
													                $laravelETA_data["route_id"] = $routeNo[1];
													                $laravelETA_data["service_no"] = $bus_service_no;;
													                $laravelETA_data["bus_id"] = $p101_bus[$bus['bus']];
													                $laravelETA_data["bus_stop_id"] = array_key_first($bus_terminal)-1;
													                $eta  = self::getBusInfoController()->getETA_method($laravelETA_data["bus_stop_id"], $laravelETA_data["bus_id"], $laravelETA_data["route_id"], false);
																					//echo "check route 17\n";
													                //echo $eta . "\n";

													                if (($eta != null) and (strcmp($eta, "No bus service found") == 0)){
													                    $distance = self::caldistance($bus_terminal[array_key_last($bus_terminal)], $currentLocation);
													                    //echo "\nDistance to: " . array_key_last($bus_terminal) . " is  ";
													                    //echo $distance . "\n";

																							//26 AUG 2024
																							//Increase the radius from 0.2 to 0.25 as P103 couldn't detect the bus when departing from PPR Stulang
																							//bus is close to the destination terminal, and hence should be on the way back.
																							if ($distance < 0.25) {
															                    $insertLocation_data['route_id'] = $routeNo[1];
															                    //$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																									$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																									//echo "insert to JB route 17\n";
															                    //echo $res . "\n";
															                }
																							else {
													                    		$insertLocation_data['route_id'] = $routeNo[0];
																									$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
													                    		//$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
													                    		//echo "insert route 16\n";
													                    		//echo $res . "\n";

													                    		//$distance = self::caldistance($bus_terminal[array_key_last($bus_terminal)], $currentLocation);
													                    		//echo "Distance to JB Sentral\n";
													                    		//echo $distance . "\n";

													                    		if ($res == 0) {
													                        		$insertLocation_data['route_id'] = $routeNo[1];
													                        		//$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																											$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																											//echo "insert to JB route 17\n";
													                        	//echo $res . "\n";
																									}
													                    }
													                }
																					elseif (($eta != null) and (strcmp($eta, "No bus service found") != 0)) {
													                    $insertLocation_data['route_id'] = $routeNo[1];
													                    //$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																							$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																							//echo "insert to JB route 17\n";
													                    //echo $res . "\n";
													                }
											            }
																	elseif (($route != null) and (strcmp($route, "No bus service found") != 0)) {
											                $currentLocation = [$bus["latitude"], $bus["longitude"]];
											                $distance = self::caldistance($bus_terminal[array_key_last($bus_terminal)], $currentLocation);
											                //echo "Distance to JB Sentral\n";
											                //echo $distance . "\n";

																			//This is only for P101, as it is a loop service...
											                if (($distance < 0.25) and (strcmp("P101", $bus_service_no) == 0)) {
											                    $insertLocation_data['route_id'] = $routeNo[1];
											                    //$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																					$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																					//echo "insert to JB route 17\n";
											                    //echo $res . "\n";
											                }

											                $distance = self::caldistance($bus_terminal[array_key_first($bus_terminal)], $currentLocation);
											                //echo "Distance to Larkin\n";
											                //echo $distance . "\n";

											                if (($distance < 0.15) && ($info['speed'] == 0)) {
											                    //$phantom = file_get_contents($removephantomETA . '?' . http_build_query($phantom_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																					$getDatabaseClass = self::getDatabaseClass();
																					$delete_stats = $getDatabaseClass->rmPhantomETA($info["bus_id"]);
																					//echo "removePhantomETA\n";
											                } else {
											                    $insertLocation_data['route_id'] = $routeNo[0];
											                    //$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																					$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																					//echo "insert to Larkin route 16\n";
											                    //echo $res . "\n";
											                }
											            }
											        } elseif (($bus['route'] == [$bus_service_no]) && ($bus["speed"] == 0)) {
											            $distance = self::caldistance($bus_terminal[array_key_first($bus_terminal)], $currentLocation);
											            //echo "Distance to Larkin\n";
											            //echo "\n Distance to " . array_key_first($bus_terminal) . ": " . $distance . "\n";

											            if ($distance < 0.1) {
											                //$phantom_data['bus_id'] = $info["bus_id"];
											                //$phantom = file_get_contents($removephantomETA . '?' . http_build_query($phantom_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																			$getDatabaseClass = self::getDatabaseClass();
																			$delete_stats = $getDatabaseClass->rmPhantomETA($info["bus_id"]);
																			//echo "removePhantomETA\n";
											            }

											            $currentLocation = [$bus["latitude"], $bus["longitude"]];
											            $distance = self::caldistance($bus_terminal[array_key_last($bus_terminal)], $currentLocation);
											            //echo "Distance to JB Sentral\n";
											            //echo "\n Distance to " . array_key_last($bus_terminal) . ": " . $distance . "\n";

											            if ($distance < 0.25) {
											                $insertLocation_data['route_id'] = $routeNo[1];
											                //$insert = file_get_contents($insertLocationURL . '?' . http_build_query($insertLocation_data), false, stream_context_create(['http' => ['header' => 'content-type: application/json']]));
																			$res = self::insertLocation($insertLocation_data['bus_id'], $insertLocation_data['route_id'], $insertLocation_data['imei'], $insertLocation_data['speed'], $insertLocation_data['date'], $insertLocation_data['latlong']);
																			//echo "insert to JB route 17\n";
											                //echo $res . "\n";
											            }
											        }
											    }
											}

											if ($response) {
											    echo $response;
											} else {
											    echo 'Error: ' . $response;
											}
										}
										//END 12 Aug 2024

                    public function getBusRoute(Request $request)
                    {
                             $busserviceno = $request->input('busserviceno');
                             $routeno = $request->input('routeno');

														 //BEGIN 20th MAY 2024
														 //Use Global variable to read the polyline content once only
														 //$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														 $globalVariable = view()->shared('bus_polyline');
														 $filecontent = $globalVariable[$busserviceno];
														 //end 20th MAY 2024

                             $json = json_decode($filecontent, true);
                             $busroutecoords = $json[$routeno]['route'];
                             return response(json_encode($busroutecoords),200);
                    }

                    public function caldistance($a, $b)
                    {
                             list($lat1, $lon1) = $a;
                             list($lat2, $lon2) = $b;

                             $theta = $lon1 - $lon2;
                             $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
                             $dist = acos($dist);
                             $dist = rad2deg($dist);
                             $km = ($dist * 60 * 1.1515) * 1.609344;

														 //print_r($dist);
                             if (is_nan($km))
                             {
                                       $km = 0;
                                       return $km;
                             }

                             return $km;
                    }

                    /*public function distancebetweenpointsonroute($busserviceno,$routeno,$point1,$point2)
                    {
                             $busradius = self::setRadius()['busradius'];
							 $pointA = self::closepointonroute($busserviceno,$routeno,$point1,$busradius);
                             $pointB = self::closepointonroute($busserviceno,$routeno,$point2,$busradius);

                             if($pointA == null || $pointB == null || $pointA == $pointB )
                             {
                                       return 0;
                             }

                             $pointALocation = explode(',',$pointA)[2];
                             $pointBLocation = explode(',',$pointB)[2];

                             if($pointALocation>$pointBLocation)
                             {
                                       return 0;
                             }

                             $filecontent = file_get_contents('../data/'.$busserviceno.'.json');
                             $json = json_decode($filecontent, true);
                             $busroutecoords = $json[$routeno]['route'];

                             $totaldistance = floatval(0);

                             if($pointBLocation>$pointALocation)
                             {
                                       for($i = $pointALocation; $i < $pointBLocation;$i++ )
                                       {
                                                $a = explode(',',$busroutecoords[$i]);
                                                $b = explode(',',$busroutecoords[$i+1]);
                                                $totaldistance = $totaldistance + self::caldistance($a,$b);
                                       }
                             }

                             return $totaldistance;
                    }
                    */
                    public function checkClosePointExist(Request $request)
                    {
                            $routeno = $request->input('routeno');
                            $busserviceno = $request->input('busserviceno');
                            $previouslocation = $request->input('previouslocation');
                            $busradius = self::setRadius()['busradius'];

                            if($busserviceno == ""|| $routeno == "" || $previouslocation =="")
                            {
                                      return response('false',200);
                            }

                            $previouslocation = explode(',',$previouslocation);

                            $data = self::closepointonroute($busserviceno,$routeno,$previouslocation,$busradius);

                            if(is_null($data))
                            {
                                      return response('false',200);
                            }

                            else
                            {
                                      return response('true',200);
                            }


                    }

                    public function closepointonroute($busserviceno,$routeno,$point,$radius)
                    {
														//BEGIN 20th MAY 2024
														//Use Global variable to read the polyline content once only
														//$filecontent = file_get_contents('../data/'.$busserviceno.'.json');
														$globalVariable = view()->shared('bus_polyline');
														$filecontent = $globalVariable[$busserviceno];
														//end 20th MAY 2024

                            $json = json_decode($filecontent, true);
                            $busroutecoords = $json[$routeno]['route'];
                            $distances = array_map(function($item) use($point)
                                                                              {
                                                                                      $item = explode(',',$item);
                                                                                      return self::caldistance($item, $point);
                                                                              }, $busroutecoords);
                             asort($distances);

                             if(array_values($distances)[0] < $radius)
                             {
                                       return $busroutecoords[key($distances)] . ',' . key($distances);
                             }

                             else
                             {
                                       return null;
                             }
                    }

                    public function calculateAverageSpeed($serviceno, $routeno, $location_data)
                    {



                            $point1 = array($location_data[0]->latitude, $location_data[0]->longitude);
                            $lastPos = end($location_data);
                            $point2 = array($lastPos->latitude, $lastPos->longitude);
                            $size_Location_data = count($location_data);
                            $half_size = floor($size_Location_data / 2)-1;
                            print("point 1 : "."<br>");
														print_r($point1);
														print("point 2 : "."<br>");
														print_r($point2);
														print("<br>");

                            if ($half_size < 0)
                            {
                            		$half_size = 0;
                            }

                            print_r($point2);

                            $distance = self::Ian_distancebetweenpointsonroute($serviceno, $routeno,$point1,$point2);

                            $timediff = strtotime($lastPos->time) - strtotime($location_data[0]->time);

														//print_r($lastPos);
														//print_r($location_data);
														//echo "\nSize: " . $size_Location_data . "\n";

														//Assuming Bus is stationary at Bus STOP
														if ($distance == 0)
														{
															$avgSpeed = -1;
															print("COND 2: use historical \r\n");
															return $avgSpeed;
														}

                            if ($timediff < 240 && $lastPos->speed <= 5)
                             {
                                      $avgSpeed = -2;
                                      print("COND 1: no update \r\n");
                                      return $avgSpeed;
                             }

                             else if ($timediff < 60 && $lastPos->speed > 2)
                             {
                                      $avgSpeed = -1;
                                      print("COND 2: use historical \r\n");
                                      return $avgSpeed;
                             }

                             else if ($lastPos->speed<5 && $location_data[$size_Location_data-2]->speed < 2)
                             {
                                      $avgSpeed = -1;
                                      print("COND 3a: use historical \r\n");
                                      return $avgSpeed;
                             }

														 //BEGIN 20th August 2024
														 else if ($size_Location_data > 2) {
														 		if ($lastPos->speed>=5 && $location_data[$size_Location_data-2]->speed < 2 && $location_data[$size_Location_data-3]->speed < 2)
                             		{
                                      $avgSpeed = -1;
                                      print("COND 3b: use historical \r\n");
                                      return $avgSpeed;
                             		}
														 }
														 //END 20th August 2024

                             else
                             {
                                      for ($x=$size_Location_data-2; $x>=1; $x--)
                                      {
                                                if ($location_data[$x]->speed < 2 && $location_data[$x-1]->speed < 2)
                                                {
                                                          $distance = self::Ian_distancebetweenpointsonroute($serviceno, $routeno, array($location_data[$x]->latitude, $location_data[$x]->longitude),$point2);
																													// print_r($lastPos);
																													// print($x);
																													// print_r($location_data[$x]);
																													print("last position");
																													print_r($lastPos);
																													// print($location_data[$x]->time);
                                                          $timediff = strtotime($lastPos->time) - strtotime($location_data[$x]->time);
                                                          print("COND 4: re-calculate distance and time \r\n");
                                                          print("TIME DIFF:" .$timediff. "\r\n");
                                                          break;
                                                }
                                      }
                             }

                             if ($timediff > 120 && $location_data[0]->speed >= 4)
                             {
                                       print("NORMAL OPERATION_1 \r\n");
									   							 		 print("Distance : ".$distance);
                                       $avgSpeed = $distance / ($timediff / 3600);

							 							 }

                             else if ($timediff > 120 && $location_data[$half_size]->speed >= 4)
                             {
                                       print("NORMAL OPERATION \r\n");
                                       $avgSpeed = $distance / ($timediff / 3600);
                             }

                             else if ($lastPos->speed > 4)
                             {
                                       $avgSpeed = $lastPos->speed;
                                       print("reported speed used \r\n");
                             }
                             else
                             {
                                       $avgSpeed = -2;
                                       print("reported speed = 0 \r\n");
                             }

                             if ($avgSpeed == 0)
                             {
                                       $avgSpeed = -2;
                             }

                             return ($avgSpeed);
                    }

                   /* public function calculateETAWin()
                    {
                             //date_default_timezone_set('Asia/Singapore');
                             $getDatabaseClass = self::getDatabaseClass();
                             $totalbus = $getDatabaseClass->getTotalBus();

                             for ($q = 0; $q <sizeof($totalbus); $q++)
                             {
                                       $routeNo = $getDatabaseClass->getRouteID($totalbus[$q]);
                                       print("bus_id: ".$totalbus[$q]. "\r\n");

                                       for ($g = 0;  $g <sizeof($routeNo); $g++)
                                       {
                                                 print("Route No.: ".$routeNo[$g]. "\r\n");

                                                 $busstopID = $getDatabaseClass->getFirstBusstopIDFromRoute($totalbus[$q],$routeNo[$g]);

                                                 $data = self::getFurthestRecord($totalbus[$q],$routeNo[$g]);
                                                 print_r($data);

                                                 if(!empty($data))
                                                 {
													  $filecontent = file_get_contents('../data/'.$data->bus_service_no.'.json');
                                                          $json1 = json_decode($filecontent, true);
                                                          $busroutecoords = $json1[$routeNo[$g]]['route'];
                                                          $busstop = self::getRoute($routeNo[$g],1);
                                                          $check = false;

                                                          if($data->speed > 4)
                                                          {
                                                                    if($data->flag == 0)
                                                                    {
                                                                              $speed = $getDatabaseClass->avgSpeed($routeNo[$g],$data->bus_id,$data->time,date("Y-m-d H:i:s",strtotime("-5 minutes",strtotime($data->time))));
                                                                              $totaldistance = array();
                                                                              $busstopKM = self::getRoute($routeNo[$g],2);
                                                                              $point[0] = $data->latitude;
                                                                              $point[1] = $data->longitude;
                                                                              $continue = 1;
                                                                              $caltotaldistance = 0;$distances = array_map(function($item) use($point)
                                                                                                                                                           {
                                                                                                                                                                     $item = explode(',',$item);
                                                                                                                                                                     return self::caldistance($item, $point);
                                                                                                                                                           }, $busroutecoords);
                                                                              asort($distances);

                                                                              print("aaaaa \t".sizeof($busroutecoords));

                                                                              for ($i=0; $i<sizeof($busroutecoords); $i++)
                                                                              {
                                                                                       if (trim($busroutecoords[key($distances)]) == trim($busroutecoords[$i]))
                                                                                       {
                                                                                                 print("aaa");
                                                                                                 $uploadedlocation = $i;
                                                                                       }
                                                                              }

                                                                              for ($z=$uploadedlocation; $z<sizeof($busroutecoords); $z++)
                                                                              {
                                                                                       if ($z+1 < sizeof($busroutecoords))
                                                                                       {
                                                                                                 $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                 $busstop2 = explode(",", trim($busroutecoords[$z+1]));
                                                                                                 $caltotaldistance = $caltotaldistance + self::caldistance($busstop1,$busstop2);

                                                                                                 for ($x = 0;$x<sizeof($busstop); $x++)
                                                                                                 {
                                                                                                           if(trim($busstop[$x])==trim($busroutecoords[$z]))
                                                                                                           {
                                                                                                                     print("busstop ".sizeof($busstop)."\r\n");
                                                                                                                     $continue = 0;

                                                                                                                     print("ROUTE NO: " .$routeNo[$g]. "\r\n");
                                                                                                                     print("BUS SERVICE NO: " .$data->bus_service_no. "\r\n");
                                                                                                                     print("BUS STOP NEXT: " .trim($busstop[$x]). "\r\n");

                                                                                                                     $time = $caltotaldistance / $speed;
                                                                                                                     $time = $time * 3600;
                                                                                                                     print("Speed2: ".$speed);

                                                                                                                     $keepTime = $time;
                                                                                                                     $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));

                                                                                                                     $hi1=$x+$busstopID;
                                                                                                                     $getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$hi1,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                                                     $getDatabaseClass->getHistoryETAV1($data->bus_id,$routeNo[$g],$data->bus_service_no,$hi1,$keepTime);
                                                                                                                     break;
                                                                                                           }
                                                                                                 }

                                                                                                 if($continue ==0)
                                                                                                 {
                                                                                                           break;
                                                                                                 }
                                                                                       }

                                                                                       else
                                                                                       {
                                                                                                 $busstop1 = explode(",", trim($busroutecoords[$z]));
                                                                                                 $caltotaldistance = $caltotaldistance + self::caldistance($point,$busstop1);
                                                                                                 $time = $caltotaldistance / $speed;
                                                                                                 $time = $time * 3600;
                                                                                                 $time = date("Y-m-d H:i:s", $time +strtotime("+0 seconds"));
                                                                                                 $hi1=$busstopID+sizeof($busstopKM);

                                                                                                 $getDatabaseClass->uploadETA($data->bus_id,$routeNo[$g],$hi1,$time,date('Y-m-d H:i:s', time()),$speed);
                                                                                       }
                                                                              }
                                                                    }

                                                                    $getDatabaseClass->updateFlag(1,$data->bus_id,$data->route_id,$data->time);
                                                          }

                                                 }

									   }
                             }
                    }
                    */
                    public function calculateHistoricDataAverage(Request $request)
                    {
                             //date_default_timezone_set('Asia/Singapore');

                             $routeno = $request->input('routeno');
                             $bus_service_no = $request->input('bus_service_no');


                             $calculateHistoricDataAverage_Query = DB::table('avg_spd')
                                                                                                 ->select('bus_stop_id_previous', 'bus_stop_id_next', 'start', 'end', 'avg_speed', 'avg_time', 'day')
                                                                                                 ->where('route_id',$routeno)
                                                                                                 ->where('bus_service_no',$bus_service_no)
                                                                                                 ->get();

                             $data = array();

                             foreach($calculateHistoricDataAverage_Query as $singleset)
                             {

                                       $data[] = $singleset;
                             }

                             $calculateHistoricDataAverage_Query3 = DB::table('avg_speed_calculated')
                                                                                                 ->where('route_id', '!=', $routeno)
                                                                                                 ->get();

                             $data3 = array();

                             foreach($calculateHistoricDataAverage_Query3 as $singleset3)
                             {
                                       $data3[] = $singleset3;

                             }

                             $calculateHistoricDataAverage_Query2 = DB::table('avg_speed_calculated')->delete();

                             $busstop = self::getRoute($routeno,1);

                             $busstopKM = self::getRoute($routeno,2);

                             for($i=0; $i<sizeof($busstop)-1; $i++)
                             {
                                       $avgSpeed = 0;
                                       $avgTime = 0;
                                       $counter = 0;
                                       $busstopid_previous = -1;
                                       $busstopid_next = -1;

                                       for($o=0; $o<sizeof($data); $o++)
                                       {

                                                 if($data[$o]->start == $busstop[$i])
                                                 {


                                                          $avgSpeed = $avgSpeed + $data[$o]->avg_speed;
                                                          $avgTime = $avgTime + $data[$o]->avg_time;
                                                          $counter = $counter + 1;
                                                          $getDay = $data[$o]->day;
                                                          $busstopid_previous = $data[$o]->bus_stop_id_previous;
                                                          $busstopid_next = $data[$o]->bus_stop_id_next;
                                                 }
                                       }

                                       $avgSpeedFinal = $avgSpeed / $counter;
                                       $avgTimeFinal = $avgTime / $counter;

                                       $calculateHistoricDataAverage_Query5 = DB::table('avg_speed_calculated')
                                                                                                            ->insert([
                                                                                                                    'bus_stop_id_previous' => $busstopid_previous,
                                                                                                                    'bus_stop_id_next' => $busstopid_next,
                                                                                                                    'route_id' => $routeno,
                                                                                                                    'bus_service_no' => $bus_service_no,
                                                                                                                    'start' => $busstop[$i],
                                                                                                                    'end' => $busstop[$i+1],
                                                                                                                    'avg_speed' => $avgSpeedFinal,
                                                                                                                    'avg_time' => $avgTimeFinal,
                                                                                                                    'day' => $getDay
                                                                                                                     ]);


                             }

                             for($l=0; $l<sizeof($data3); $l++)
                             {
                                       if(!empty($data3) && $data3[$l]->route_id != $routeno)
                                       {

                                                 $rid = $data3[$l]->route_id;
                                                 $busno = $data3[$l]->bus_service_no;
                                                 $busidPrev = $data3[$l]->bus_stop_id_previous;
                                                 $busidNext = $data3[$l]->bus_stop_id_next;
                                                 $startLoc = $data3[$l]->start;
                                                 $endLoc = $data3[$l]->end;
                                                 $avgSpdDB = $data3[$l]->avg_speed;
                                                 $avgTimeDB = $data3[$l]->avg_time;
                                                 $theDay = $data3[$l]->day;
                                                 $theTimeStamp = $data3[$l]->time_stamp;

                                                 $calculateHistoricDataAverage_Query4 = DB::table('avg_speed_calculated')
                                                                                                            ->insert([
                                                                                                                    'route_id' => $rid,
                                                                                                                    'bus_service_no' => $busno,
                                                                                                                    'bus_stop_id_previous' => $busidPrev,
                                                                                                                    'bus_stop_id_next' => $busidNext,
                                                                                                                    'start' => $startLoc,
                                                                                                                    'end' => $endLoc,
                                                                                                                    'avg_speed' => $avgSpdDB,
                                                                                                                    'avg_time' => $avgTimeDB,
                                                                                                                    'day' => $theDay,
                                                                                                                    'time_stamp' => $theTimeStamp
                                                                                                                     ]);
                                       }
                             }
                    }

										public function checkconnection() {
												print("Sucess");
										}

                    public function calculateHistoricData()
                    {
                             $busroute[0] = '1';
                             $busroute[1] = '2';
                             $routeID = 1000;
                             $routeno;
                             $busradius = self::setRadius()['busradius'];

                             //date_default_timezone_set('Asia/Singapore');

                             for ($g = 0;  $g <sizeof($busroute); $g++)
                             {
                                       $routeno = $g+1;

                                       if ($g == 0)
                                       {
                                                $routeID = 1000;
                                       }

                                       else
                                       {
                                                $routeID = 1037;
                                       }

                                       print("ROUTENO = " .$routeno. "\r\n");

                                       $dateRangeStart = "2015-10-19 00:00:00";
                                       $dateRangeEnd = "2015-10-22 00:00:00";

                                       $calculateHistoricData_Query = DB::table('bus_route')
                                                                                                 ->select('bus_route.bus_service_no', 'location_data.route_id', 'location_data.latitude', 'location_data.longitude', 'location_data.time')
                                                                                                 ->join('route', 'route.route_id', '=', 'bus_route.route_id')
                                                                                                 ->join('location_data', 'bus_route.bus_id', '=', 'location_data.bus_id')
                                                                                                 ->where('location_data.route_id', $routeno)
                                                                                                 ->where('location_data.time', '>', $dateRangeStart)
                                                                                                 ->where('location_data.time', '<', $dateRangeEnd)
                                                                                                 ->orderBy('time')
                                                                                                 ->distinct()
                                                                                                 ->get();

                                       $data = array();

                                       foreach($calculateHistoricData_Query as $singleset)
                                       {
                                                $data[] = $singleset;
                                       }

                                       $calculateHistoricData_Query2 = DB::table('manual_avg_speed')
                                                                                                           ->select('start', 'end', 'distance', 'avg_time')
                                                                                                           ->where('route_id',$routeno)
                                                                                                           ->get();

                                       $data2 = array();

                                       foreach($calculateHistoricData_Query2 as $singleset2)
                                       {
                                                $data2[] = $singleset2;
                                       }

                                       if (!empty($data))
                                       {

                                                $totaldistance = array();
                                                $busstop = self::getRoute($routeno,1);
                                                $busstopKM = self::getRoute($routeno,2);

                                                for($e=0; $e<sizeof($busstop)-1; $e++)
                                                {
                                                          $segmentBusStop[] = $busstop[$e] ."\t". $busstop[$e+1] ."\t". $busstopKM[$e];
                                                }

                                                for($i=0;$i<sizeof($data)-1; $i++)
                                                {
                                                          $nextBusStop = -1;
                                                          $previousBusStop = -1;
                                                          $databaseRecord = $data[$i]->latitude.",".$data[$i]->longitude;

																													//BEGIN 20th MAY 2024
																													//Use Global variable to read the polyline content once only
																													//$filecontent = file_get_contents('../data/'.$data[$i]->bus_service_no.'.json');
																													$globalVariable = view()->shared('bus_polyline');
																													$filecontent = $globalVariable[$data[$i]->bus_service_no];
																													//end 20th MAY 2024

                                                          $json1 = json_decode($filecontent, true);
                                                          $busroutecoords = $json1[$routeno]['route'];

                                                          $updateLocation = explode(',',$databaseRecord);

                                                          $newLocation = self::Ian_closepointonroute($data[$i]->bus_service_no,$data[$i]->route_id,$updateLocation,$busradius);

                                                          if($newLocation!=null)
                                                          {
                                                                    $delimLocation=explode(",",$newLocation);
                                                                    $newEditLocation = $delimLocation[0].",".$delimLocation[1];

                                                                    $pointA[0] = $delimLocation[0];
                                                                    $pointA[1] = $delimLocation[1];
                                                                    $distancesA = array_map(function($itemA) use($pointA)
                                                                                                                    {
                                                                                                                              $itemA = explode(',',$itemA);
                                                                                                                              return self::caldistance($itemA, $pointA);
                                                                                                                    }, $busroutecoords);

                                                                    asort($distancesA);

                                                                    if(array_values($distancesA)[0] < 0.06)
                                                                    {
                                                                              for ($s=0; $s<sizeof($busroutecoords); $s++)
                                                                              {
                                                                                       if (trim($busroutecoords[key($distancesA)]) == trim($busroutecoords[$s]))
                                                                                       {
                                                                                                 $uploadedlocation = $s;
                                                                                       }
                                                                              }

                                                                              for($x=$uploadedlocation; $x<sizeof($busroutecoords); $x++)
                                                                              {
                                                                                       $continue = 0;

                                                                                       for($k=0; $k<sizeof($busstop); $k++)
                                                                                       {
                                                                                                 if(trim($busstop[$k])==trim($busroutecoords[$x]))
                                                                                                 {
                                                                                                           $continue = 1;
                                                                                                           $nextBusStop = $busstop[$k];

                                                                                                           if($k>0)
                                                                                                           {
                                                                                                                    $previousBusStop = $busstop[$k-1];
                                                                                                           }

                                                                                                           if($k<sizeof($busstop)-1)
                                                                                                           {
                                                                                                                    if(trim($nextBusStop) == trim($newEditLocation))
                                                                                                                    {
                                                                                                                              $previousBusStop = $nextBusStop;
                                                                                                                              $nextBusStop = $busstop[$k+1];
                                                                                                                    }
                                                                                                           }
                                                                                                 }
                                                                                       }

                                                                                       if($continue == 1)
                                                                                       {
                                                                                                 break;
                                                                                       }

                                                                              }

                                                                              $storeData[] = $newEditLocation ."\t". $previousBusStop ."\t". $nextBusStop ."\t". $data[$i]->time;
                                                                    }
                                                          }
                                                }

                                                $afterSegFirstBusStop = explode("\t",$segmentBusStop[0]);
                                                print("ROUTE ID: " .$routeID. "\r\n");

                                                if($afterSegFirstBusStop[0] == $busstop[0])
                                                {
                                                          for($z=0; $z<sizeof($data2); $z++)
                                                          {
                                                                    if(trim($data2[$z]->start) == trim($afterSegFirstBusStop[0]) && trim($data2[$z]->end) == trim($afterSegFirstBusStop[1]))
                                                                    {
                                                                              if($data2[$z]->avg_time != 0)
                                                                              {
                                                                                       $calSpeed3 = $data2[$z]->distance / ($data2[$z]->avg_time/3600);
                                                                                       $calSpeed3 = $calSpeed3;
                                                                              }

                                                                              else
                                                                              {
                                                                                       $calSpeed3 = 0;
                                                                              }

                                                                              $getDay3 = date('l', strtotime($data[$i]->time));
                                                                              print("PREVIOUS BUS STOP = " .$data2[$z]->start. "\r\n");
                                                                              print("NEXT BUS STOP = " .$data2[$z]->end. "\r\n");
                                                                              print("DISTANT = ".$data2[$z]->distance. "\r\n");
                                                                              print("AVERAGE TIME = " .$data2[$z]->avg_time. "\r\n");
                                                                              print("DAY: " .$getDay3. "\r\n");
                                                                              print("SPEED: " .$calSpeed3. "\r\n\r\n");

                                                                              $routeID3 = $routeID+1;

                                                                              $calculateHistoricData_Query6 = DB::table('avg_spd')
                                                                                                                                        ->insert([
                                                                                                                                                 'route_id' => $routeno,
                                                                                                                                                 'bus_service_no' => $data[$i]->bus_service_no,
                                                                                                                                                 'bus_stop_id_previous' => $routeID,
                                                                                                                                                 'bus_stop_id_next' => $routeID3,
                                                                                                                                                 'start' => $afterSegFirstBusStop[0],
                                                                                                                                                 'end' => $afterSegFirstBusStop[1],
                                                                                                                                                 'distance' => $data2[$z]->distance,
                                                                                                                                                 'avg_speed' => $calSpeed3,
                                                                                                                                                 'avg_time' => $data2[$z]->avg_time,
                                                                                                                                                 'day' => $getDay3,
                                                                                                                                                 'date_time' => $data[$z]->time
                                                                                                                                                  ]);

                                                                    }
                                                          }

                                                          $routeID = $routeID + 1;
                                                }

                                                for($v=1; $v<sizeof($segmentBusStop);$v++)
                                                {
                                                          $afterSegBusStop = explode("\t",$segmentBusStop[$v]);
                                                          $storeLocation = array();

                                                          for($h=0; $h<sizeof($storeData);$h++)
                                                          {
                                                                    $getStoredData = explode("\t", $storeData[$h]);

                                                                    if($getStoredData[1] == $afterSegBusStop[0] && $getStoredData[2] == $afterSegBusStop[1])
                                                                    {
                                                                              $storeLocation[] = $getStoredData[0] ."\t". $getStoredData[3];
                                                                    }
                                                          }

                                                          print("BUS STOP ID: " .$routeID. "\r\n");

                                                          if(sizeof($storeLocation) > 1)
                                                          {
                                                                    $greatestPrevious = -1;
                                                                    $greatestNext = -1;
                                                                    $counterTest = 0;

                                                                    for($u=0; $u<sizeof($storeLocation); $u++)
                                                                    {
                                                                              $getStoreLocation = explode("\t", $storeLocation[$u]);
                                                                              $useVal1 = explode(",", $afterSegBusStop[0]);
                                                                              $useVal2 = explode(",", $getStoreLocation[0]);
                                                                              $useVal3 = explode(",", $afterSegBusStop[1]);
                                                                              $calDistPolyBusPrevious = 0;

                                                                              $calDistPolyBusPrevious = $calDistPolyBusPrevious + self::caldistance($useVal1,$useVal2);

                                                                              if($greatestPrevious == -1)
                                                                              {
                                                                                       $greatestPrevious = $calDistPolyBusPrevious;
                                                                                       $storeDataFinalPrevious = $storeLocation[$u] ."\t". $greatestPrevious;
                                                                              }

                                                                              else if($calDistPolyBusPrevious < $greatestPrevious)
                                                                              {
                                                                                       $greatestPrevious = $calDistPolyBusPrevious;
                                                                                       $storeDataFinalPrevious = $storeLocation[$u] ."\t". $greatestPrevious;
                                                                              }

                                                                              $calDistPolyBusNext = 0;

                                                                              $calDistPolyBusNext = $calDistPolyBusNext + self::caldistance($useVal2,$useVal3);

                                                                              if($greatestNext == -1)
                                                                              {
                                                                                       $greatestNext = $calDistPolyBusNext;
                                                                                       $storeDataFinalNext = $storeLocation[$u] ."\t". $greatestNext;
                                                                              }

                                                                              else if($calDistPolyBusNext < $greatestNext)
                                                                              {
                                                                                       $greatestNext = $calDistPolyBusNext;
                                                                                       $storeDataFinalNext = $storeLocation[$u] ."\t". $greatestNext;
                                                                              }
                                                                    }

                                                                    $getStoreValuePrevious = explode("\t", $storeDataFinalPrevious);
                                                                    $getStoreValueNext = explode("\t", $storeDataFinalNext);
                                                                    $getValTime3 = explode(",", $getStoreValuePrevious[0]);
                                                                    $getValTime4 = explode(",", $getStoreValueNext[0]);
                                                                    $calDist = 0;
                                                                    $calDist = $calDist + self::caldistance($getValTime3,$getValTime4);
                                                                    $getDataTime = strtotime($getStoreValuePrevious[1]);
                                                                    $getDataTime2 = strtotime($getStoreValueNext[1]);
                                                                    $timeDiff = ($getDataTime2 - $getDataTime) / 3600;

                                                                    if($calDist > 0.1 && $timeDiff > 0)
                                                                    {
                                                                              print("BUS STOP ID: " .$routeID. "\r\n\r\n");
                                                                              print("Condition Met!\r\n");
                                                                              print("STORED VALUE FIRST = " .$getStoreValuePrevious[0]. "\r\n");
                                                                              print("STORED VALUE LAST = " .$getStoreValueNext[0]. "\r\n");
                                                                              print("STORED TIME FIRST = " .$getStoreValuePrevious[1]. "\r\n");
                                                                              print("STORED TIME LAST = " .$getStoreValueNext[1]. "\r\n");
                                                                              print("NO. OF UPLOADS = " .sizeof($storeLocation). "\r\n");
                                                                              print("PREVIOUS BUSSTOP = " .$afterSegBusStop[0]. "\r\n");
                                                                              print("NEXT BUSSTOP = " .$afterSegBusStop[1]. "\r\n");
                                                                              print("DISTANT = " .$calDist. "\r\n");
                                                                              print("TIME DIFFERENCE = " .($timeDiff*3600). "\r\n");

                                                                              $calSpeed = $calDist / $timeDiff;
                                                                              print("AVERAGE SPEED = " .$calSpeed. "\r\n");

                                                                              $calAvgTime = $afterSegBusStop[2] / $calSpeed;
                                                                              $calAvgTime = ($calAvgTime*3600);
                                                                              print("AVERAGE TIME = " .$calAvgTime. "\r\n");

                                                                              $getDay = date('l', strtotime($getStoreValuePrevious[1]));
                                                                              print("DAY = " .$getDay. "\r\n\r\n");
                                                                              $routeID2 = $routeID+1;

                                                                              $calculateHistoricData_Query3 = DB::table('avg_spd')
                                                                                                                                        ->insert([
                                                                                                                                                 'route_id' => $routeno,
                                                                                                                                                 'bus_service_no' => $data[$i]->bus_service_no,
                                                                                                                                                 'bus_stop_id_previous' => $routeID,
                                                                                                                                                 'bus_stop_id_next' => $routeID2,
                                                                                                                                                 'start' => $afterSegBusStop[0],
                                                                                                                                                 'end' => $afterSegBusStop[1],
                                                                                                                                                 'distance' => $calDist,
                                                                                                                                                 'avg_speed' => $calSpeed,
                                                                                                                                                 'avg_time' => $calAvgTime,
                                                                                                                                                 'day' => $getDay,
                                                                                                                                                 'date_time' => $getStoreValuePrevious[1]
                                                                                                                                                  ]);
                                                                    }

                                                                    else
                                                                    {
                                                                              for($f=0; $f<sizeof($data2); $f++)
                                                                              {

                                                                                       if(trim($data2[$f]->start) == trim($afterSegBusStop[0]) && trim($data2[$f]->end) == trim($afterSegBusStop[1]))
                                                                                       {
                                                                                                 print("Condition NOT Met!\r\n");
                                                                                                 print("STORED VALUE FIRST = " .$getStoreValuePrevious[0]. "\r\n");
                                                                                                 print("STORED VALUE LAST = " .$getStoreValueNext[0]. "\r\n");
                                                                                                 print("STORED TIME FIRST = " .$getStoreValuePrevious[1]. "\r\n");
                                                                                                 print("STORED TIME LAST = " .$getStoreValueNext[1]. "\r\n");
                                                                                                 print("NO. OF UPLOADS = " .sizeof($storeLocation). "\r\n");
                                                                                                 print("PREVIOUS BUSSTOP = " .$afterSegBusStop[0]. "\r\n");
                                                                                                 print("NEXT BUSSTOP = " .$afterSegBusStop[1]. "\r\n");
                                                                                                 print("DISTANT = " .$data2[$f]->distance. "\r\n");
                                                                                                 print("TIME DIFFERENCE = " .$data2[$f]->avg_time. "\r\n");
                                                                                                 $calSpeed2 = $data2[$f]->distance / ($data2[$f]->avg_time/3600);
                                                                                                 $calSpeed2 = $calSpeed2;
                                                                                                 print("AVERAGE TIME = " .$data2[$f]->avg_time. "\r\n");
                                                                                                 $getDay2 = date('l', strtotime($getStoreValuePrevious[1]));
                                                                                                 print("DAY: " .$getDay2. "\r\n");
                                                                                                 print("SPEED: " .$calSpeed2. "\r\n\r\n");

                                                                                                 $routeID2 = $routeID+1;

                                                                                                 $calculateHistoricData_Query4 = DB::table('avg_spd')
                                                                                                                                                           ->insert([
                                                                                                                                                                     'route_id' => $routeno,
                                                                                                                                                                     'bus_service_no' => $data[$i]->bus_service_no,
                                                                                                                                                                     'bus_stop_id_previous' => $routeID,
                                                                                                                                                                     'bus_stop_id_next' => $routeID2,
                                                                                                                                                                     'start' => $afterSegBusStop[0],
                                                                                                                                                                     'end' => $afterSegBusStop[1],
                                                                                                                                                                     'distance' => $data2[$f]->distance,
                                                                                                                                                                     'avg_speed' => $calSpeed2,
                                                                                                                                                                     'avg_time' => $data2[$f]->avg_time,
                                                                                                                                                                     'day' => $getDay2,
                                                                                                                                                                     'date_time' => $getStoreValuePrevious[1]
                                                                                                                                                                      ]);
                                                                                       }
                                                                              }
                                                                    }

                                                                    unset($storeLocation);
                                                                    $storeLocation = array();
                                                          }

                                                          else
                                                          {
                                                                    for($f=0; $f<sizeof($data2); $f++)
                                                                    {

                                                                              if(trim($data2[$f]->start) == trim($afterSegBusStop[0]) && trim($data2[$f]->end) == trim($afterSegBusStop[1]))
                                                                              {
                                                                                       if($data2[$f]->avg_time != 0)
                                                                                       {
                                                                                                 $calSpeed2 = $data2[$f]->distance / ($data2[$f]->avg_time/3600);
                                                                                                 $calSpeed2 = $calSpeed2;
                                                                                       }

                                                                                       else
                                                                                       {
                                                                                                 $calSpeed2 = 0;
                                                                                       }

                                                                                       $getDay2 = date('l', strtotime($data[$i]->time));
                                                                                       print("PREVIOUS BUS STOP = " .$data2[$f]->start. "\r\n");
                                                                                       print("NEXT BUS STOP = " .$data2[$f]->end. "\r\n");
                                                                                       print("DISTANT = ".$data2[$f]->distance. "\r\n");
                                                                                       print("AVERAGE TIME = " .$data2[$f]->avg_time. "\r\n");
                                                                                       print("DAY: " .$getDay2. "\r\n");
                                                                                       print("SPEED: " .$calSpeed2. "\r\n\r\n");

                                                                                       $routeID2 = $routeID+1;

                                                                                       $calculateHistoricData_Query5 = DB::table('avg_spd')
                                                                                                                                                  ->insert([
                                                                                                                                                           'route_id' => $routeno,
                                                                                                                                                           'bus_service_no' => $data[$i]->bus_service_no,
                                                                                                                                                           'bus_stop_id_previous' => $routeID,
                                                                                                                                                           'bus_stop_id_next' => $routeID2,
                                                                                                                                                           'start' => $afterSegBusStop[0],
                                                                                                                                                           'end' => $afterSegBusStop[1],
                                                                                                                                                           'distance' => $data2[$f]->distance,
                                                                                                                                                           'avg_speed' => $calSpeed2,
                                                                                                                                                           'avg_time' => $data2[$f]->avg_time,
                                                                                                                                                           'day' => $getDay2
                                                                                                                                                           ]);
                                                                              }
                                                                    }
                                                          }

                                                          $routeID = $routeID + 1;
                                                }

                                       }

                                       unset($segmentBusStop);
                                       unset($storeLocation);
                                       unset($data);
                                       unset($data2);
                             }
                    }

  public function NewtestgetKM(Request $request)
  {
          $busserviceno = $request->input('busserviceno');
          $routeno = $request->input('routeno');
          $arg1 = $request->input('arg1');
          $arg2 = $request->input('arg2');

          \Log::info("Request received", compact('busserviceno', 'routeno', 'arg1', 'arg2'));

          // Check if parameters exist
        if(!$busserviceno || !$routeno || !$arg1 || !$arg2){
          return response()-> json(['error'=> "Missing parameters"],400);
        }

        \Log::info("Received request", [
          'busserviceno' => $busserviceno,
          'routeno' => $routeno,
          'arg1' => $arg1,
          'arg2' => $arg2
      ]);

          //13 Aug 2020
          //map the coordinate to a location on the polyline
          $sourcePolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg1), 0.06);
          $destinationPolyLine = self::closepointonroute($busserviceno, $routeno, explode(',', $arg2), 0.06);
          $source = explode(',', $sourcePolyLine);
          $destination = explode(',', $destinationPolyLine);
          $arg1 = $source[0] . "," . $source[1];
          $arg2 = $destination[0] . "," . $destination[1];
          //End

          $totaldistance = self::getDistanceOnRoute($busserviceno, $routeno, $arg1, $arg2);

          return response(json_encode($totaldistance), 200);
  }


}
