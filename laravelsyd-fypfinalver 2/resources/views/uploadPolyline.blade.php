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
                    Upload json file
                </div>

          <label for="routeID">Bus id:</label>
      		<input id="routeID" type="text" name="routeID" value="">
				  <button id="upload_widget" class="cloudinary-button" onclick="getRoute()">Upload files</button>
				  <br><br>
          <p id="test"></p>
				</div>
            </div>

    </body>
    <script src="https://widget.cloudinary.com/v2.0/global/all.js" type="text/javascript"></script>

<script type="text/javascript">
var route = "";
var path ="testingSyd/7/";
function getRoute() {
  console.log('btn click');
  route = document.getElementById("routeID").value;
}





document.getElementById("upload_widget").addEventListener("click", function(){
  console.log('testing :' + route);
  if(route == "")
  {
    document.getElementById("test").innerHTML = "route missing";
  }
  else {
    path = "testingSyd/7/" +route + "/";
    //path = "testingSyd/7/";
    var myWidget = cloudinary.createUploadWidget({
      cloudName: 'hsj2bliee',
      uploadPreset: 'k2xqd8id',
      folder: path }, (error, result) => {
        if (!error && result && result.event === "success") {
          console.log('Done! Here is the image info: ', result.info);
          console.log('path: ' + path);
          document.getElementById("test").innerHTML = "" + result.info.secure_url;
        }
      }
    )
    myWidget.open();
  }


  }, false);


</script>

</html>
