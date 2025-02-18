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
