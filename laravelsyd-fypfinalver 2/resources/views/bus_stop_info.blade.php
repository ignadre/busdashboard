<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bus Stop Info</title>
<style>

@font-face {
    font-family: data-latin_font;
    src: url('{{ public_path('fonts/data-latin_font.tff') }}');
}

body {
   background-color: #FFFFFF;
}
.table-users{
    position: absolute;
    height: 100%;
    right: 0;
    bottom: auto;
    left: 0;
    top: -2.5%;
}
p {
  font-weight: bold;
  font-size: 125%;
}
.header {
   background-color: lightcyan;
   color: black;
   text-align: center;
   font-weight: bold;
   font-size: 230%;
   position: absolute;
   top: 8%;
   bottom: 0;
   left: 0;
   right: 0;
   padding-top: 0.5%;
}
table {
    position: absolute;
    top: 18%;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
}

th, td {
    text-align: center;
    color: #005555;
    font-size: 180%;
    width: 20%;
    padding: 1%;
}


tr:nth-child(odd){background-color: #AFEEEE;}
tr:nth-child(even){background-color: #FFFDD0;}


th {
    background-color: darkcyan;
    color: white;
}
</style>

</head>

<body onload="startScript()">
  <div class="table-users">
    <p id="clock"></p>
     <div class="header" id="stop_id" stop_id="{{$data['bus_stop_id']}}" num_bus="{{count($data['bus_data'])}}">{{$data['stop_name']}}</div>

     <table cellspacing="0">
        <tr>
           <th>Service</th>
           <th>Incoming</th>
           <th>Next Bus</th>
           <th>Destination</th>
        </tr>
        @foreach($data['bus_data'] as $key=>$value)

        <tr>
           <td>{{$value['bus_service_no']}}</td>
           <td id="eta{{$key}}" eta_date="{{$value['eta_date']}}" eta_grace_check="NA">{{$value['stop_eta']}}</td>
           <td id="eta_b{{$key}}" eta_date="{{$value['eta_date2']}}" eta_grace_check="NA">{{$value['stop_eta2']}}</td>

           <td id="route{{$key}}" route="{{$value['route']}}">{{$value['Destination']}}</td>
        </tr>
        @endforeach

     </table>



  </div>







</body>
<script>
var buses = 0;
function startScript()
{
  var t0 = setTimeout(refresh_eta, 30000);
  startTime();

}
function startTime() {
    buses = parseInt(document.getElementById("stop_id").getAttribute("num_bus"));
    var today = new Date();
    var months = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    var days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday","Friday",
    "Saturday"];
    var D = days[today.getDay()];
    var M = months[today.getMonth()];
    var Y = today.getFullYear();
    var d = today.getDate();
    var h = today.getHours();
    var m = today.getMinutes();
    var s = today.getSeconds();
    h = checkTime(h);
    m = checkTime(m);
    s = checkTime(s);
    var clock =D + ", " + d + " " + M + " " + " " + Y + " " + h + ":" + m + ":" + s;
    document.getElementById('clock').innerHTML = clock;
    // for (var i =0; i < buses; i++)
    // {
    //   var index_date = 'eta' + i;
    //   var index_route = 'route' + i;
    //   var bus_stop_id = document.getElementById("stop_id").getAttribute("stop_id");
    //   var eta_date = document.getElementById(index_date).getAttribute("eta_date");
    //   var bus_route = document.getElementById(index_route).getAttribute("route");
    //   var eta_grace_check = document.getElementById(index_date).getAttribute("eta_grace_check");
    //   if (eta_date == "NA")
    //   {
    //     if(eta_grace_check == "NA")
    //     {
    //       grace_time =new Date(today.getTime() +  (1 * 60000));
    //       eta_grace_check = grace_time;
    //       document.getElementById(index_date).setAttribute("eta_grace_check", eta_grace_check);
    //     }
    //     else
    //     {
    //       var grace_check = new Date(eta_grace_check);
    //       if(today >= grace_check)
    //         {
    //           refresh(bus_stop_id, bus_route, index_date);
    //           document.getElementById(index_date).setAttribute("eta_grace_check", "NA");
    //         }
    //     }
    //
    //   }
    //   else
    //   {
    //     var eta_check = new Date(eta_date);
    //     if(today >= eta_check)
    //     {
    //
    //       refresh(bus_stop_id, bus_route,index_date);
    //     }
    //   }
    //
    //
    // }

    var t = setTimeout(startTime, 500);
    // var t2 = setTimeout(refresh_eta, 30000);

}

function refresh_eta() {
  console.log("refresh_eta: " + document.getElementById('clock').innerHTML);
  var refresh_map = new Map();
  var bus_stop_id = document.getElementById("stop_id").getAttribute("stop_id");
  for (var i=0; i < buses; i++)
  {
      var index_route = 'route' + i;
      var key = 'key_' + i;
      var bus_route = document.getElementById(index_route).getAttribute("route")
      refresh_map.set(key, bus_route);
  }
  refresh_array = [];
  for (const entry of refresh_map.entries()) {
    refresh_array.push(entry);
  }
  //console.log(refresh_array[0].length);
  refresh_eta_post(bus_stop_id, refresh_array);
}

function checkTime(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
    return i;
}

function refresh_eta_post(bus_stop_id, refresh_array)
{
  //console.log("size :" + refresh_map.size);
  var req = new XMLHttpRequest();
  // req.open("POST", "https://laravelsyd-fypfinalver.herokuapp.com/getBusStopInfo_refresh", true);
  req.open("POST", "getBusStopInfo_refresh", true);
  req.setRequestHeader('Content-Type', 'application/json');
  req.send(JSON.stringify({
      'bus_stop_id': bus_stop_id,
      'refresh_array': refresh_array
  }));
  req.onload = function() {
    var resp = this.responseText;
    var jsonResponse = JSON.parse(resp);
    for (var value in jsonResponse)
    {
      console.log(value, jsonResponse[value]);

      var n = jsonResponse[value].key.indexOf("_") + 1;
      var get_id =  jsonResponse[value].key.substr(n,jsonResponse[value].key.length);
      console.log("id :" + get_id);
      var index_eta = "eta" + get_id;
      var index_eta2 = "eta_b" + get_id;
      var eta_1 = "NA";
      var eta_2 = "NA";

      if (jsonResponse[value].stop_eta != 'NA')
      {
        if(jsonResponse[value].stop_eta > 1)
        {
          eta_1 = jsonResponse[value].stop_eta + " mins";
        }
        else
        {
            eta_1 = "ARR";
        }
      }

      if (jsonResponse[value].stop_eta2 != 'NA')
      {
        if(jsonResponse[value].stop_eta2 > 1)
        {
          eta_2 = jsonResponse[value].stop_eta2 + " mins";
        }
        else
        {
            eta_2 = "ARR";
        }
      }
      document.getElementById(index_eta).innerHTML = eta_1;
      document.getElementById(index_eta2).innerHTML = eta_2;
      var t2 = setTimeout(refresh_eta, 30000);
    }

  }
}

function refresh(bus_stop_id, bus_route, index_date) {


    var req = new XMLHttpRequest();
    // req.open("POST", "https://laravelsyd-fypfinalver.herokuapp.com/getBusStopInfo_refresh", true);
    req.open("POST", "getBusStopInfo_refresh", true);
    req.setRequestHeader('Content-Type', 'application/json');
    req.send(JSON.stringify({
        'bus_stop_id': bus_stop_id,
        'route_id': bus_route
    }));
    req.onload = function() {
      var resp = JSON.parse(this.responseText);
      if (resp == "NA")
      {
        var eta_date_text =  resp;
      }
      else
      {
        var n = resp.indexOf(",");
        var eta_date_text =  resp.substr(n,resp.length);
      }

      document.getElementById(index_date).innerHTML = eta_date_text;
      document.getElementById(index_date).setAttribute("eta_date", resp);
      document.getElementById(index_date).setAttribute("eta_grace_check", "NA");
    }
}

</script>

</html>
