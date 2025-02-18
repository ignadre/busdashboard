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
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #4d4d4d;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
    h1 {
      font-size: 60px;
      text-align: center;
      margin-bottom: 10px;
      color: #aef310;
    }
    .bus-stop-info {
      text-align: center;
      margin-bottom: 20px;
    }
    .current-date-time {
      text-align: center;
      font-size: 20px;
      margin-bottom: 20px;
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
      background-color: #aef310;
      border-radius: 5px;
      font-size: 15px;
    }
    .bus-list-header > div {
      flex: 1;
      text-align: left;
    }
    .bus-item {
      display: flex;
      justify-content: space-between;
      align-items: left;
      padding: 10px;
      font-size: 30px;
      border-bottom: 1px solid #ccc;
    }
    .bus-item:last-child {
      border-bottom: none;
    }
    .bus-item > div {
      flex: 1;
      text-align: left;
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
    .note {
      color: #d7ff00;
      align-items: left;
      text-align: left;
      font-size: 15px;
    }
    .arrival-time {
      color: #ffffff;
      display: flex;
      align-items: center;
    }
    .live-icon {
      width: 20px;
      height: 20px;
      margin-right: 5px;
      animation: pulse 1.5s infinite alternate;
    }
    .timetable-icon {
      height: 20px;
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
      max-width: 400px;
      height: 80px;
    }
  </style>
</head>
<body>

<div class="container">
  <h1 id="bus_stop_id" bus_stop_id="{{$data['bus_stop_id']}}">{{$data['bus_stop_id']}} &nbsp;&nbsp;&nbsp;&nbsp;{{$data['stop_name']}}</h1>
  <!--<h1>Hospital Kulai</h1>-->
  <!--<div class="weather-info" id="weather-info"></div>-->
  <div class="current-date-time" id="current-date-time"></div>
    <div class="bus-list-header">
      <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Bus Service</div>
      <div>Destination</div>
      <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Incoming</div>
      <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Next Arrival</div>
    </div>
    <div id="mydiv">
    <ul class="bus-list">
      @foreach($data['bus_data'] as $key=>$value)
      <li class="bus-item">
        <div>
          <!--<img class="operator-logo" src="images/mybas.png" alt="Operator Logo">-->
          <span class="bus-name">
            <svg width="90" height="55" xmlns="http://www.w3.org/2000/svg">
          <g>
            <rect x="0" y="0" width="80" height="50" fill="darkcyan" x="10" y="10" rx="15" ry="15"></rect>
            @if(strpos($value['bus_service_no'], 'P') !== false)
              <text x="14" y="34" font-family="IdentityFont" font-size="25" fill="white">{{$value['bus_service_no']}}</text>
            @else
              <text x="18" y="34" font-family="IdentityFont" font-size="25" fill="white">{{$value['bus_service_no']}}</text>
            @endif
          </g>
          </svg>
        </span>
        </div>
        <div>
          @if(strpos($value['bus_service_no'], 'P') !== 0)
              <span class="destination" id="route{{$key}}" route="{{$value['route']}}">{{$value['Destination']}}</span>
          @else
              <span class="destination" id="route{{$key}}" route="{{$value['route']}}">{{$value['Destination']}}</span>
              <span class="note"><br>FREE / PERCUMA / 免費 / இலவசம்</span>
          @endif
        </div>
        <div></div>
        <div>
          <span class="arrival-time" id="eta{{$key}}" eta_date="{{$value['eta_date']}}" eta_grace_check="NA">{{$value['stop_eta']}} &nbsp;
          @if (strcmp($value['stop_eta'], "NA") == 0)
              &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
          @endif
          @if ($value['live'] > 0)
              <img class="live-icon" src="images/live2.png" alt="Live Icon">
          @else
              @if (strcmp($value['stop_eta'], "NA") !== 0)
                <img class="timetable-icon" src="images/clock.png" alt="Timetable Icon">
              @endif
          @endif
          </span>
        </div>
        <div>
          <span class="arrival-time" id="eta_b{{$key}}" eta_date="{{$value['eta_date2']}}" eta_grace_check="NA">{{$value['stop_eta2']}} &nbsp;
            @if (strcmp($value['stop_eta2'], "NA") == 0)
                &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
            @endif
            @if ($value['live2'] > 0)
                <img class="live-icon" src="images/live2.png" alt="Live Icon">
            @else
                @if (strcmp($value['stop_eta2'], "NA") !== 0)
                  <img class="timetable-icon" src="images/clock.png" alt="Timetable Icon">
                @endif
            @endif
          </span>
        </div>
      </li>
      @endforeach
    </ul>
  </div>
</div>
<div class="footer">
  <marquee>Disclaimer: This passenger information system is currently under testing and not all buses' arrival times are displayed. The accuracy of the bus ETAs may be affected by the traffic conditions. For any feedback and enquiries, please contact: aseanivoiot@yahoo.com Sistem maklumat penumpang ini sedang diuji dan bukan semua masa ketibaan bas akan dipaparkan. Ketepatan masa ketibaan bas mungkin dipengaruhi oleh keadaan lalulintas semasa. Sebarang maklum balas dan pertanyaan, sila hubungi: aseanivoiot@yahoo.com 此乘客信息系统目前正在测试中，并未显示所有公交车的抵达时间。巴士预计抵达时间的准确性可能会受到交通状况的影响。如有任何反馈或疑问，请联系：aseanivoiot@yahoo.com இந்த பயணிகள் தகவல் அமைப்பு தற்போது சோதனையில் உள்ளது மேலும் அனைத்து பேருந்துகளின் வருகை நேரமும் காட்டப்படவில்லை. ட்ராஃபிக் நிலைமைகளால் பேருந்தின் மதிப்பிடப்பட்ட வருகை நேரங்களின் துல்லியம் பாதிக்கப்படலாம். ஏதேனும் கருத்து மற்றும் விசாரணைகளுக்கு, தயவுசெய்து தொடர்பு கொள்ளவும்: aseanivoiot@yahoo.com
  </marquee>
  <img src="images/logo2.png" alt="City Council Logo">
</div>

<script>

    function refreshDiv() {

          var bus_stop_id = document.getElementById("bus_stop_id").getAttribute("bus_stop_id");
          var xhr = new XMLHttpRequest();
          xhr.open('POST', 'getBusStopInfoRefresh', true);
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
