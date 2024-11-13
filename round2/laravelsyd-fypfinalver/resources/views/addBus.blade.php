<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bus Simulator</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Raleway', sans-serif;
                font-weight: 100;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

			input[type=text], select {
    width: 100%;
    padding: 12px 20px;
    margin: 8px 0;
    display: inline-block;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}

input[type=submit] {
    width: 100%;
    background-color: #4CAF50;
    color: white;
    padding: 14px 20px;
    margin: 8px 0;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
form, label {
	color: Black;
	font-size: 20px;
	font-weight: 1000;
}

input[type=submit]:hover {
    background-color: #45a049;
}

.myForm{
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
}


        </style>
    </head>
    <body>


            <div class="content">



				<div class="myForm">
				<div class="title m-b-md">
                    Add new Bus
                </div>
				<form action="https://laravelsyd-fypfinalver.herokuapp.com/" method="post">

				  <label for="bus_id">Bus id:</label>
				  <input id="bus_id" type="number" name="bus_id" value="">
				  <br><br>
				  <label for="plate_no">Plate Number:</label>
				  <input id="plate_no" type="text" name="plate_no" value="">
				  <br><br>
				  <label for="beacon_mac">Beacon Mac Address:</label>
				  <input id="beacon_mac" type="text" name="beacon_mac" value="">
				  <br><br>
          <label for="bus_service_no">Bus Service Number:</label>
				  <input id="bus_service_no" type="text" name="bus_service_no" value="">
				  <br><br>
          <label for="route_id">Route id:</label>
          <select name="route_id">
            @foreach($data['bus_data'] as $key=>$value)
            <option value="{{$value['bus_service_no']}}">{{$value['bus_service_no']}}</option>
            @endforeach
          </select>
				  <input type="submit" value="Submit">
				</form>
				</div>
            </div>

    </body>
</html>
