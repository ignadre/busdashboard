<!--<div id="mydiv" class="container">-->
<div id="mydiv">
  <div class="bus-list-header">
    <div>&nbsp;&nbsp;&nbsp;&nbsp;Bus Service<br>&nbsp;&nbsp;&nbsp;&nbsp;<i>Bas</i></div>
    <div>&nbsp;&nbsp;&nbsp;&nbsp;Destination<br>&nbsp;&nbsp;&nbsp;&nbsp;<i>Destinasi</i></div>
    <div>&nbsp;&nbsp;&nbsp;&nbsp;Next Arrival<br>&nbsp;&nbsp;&nbsp;&nbsp;<i>Ketibaan</i></div>
    <div>Scheduled Departure<br><i>Jadual Perlepasan</i></div>
  </div>
  <ul class="bus-list">
    @foreach($data['bus_data'] as $key=>$value)
    <li class="bus-item">
      <div class="bus-item">
        <div>
        <!--<img class="operator-logo" src="images/mybas.png" alt="Operator Logo">-->
        <span class="bus-name">
          <svg width="90" height="55" xmlns="http://www.w3.org/2000/svg">
        <g>
          <rect x="0" y="0" width="80" height="50" fill="darkcyan" x="10" y="10" rx="15" ry="15"></rect>
          @if(strpos($value['bus_service_no'], 'P') !== false)
            <text x="12" y="34" font-family="IdentityFont" font-size="25" fill="white">{{$value['bus_service_no']}}</text>
          @else
            <text x="18" y="34" font-family="IdentityFont" font-size="25" fill="white">{{$value['bus_service_no']}}</text>
          @endif
        </g>
        </svg>
      </span>
      </div>
      <div>
        <span class="destination" id="route{{$key}}" route="{{$value['route']}}">{{$value['Destination']}}</span>
      </div>
      <div>
        <span class="arrival-time" id="eta{{$key}}" eta_date="{{$value['eta_date']}}" eta_grace_check="NA">&nbsp;&nbsp;&nbsp;&nbsp;{{$value['stop_eta']}} &nbsp;

        @if ($value['live'] > 0)
            <img class="live-icon" src="images/live2.png" alt="Live Icon">
        @endif
        </span>
      </div>
      <div>
        <span class="next-departure" id="departure{{$key}}" departure_date="{{$value['departure_date']}}">{{$value['departure']}} &nbsp;
          @if (strcmp($value['departure_date'], "NA") !== 0)
            <img class="timetable-icon" src="images/clock.png" alt="Timetable Icon">
          @endif
        </span>
        <span class="next-departure" id="departure_b{{$key}}" departure_date="{{$value['departure_date2']}}">{{$value['departure2']}} &nbsp;</span>
      </div>
    </div>
    </li>
    @endforeach
  </ul>
</div>
