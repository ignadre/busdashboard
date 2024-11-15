<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Johor Bus Arrival Dashboard</title>
  <style>
  @font-face {
    font-family: 'IdentityFont';
    src:  url('fonts/LTAIdentity.Medium.ttf');
  }
    body {
      font-family: IdentityFont, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #616161;
    }
    .container {
      max-width: 800px;
      margin: 20px auto;
      padding: 20px;
      background-color: #111324;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    h1 {
      font-size: 35px;
      text-align: center;
      margin-bottom: 10px;
      color: #E28413;
    }
    .bus-stop-info {
      text-align: center;
      margin-bottom: 20px;
    }
    .current-date-time {
      text-align: center;
      font-size: 20px;
      margin-bottom: 25px;
      color: #ffffff;
    }
    .weather-info {
      text-align: center;
      margin-bottom: 20px;
      color: #6c757d;
    }
    .bus-list-header {
      display: flex;
      justify-content: space-between;
      align-items: left;
      padding: 10px 15px;
      background-color: #E28413;
      border-radius: 5px;
      font-size: 20px;
    }
    .bus-list-header > div {
      flex: 1;
      text-align: left;
      color: #FFFCF2;
    }
    .bus-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-align: center;
      padding: 5px;
      font-size: 25px;
      border-bottom: 1px solid #ccc;
    }
    .bus-item:last-child {
      border-bottom: none;
    }
    .bus-item > div {
      flex: 1;
      text-align: left;
      /*border: 1px solid red;*/
    }
    .bus-name {
      font-weight: bold;
      align-items: center;
      text-align: center;
    }
    .destination {
      color: #ffffff;
      align-items: left;
      text-align: left;
    }
    .arrival-time {
      color: #ffffff;
      display: flex;
      align-items: center;
      /*border: 3px solid green;*/
    }
    .next-departure {
      color: #ffffff;
      display: flex;
      align-items: left;
      text-align: left;
      /*border: 3px solid green;*/
    }
    .live-icon {
      width: 20px;
      height: 20px;
      margin-right: 5px;
      animation: pulse 1.5s infinite alternate;
    }
    .timetable-icon {
      height: 15px;
      margin-right: 0px;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      100% { transform: scale(2); }
    }
    .operator-logo {
      width: 60px;
      height: auto;
    }
    .footer {
        text-align: center;
        padding-top: 20px;
        border-top: 1px solid #ccc;
        margin-top: auto;
        background-color: #f8f9fa;
        width: 100%;
        position: fixed;
        bottom: 0;
        left: 10;
      }
    .footer img {
      max-width: 550px;
      height: 80px;
    }

    .delay {
    color: red;
    font-weight: bold;
    font-size: 16px;
    margin-left: 10px;
  }

  </style>
</head>
<body>

<div class="container">
  <h1 id="bus_stop_id" bus_stop_id="{{$data['bus_stop_id']}}">{{$data['stop_name']}}</h1>
  <!--<h1>Hospital Kulai</h1>-->
  <!--<div class="weather-info" id="weather-info"></div>-->
  <div class="current-date-time" id="current-date-time"></div>
  <div id="mydiv">
  <div class="bus-list-header">
  <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Bus Service</div>
  <div>&nbsp;&nbsp;&nbsp;&nbsp;Next Arrival</div>
  <div>&nbsp;&nbsp;&nbsp;&nbsp;Next Departure</div>
  <div>&nbsp;&nbsp;&nbsp;&nbsp;Destination</div>
  </div>
  <ul class="bus-list">
    @foreach($data['bus_data'] as $key=>$value)
    <li class="bus-item">
      <div class="bus-item">
        <div>
          <!--<img class="operator-logo" src="images/mybas.png" alt="Operator Logo">-->
          <span class="bus-name">
            <svg width="110" height="55" xmlns="http://www.w3.org/2000/svg">
              <g>
                <rect x="0" y="0" width="100" height="55" fill="#E28413" rx="15" ry="15"></rect>
                @if(strpos($value['bus_service_no'], 'P') !== false)
                <text x="14" y="39" font-family="IdentityFont" font-size="35" fill="white">{{$value['bus_service_no']}}</text>                
                @else
                  <text x="22" y="39" font-family="IdentityFont" font-size="35" fill="white">{{$value['bus_service_no']}}</text>
                @endif
              </g>
            </svg>
          </span>
        </div>
        <div>
          <span class="arrival-time" id="eta{{$key}}" eta_date="{{$value['eta_date']}}" eta_grace_check="NA">
            {{$value['stop_eta']}}
            @if ($value['live'] > 0)
              <img class="live-icon" src="images/live2.png" alt="Live Icon">
            @endif
          </span>
        </div>
        <div>
          <span class="next-departure" id="departure{{$key}}" departure_date="{{$value['departure_date']}}">
            {{$value['departure']}}
            @if (strcmp($value['departure_date'], "NA") !== 0)
              <img class="timetable-icon" src="images/clock.png" alt="Timetable Icon">
            @endif
          </span>
          <span class="next-departure" id="departure_b{{$key}}" departure_date="{{$value['departure_date2']}}">{{$value['departure2']}}</span>
        </div>
        <div>
          <span class="destination" id="route{{$key}}" route="{{$value['route']}}">{{$value['Destination']}}</span>
        </div>
      </div>
    </li>
    @endforeach
  </ul>
  </div>
