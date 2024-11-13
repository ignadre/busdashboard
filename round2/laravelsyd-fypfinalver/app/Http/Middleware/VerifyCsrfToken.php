<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
		'/Ian_updateLocation',
		'/calculateETATest',
		'/calculateETA',
		'/viewETATable',
		'/convertBustoptoNearestPolyLine',
		'/getData',
		'/getKM',
		'/testgetKM',
		'/testCal',
		'/pushCurrentData',
		'/getETA',
		'/getBusStopServices',
		'/getBusRoute',
		'/checkClosePointExist',
		'/calculateETAWin',
		'/calculateHistoricDataAverage',
		'/calculateHistoricData',
		'/ianTest',
		'/getETA_schedule',
		'/pi_insertlocation',
		'/getAllBeaconInfo',
		'/bus_insertlocation',
		'/syd_Cal',
		'/getKM_syd',
		'/getBusRoute',
		'/getBusRouteInfo',
		'/getBusService',
		'/getBusStop',
		'/getBusstopRoute',
		'/getLocationData',
		'/getNearbyBusStop',
		'/getBusstopList',
		'/getETA',
		'/updateLocation',
		'/getHistoryETA',
    '/getBusstopRoute_Test',
    '/getAllBusStop'
    ];
}
