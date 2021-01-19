
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Weekly earning report</title>
<meta name="author" content="">

<!-- Web Fonts
======================= -->

<!-- Stylesheet
======================= -->
<link rel="stylesheet" type="text/css" href="{{ asset('inv/bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('inv/all.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="{{ asset('inv/stylesheet.css') }}"/>
<style>
 .custom-p{
    font-weight: 700;
    margin-left: 80px;
 }
 .dark-head{
    background-color:#b9b9b9;
 }
 .table-bordered td, .table-bordered th {
     border: 1px solid #000000;
     text-align: center!important;
 }
 .table thead th {
    border-bottom: 2px solid #515151;
}
.td-borderless{
    border: 0px solid #000000!important;
}
.table{
    color:#000000!important;
}
.color-dt{
    color:#ff720b!important;  
}
</style>
</head>
<body>
<!-- Container -->
<div class="container-fluid invoice-container"> 
  <!-- Header -->
  <header>
    <div class="row align-items-center">
      <div class="col-sm-7 text-center text-sm-left mb-3 mb-sm-0">
        $setup['company']<br>
        $setup['address']<br>
        $setup['city'], $setup['state'] $setup['zip']<br>
        Phone #: $setup['phone']<br>
      </div>
      <div class="col-sm-5 text-sm-right">
        <h5 class="mb-0">Statement</h5>
        <h5 class="mb-0">{{ $owner['company'] }}</h5>
        <h5 class="mb-0">{{ date('m/d/Y') }}</h5>
        <h5 class="mb-0">Truck #{{ $truck }}</h5>
      </div>
    </div>
    <hr>
  </header>
  <!-- Main Content -->
  <main>
    <p class="text-1 text-left text-dark custom-p">
    {{ $owner['company'] }}<br>
    {{ $owner['address'] }} <br>
    {{ $owner['city'] }}, {{ $owner['state'] }} {{ $owner['zip'] }}
    </p>
    <!-- Passenger Details -->
    <br>
    <!-- TRIPS ================== -->
    <h4 class="text-4 mt-2"><b>Trips:</b></h4>
    <div class="table-responsive">
      <table class="table table-bordered text-1 table-sm">
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
                    <td>{{ $trp['number'] }}</td>
                    <td>{{ $trp['origin'] }}, {{ $trp['destination'] }}</td>
                    <td>{{ $trp['mileage'] }}</td>
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
    </div>
    <!-- DEDUCTIONS ======================= -->
    <h4 class="text-4 mt-2"><b>Deductions:</b></h4>
    <div class="table-responsive">
      <table class="table table-bordered text-1 table-sm">
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
    </div>

    <!-- SCHEDULED DEDUCTIONS ======================= -->
    <h4 class="text-4 mt-2"><b>Scheduled Deductions:</b></h4>
    <div class="table-responsive">
      <table class="table table-bordered text-1 table-sm">
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
    </div>

    <!-- FUEL CARD ======================= -->
    <h4 class="text-4 mt-2"><b>Fuel card:</b></h4>
    <div class="table-responsive">
      <table class="table table-bordered text-1 table-sm">
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
            @foreach( $fuel as $fuel )
                <tr>
                    <td>{{ $fuel['description'] }}</td>
                    <td>{{ $fuel['city'] }}</td>
                    <td>{{ $fuel['state'] }}</td>
                    <td>${{ $fuel['amount_f'] }}</td>
                    <td>${{ $fuel['misc_amount_f'] }}</td>
                    <td>{{ date('m/d/Y', strtotime($fuel['created_at'])) }}</td>
                    <td>${{ $fuel['total'] }}</td>
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
    </div>
    
    <!-- Fare Details -->
    <div class="table-responsive">
      <table class="table table-bordered text-1 table-sm">
        <tbody>
          <tr>
            <td style="width:450px;" class="td-borderless"></td>
            <td class=""><b>Check amount</b></td>
            <td class="color-dt">${{ $summations['e'] }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
  <!-- Footer -->
  <footer class="text-center">
    <hr>
    <p><strong>{{ Config::get('app.name')}}</strong><br>
    {{ Config::get('app.url')}}
      </p>
    <hr>
    <p class="text-1"><b>{{ $setup['company'] }}</b> | <b>$setup['address']</b> | <b>$setup['city'], $setup['state'] $setup['zip']</b> </p>
  </footer>
</div>
<!-- Back to My Account Link -->
</body>
</html>