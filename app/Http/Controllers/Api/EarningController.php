<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Validator;
use Storage;
use Config;
use PDF;
use Carbon\Carbon;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\Truck;
use App\Models\Load;
use App\Models\Driver;
use App\Models\Setup;
use App\Models\User;
use App\Models\Advance;


class EarningController extends Controller
{
    public function driver_e(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'driver' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select driver, rate and dates correctly',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
        ];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Start date" cannot be greater than "End date"',
                'errors' => [],
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select valid dates.',
                'errors' => [],
            ], 403);
        }
        $input = $req->all();
        $p = Load::where('is_active', true)
            ->where('driver', $input['driver'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        $trips_meta = $this->f_trips($trips, $input['rate']);
        $advances_meta = $this->f_advances($input['driver'], $from_date, $to_date);
        $summations = [
            'a' => $trips_meta[1],
            'b' => number_format($advances_meta[1], 2),
            'c' => number_format($this->clean_n($trips_meta[1])-$advances_meta[1], 2),
        ];
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'trips' => $trips_meta[0],
            'advances' => $advances_meta[0],
            'summations' => $summations,
        ], 200);
    }

    public function driver_d(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $validator = Validator::make($req->all(), [
            'driver' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'except' => 'array',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select driver, rate and dates correctly',
                'errors' => $validator->errors()->all(),
                'logs' => $req->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
        ];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Start date" cannot be greater than "End date"',
                'errors' => [],
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select valid dates.',
                'errors' => [],
            ], 403);
        }
        $input = $req->all();
        $p = Load::where('is_active', true)
            ->where('driver', $input['driver'])
            ->whereNotIn('id', $input['except'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        $trips_meta = $this->f_trips($trips, $input['rate']);
        $advances_meta = $this->f_advances($input['driver'], $from_date, $to_date);
        $summations = [
            'a' => $trips_meta[1],
            'b' => number_format($advances_meta[1], 2),
            'c' => number_format($this->clean_n($trips_meta[1])-$advances_meta[1], 2),
        ];
        $driver_meta = Driver::find($input['driver']);
        $pdf_data = [
            'trips' => $trips_meta[0],
            'advances' => $advances_meta[0],
            'summations' => $summations,
            'setup' => $this->find_setup(),
            'owner' => $driver_meta,
        ];
        $filename = ('app/cls/' . $uuid_string);
        PDF::loadView('reports.driver_earn', $pdf_data)->save(storage_path($filename));
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $uuid_string]),
            'errors' => [],
        ], 200);
    }
    public function dispatcher_e(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'dispatcher' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select dispatcher, rate and dates correctly',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
        ];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Start date" cannot be greater than "End date"',
                'errors' => [],
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select valid dates.',
                'errors' => [],
            ], 403);
        }
        $input = $req->all();
        $p = Load::where('is_active', true)
            ->where('dispatcher', $input['dispatcher'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        $trips_meta = $this->f_trips_dispa($trips, $input['rate']);
        $advances_meta = $this->f_advances_dispa($input['dispatcher'], $from_date, $to_date);
        $summations = [
            'a' => $trips_meta[1],
            'b' => number_format($advances_meta[1], 2),
            'c' => number_format($this->clean_n($trips_meta[1])-$advances_meta[1], 2),
        ];
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'trips' => $trips_meta[0],
            'advances' => $advances_meta[0],
            'summations' => $summations,
        ], 200);
    }
    public function dispatcher_d(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $validator = Validator::make($req->all(), [
            'dispatcher' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'except' => 'array',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select dispatcher, rate and dates correctly',
                'errors' => $validator->errors()->all(),
                'logs' => $req->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
        ];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Start date" cannot be greater than "End date"',
                'errors' => [],
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select valid dates.',
                'errors' => [],
            ], 403);
        }
        $input = $req->all();
        $p = Load::where('is_active', true)
            ->where('dispatcher', $input['dispatcher'])
            ->whereNotIn('id', $input['except'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        $trips_meta = $this->f_trips_dispa($trips, $input['rate']);
        $advances_meta = $this->f_advances_dispa($input['dispatcher'], $from_date, $to_date);
        $summations = [
            'a' => $trips_meta[1],
            'b' => number_format($advances_meta[1], 2),
            'c' => number_format($this->clean_n($trips_meta[1])-$advances_meta[1], 2),
        ];
        $dispatcher_meta = User::find($input['dispatcher']);
        $pdf_data = [
            'trips' => $trips_meta[0],
            'advances' => $advances_meta[0],
            'summations' => $summations,
            'setup' => $this->find_setup(),
            'owner' => $dispatcher_meta,
        ];
        $filename = ('app/cls/' . $uuid_string);
        PDF::loadView('reports.dispatcher_earn', $pdf_data)->save(storage_path($filename));
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $uuid_string]),
            'errors' => [],
        ], 200);
    }
    protected function f_advances($user, $date1, $date2)
    {
        $data = Advance::where('is_active', true)
            ->where('user', $user)
            ->where('user_type', 1)
            ->where('payfrom', '>=', $date1)
            ->where('payto', '<=', $date2)
            ->get();
        $sum = Advance::where('is_active', true)
            ->where('user', $user)
            ->where('user_type', 1)
            ->where('payfrom', '>=', $date1)
            ->where('payto', '<=', $date2)
            ->sum('amount');
        if(is_null($data))
        {
            return [ [], 0];
        }
        return [ $data->toArray(), $sum ];
    }
    protected function f_advances_dispa($user, $date1, $date2)
    {
        $data = Advance::where('is_active', true)
            ->where('user', $user)
            ->where('user_type', 2)
            ->where('payfrom', '>=', $date1)
            ->where('payto', '<=', $date2)
            ->get();
        $sum = Advance::where('is_active', true)
            ->where('user', $user)
            ->where('user_type', 2)
            ->where('payfrom', '>=', $date1)
            ->where('payto', '<=', $date2)
            ->sum('amount');
        if(is_null($data))
        {
            return [ [], 0];
        }
        return [ $data->toArray(), $sum ];
    }
    protected function f_trips($in, $rate)
    {
        $data = [];
        $additions = [];
        foreach($in as $_trip ):
            $_trip['rate'] = number_format($_trip['rate'], 2);
            $_trip['net'] = $this->compute_net_rate($_trip['rate'], intval($rate));
            $net_rate = $this->clean_n($_trip['net']);
            $d_meta = Driver::find($_trip['driver']);
            $rtype = $d_meta->rate_type;
            $r = $d_meta->rate;
            $_trip['pay'] = $this->calc_driver_earn($rtype, $r, $net_rate);
            $pay = $this->clean_n($_trip['pay']);
            array_push($data, $_trip);
            array_push($additions, $pay);
        endforeach;
        return [ $data, number_format(array_sum($additions), 2) ];
    }
    protected function f_trips_dispa($in, $rate)
    {
        $data = [];
        $additions = [];
        foreach($in as $_trip ):
            $_trip['rate'] = number_format($_trip['rate'], 2);
            $_trip['net'] = $this->compute_net_rate($_trip['rate'], intval($rate));
            $net_rate = $this->clean_n($_trip['net']);
            $d_meta = User::find($_trip['dispatcher']);
            $rtype = $d_meta->rate_type;
            $r = $d_meta->rate;
            $_trip['pay'] = $this->calc_driver_earn($rtype, $r, $net_rate);
            $pay = $this->clean_n($_trip['pay']);
            array_push($data, $_trip);
            array_push($additions, $pay);
        endforeach;
        return [ $data, number_format(array_sum($additions), 2) ];
    }
    protected function calc_driver_earn($type, $r, $value)
    {
        if(intval($type) == 2)
        {
            return number_format($r, 2);
        }
        return number_format(($r*$value/100), 2);
    }
    protected function compute_net_rate($amt, $percent)
    {
        $amt = $this->clean_n($amt);
        return number_format(((100-$percent)/100)*$amt, 2);
    }
    protected function clean_n($n)
    {
        return str_replace(',', '', $n);
    }
    protected function format_earnings_s($in)
    {
        $data = [];
        foreach( $in as $_earning ):
            $staff_meta = User::find($_earning['dispatcher']);
            $truck_meta = Truck::find($_earning['truck']);
            $_earning['rate_type'] = $staff_meta['rate_type'];
            $_earning['srate'] = $staff_meta['rate'];
            $_earning['earning'] = $this->compute_earning($staff_meta['rate_type'], $staff_meta['rate'], $_earning['rate']);
            $_earning['rate'] = number_format($_earning['rate'], 2);
            $_earning['tlabel'] = $truck_meta['make'] . ' - ' . $truck_meta['number'];
            $_earning['slabel'] = $staff_meta['fname'] . ' ' . $staff_meta['lname'];
            array_push($data, $_earning);
        endforeach;

        return $data;
    }
    protected function format_earnings_d($in)
    {
        $data = [];
        foreach( $in as $_earning ):
            $driver_meta = Driver::find($_earning['driver']);
            $truck_meta = Truck::find($_earning['truck']);
            $_earning['rate_type'] = $driver_meta['rate_type'];
            $_earning['drate'] = $driver_meta['rate'];
            $_earning['earning'] = $this->compute_earning($driver_meta['rate_type'], $driver_meta['rate'], $_earning['rate']);
            $_earning['rate'] = number_format($_earning['rate'], 2);
            $_earning['tlabel'] = $truck_meta['make'] . ' - ' . $truck_meta['number'];
            $_earning['dlabel'] = $driver_meta['fname'] . ' ' . $driver_meta['lname'];
            array_push($data, $_earning);
        endforeach;

        return $data;
    }
    protected function compute_earning($rtype, $r, $loadvalue)
    {
        if(is_null($rtype) || is_null($r) )
        {
            return 0.00;
        }
        if( intval($rtype) == 1)
        {
            $p = ( ($r/100) * $loadvalue);
            return number_format(($p),2);
        }
        if( intval($rtype) == 2)
        {
            return number_format(($r),2);
        }
    }
    protected function find_setup()
    {
        $s = Setup::where('id', '!=', 0)->first();
        if(!is_null($s))
        {
            return $s->toArray();
        }
        return [
            'company' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'zip' => null,
            'email' => null,
            'phone' => null,
        ];
    }
}
