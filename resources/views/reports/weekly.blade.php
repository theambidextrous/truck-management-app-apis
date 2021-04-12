
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Weekly earning report</title>
<style>
#customers {
  font-family: Arial, Helvetica, sans-serif;
  border-collapse: collapse;
  width: 100%;
  font-size:12px;
}

#customers td, #customers th {
  border: 1px solid #ddd;
  padding: 5px;
  text-align:center!important;
}
.td-borderless{
  border: 0px solid #ddd!important;
  margin: 0px!important;
}

#customers tr:nth-child(even){background-color: #f2f2f2;}

#customers tr:hover {background-color: #ddd;}

#customers th {
  padding-top: 4px;
  padding-bottom: 4px;
  text-align: left;
  background-color: #036a6b;
  color: white;
}
</style>
</head>
<body>
<!-- Container -->
<!-- <div class="container-fluid invoice-container">  -->
  <!-- Header -->
  <header>
    <div class="row align-items-center">
    <table class="table" id="customers" width="800px">
      <tr class="td-borderless" style="background-color: #ffffff !important;">
        <td class="td-borderless" style="text-align:left!important;width:450px">
            {{$setup['company']}}<br>
            {{$setup['address']}}<br>
            {{$setup['city']}}, {{$setup['state']}} {{$setup['zip']}}<br>
            Phone #: {{$setup['phone']}}<br>
        </td>
        <!-- <td class="td-borderless" style="width:300px!important;"></td> -->
        <td class="td-borderless" style="text-align:right!important;width:350px">
            <span style="font-weight:700; font-size:20px">Statement</span><br>
            <span style="font-weight:700; font-size:20px">{{ $owner['company'] }}</span><br>
            <span style="font-weight:700; font-size:20px">{{ date('m/d/Y') }}</span><br>
            <span style="font-weight:700; font-size:20px">Truck #{{ $truck }}</span><br>
        </td>
      </tr>
      </table>      
    </div>
    <hr>
  </header>
  <!-- Main Content -->
    <p class="text-1 text-left text-dark custom-p">
    {{ $owner['company'] }}<br>
    {{ $owner['address'] }} <br>
    {{ $owner['city'] }}, {{ $owner['state'] }} {{ $owner['zip'] }}
    </p>
    <!-- Passenger Details -->
    <!-- TRIPS ================== -->
    <h4 class="text-4 mt-2"><b>Trips:</b></h4>
   <table class="table" id="customers" width="800px">
      <thead class="dark-head">
        <tr>
          <th>Trip. NO.</th>
          <th>Description</th>
          <th>Mileage</th>
          <th>Freight Amt</th>
          <th>Date</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        @if(count($trips))
          @foreach( $trips as $trp )
              <tr>
                  <td>{{ $trp['id'] }}</td>
                  <td>
                    {{ $trp['street'] .", ". $trp['city'] .", " . $trp['state'] }}
                    {{"  -  "}} 
                    {{ $trp['d_street'] .", ". $trp['d_city'] .", " . $trp['d_state'] }}</td>
                  <td>{{ $trp['miles'] }}</td>
                  <td>${{ $trp['rate'] }}</td>
                  <td>{{ date('m/d/Y', strtotime($trp['created_at'])) }}</td>
                  <td>${{ $trp['net'] }}</td>
              </tr>
          @endforeach
        @endif
        <tr>
          <td colspan="4" class="td-borderless"></td>
          <td><b>Total</b></td>
          <td><b class="color-dt">${{ $summations['a'] }}</b></td>
        </tr>
      </tbody>
    </table>
    <!-- DEDUCTIONS ======================= -->
    <h4 class="text-4 mt-2"><b>Deductions:</b></h4>
   <table class="table" id="customers" width="800px">
      <thead class="dark-head">
        <tr>
          <th  style="width:500px;">Description</th>
          <th>Date</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
      @if(count($deductions))
          @foreach( $deductions as $ded )
              <tr>
                  <td>{{ $ded['description'] }}</td>
                  <td>{{ date('m/d/Y', strtotime($ded['created_at'])) }}</td>
                  <td>${{ $ded['total'] }}</td>
              </tr>
          @endforeach
      @endif
        <tr>
          <td colspan="1" class="td-borderless"></td>
          <td><b>Total</b></td>
          <td><b class="color-dt">(${{ $summations['b'] }})</b></td>
        </tr>
      </tbody>
    </table>

    <!-- SCHEDULED DEDUCTIONS ======================= -->
    <h4 class="text-4 mt-2"><b>Scheduled Deductions:</b></h4>
   <table class="table" id="customers" width="800px">
      <thead class="dark-head">
          <tr>
              <th style="width:500px;">Description</th>
              <th>Date</th>
              <th>Amount</th>
          </tr>
      </thead>
      <tbody>
          @if(count($scheduled))
              @foreach( $scheduled as $sch )
                  <tr>
                      <td>{{ $sch['description'] }}</td>
                      <td>{{ date('m/d/Y', strtotime($sch['created_at'])) }}</td>
                      <td>${{ $sch['total'] }}</td>
                  </tr>
              @endforeach
          @endif
        <tr>
          <td colspan="1" class="td-borderless"></td>
          <td><b>Total</b></td>
          <td><b class="color-dt">(${{ $summations['d'] }})</b></td>
        </tr>
      </tbody>
    </table>

    <!-- FUEL CARD ======================= -->
    <h4 class="text-4 mt-2"><b>Fuel card:</b></h4>
    <table class="table" id="customers" width="800px">
      <thead class="dark-head">
        <tr>
          <th>Description</th>
          <th>City</th>
          <th>State</th>
          <th>Fuel($)</th>
          <th>Misc.($)</th>
          <th>Date</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        @if( count($fuel) )
          @foreach( $fuel as $_fuel )
              <tr>
                  <td>{{ $_fuel['description'] }}</td>
                  <td>{{ $_fuel['city'] }}</td>
                  <td>{{ $_fuel['state'] }}</td>
                  <td>${{ $_fuel['amount_f'] }}</td>
                  <td>${{ $_fuel['misc_amount_f'] }}</td>
                  <td>{{ date('m/d/Y', strtotime($_fuel['created_at'])) }}</td>
                  <td>${{ $_fuel['total'] }}</td>
              </tr>
          @endforeach
        @endif
        <tr>
          <td colspan="5" class="td-borderless"></td>
          <td><b>Total</b></td>
          <td><b class="color-dt">(${{ $summations['c'] }})</b></td>
        </tr>
      </tbody>
    </table>
    
    <!-- Fare Details -->
    <br>
   <table class="table" id="customers" width="800px">
      <tbody>
        <tr>
          <td style="width:450px;" class="td-borderless"></td>
          <td class=""><b>Check amount</b></td>
          <td class="color-dt"><b>${{ $summations['e'] }}</b></td>
        </tr>
      </tbody>
    </table>
  <!-- Footer -->
  <footer class="text-center">
    <br>
    <hr>
    <p><b>{{ $setup['company'] }}</b> | <b>{{$setup['address']}}</b> | <b>{{$setup['city']}}, {{$setup['state']}} {{$setup['zip']}}</b> <span style="text-align:right; float:right">{{ Config::get('app.name')}} | {{ Config::get('app.url')}}</span> </p>
  </footer>
<!-- </div> -->
<!-- Back to My Account Link -->
</body>
</html>