</div>
<div class="footer">
  <marquee>Disclaimer: This passenger information system is currently under testing and not all buses' arrival times are displayed. The accuracy of the bus ETAs may be affected by the traffic conditions. For any feedback and enquiries, please contact: aseanivoiot@yahoo.com Sistem maklumat penumpang ini sedang diuji dan bukan semua masa ketibaan bas akan dipaparkan. Ketepatan masa ketibaan bas mungkin dipengaruhi oleh keadaan lalulintas semasa. Sebarang maklum balas dan pertanyaan, sila hubungi: aseanivoiot@yahoo.com 此乘客信息系统目前正在测试中，并未显示所有公交车的抵达时间。巴士预计抵达时间的准确性可能会受到交通状况的影响。如有任何反馈或疑问，请联系：aseanivoiot@yahoo.com இந்த பயணிகள் தகவல் அமைப்பு தற்போது சோதனையில் உள்ளது மேலும் அனைத்து பேருந்துகளின் வருகை நேரமும் காட்டப்படவில்லை. ட்ராஃபிக் நிலைமைகளால் பேருந்தின் மதிப்பிடப்பட்ட வருகை நேரங்களின் துல்லியம் பாதிக்கப்படலாம். ஏதேனும் கருத்து மற்றும் விசாரணைகளுக்கு, தயவுசெய்து தொடர்பு கொள்ளவும்: aseanivoiot@yahoo.com
  </marquee>
  <img src="images/logo4.png" alt="City Council Logo">
</div>

<script>

    function refreshDiv() {
          var bus_stop_id = document.getElementById("bus_stop_id").getAttribute("bus_stop_id");
          var xhr = new XMLHttpRequest();
          xhr.open('POST', 'getBusTerminalInfoRefresh', true);
          xhr.setRequestHeader('Content-Type', 'application/json');
          //alert(bus_stop_id);
          xhr.onreadystatechange = function() {
              //alert("statechange!");
              //consolo.log(xhr.responseText);
              //alert(xhr.readyState);
              if (xhr.readyState === 4 && xhr.status === 200) {
                  //alert(xhr.responseText);
                  document.getElementById('mydiv').innerHTML = xhr.responseText;
              }
          };
          xhr.send(JSON.stringify({
              'bus_stop_id': bus_stop_id,
          }));
          //xhr.send();
    }

    function updateDateTime() {
      const now = new Date();
      const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      const day = days[now.getDay()];
      const date = now.getDate();
      const month = months[now.getMonth()];
      const year = now.getFullYear();
      let hour = now.getHours();
      const minute = String(now.getMinutes()).padStart(2, '0');
      const second = String(now.getSeconds()).padStart(2, '0');
      const ampm = hour >= 12 ? 'PM' : 'AM';
      hour = hour % 12 || 12;
      const dateTimeString = `${day}, ${date}${getOrdinalSuffix(date)} ${month} ${year}, ${hour}:${minute}:${second} ${ampm}`;
      document.getElementById('current-date-time').textContent = `${dateTimeString}`;
    }


    function getOrdinalSuffix(date) {
      if (date > 3 && date < 21) return 'th';
      switch (date % 10) {
        case 1: return "st";
        case 2: return "nd";
        case 3: return "rd";
        default: return "th";
      }
    }

    function updateDelays() {
        // Get all bus items
        var busItems = document.querySelectorAll('.bus-item');
        
        busItems.forEach(function(item, index) {
            var arrivalTimeStr = document.querySelector(`#eta${index}`).textContent;
            var departureTimeStr = document.querySelector(`#departure${index}`).textContent;
            
            // Convert the arrival and departure times to Date objects
            var arrivalTime = convertToDate(arrivalTimeStr);
            var departureTime = convertToDate(departureTimeStr);

            // Check if the arrival time is later than the departure time
            if (arrivalTime && departureTime && arrivalTime > departureTime) {
                var delay = calculateDelay(arrivalTime, departureTime);
                // Show the delay next to the departure time
                var delayText = document.createElement('span');
                delayText.classList.add('delay');
                delayText.textContent = ` (Delayed by ${delay} min)`;
                document.querySelector(`#departure${index}`).appendChild(delayText);
            }
        });
    }

      // Function to convert time string (e.g., "4:30 PM") to Date object
      function convertToDate(timeStr) {
        var now = new Date();
        var [time, period] = timeStr.split(' ');
        var [hours, minutes] = time.split(':').map(Number);
        
        if (period === 'PM' && hours !== 12) {
            hours += 12; // Convert PM times to 24-hour format
        } else if (period === 'AM' && hours === 12) {
            hours = 0; // Convert 12 AM to 00:00
        }
        
        var date = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes, 0);
        return date;
    }

    // Function to calculate the delay in minutes
    function calculateDelay(arrivalTime, departureTime) {
        var diffMs = arrivalTime - departureTime; // Difference in milliseconds
        var diffMins = Math.floor(diffMs / 60000); // Convert to minutes
        return diffMins;
    }


    // Refresh the div every 2 seconds (adjust the interval as needed)
    //alert("call refresh");
    //refreshDiv();
    setInterval(refreshDiv, 30000); // 2000 milliseconds (2 seconds)
    updateDateTime();
    setInterval(updateDateTime, 1000); // Update every second
    //refreshDiv();

</script>

</body>
</html>
