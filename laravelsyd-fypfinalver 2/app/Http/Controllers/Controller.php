<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {

        //Reading the polyline from data folder. Need to add for each bus service.
        $filecontent_T30 = file_get_contents('../data/T30.json');
        $filecontent_T31 = file_get_contents('../data/T31.json');
        $filecontent_P411 = file_get_contents('../data/P411.json');
        $filecontent_P211 = file_get_contents('../data/P211.json');
        $filecontent_PoolA = file_get_contents('../data/Pool A.json');
        $filecontent_P101 = file_get_contents('../data/P101.json');
        $filecontent_P103 = file_get_contents('../data/P103.json');
        $bus_list = [
  						'T30' => $filecontent_T30,
              'T31' => $filecontent_T31,
              'P411' => $filecontent_P411,
              'P211' => $filecontent_P211,
              'Pool A' => $filecontent_PoolA,
              'P101' => $filecontent_P101,
              'P103' => $filecontent_P103,
  					];
        // Set a global variable for all views
        view()->share('bus_polyline', $bus_list);
    }

}
