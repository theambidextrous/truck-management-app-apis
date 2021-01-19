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
use Carbon\Carbon;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\Truck;
use App\Models\Load;
use App\Models\Driver;
use App\Models\User;


class EarningController extends Controller
{
    public function driver_e(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Either "Date from" or "Date to" is missing',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $data = [];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Date from" cannot be greater than "Date to"',
                'errors' => [],
                'data' => $data,
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            $p = Load::where('is_active', true)->where('driver', $id)->get();
            if(!is_null($p)){ $data = $p->toArray();}
            return response([
                'status' => 200,
                'message' => 'data without dates',
                'data' => $this->format_earnings_d($data),
            ], 200);
        }
        $p = Load::where('is_active', true)
            ->where('driver', $id)
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $data = $p->toArray();}
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'data' => $this->format_earnings_d($data),
        ], 200);
    }
    public function dispatcher_e(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Either "Date from" or "Date to" is missing',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $data = [];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Date from" cannot be greater than "Date to"',
                'errors' => [],
                'data' => $data,
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            $p = Load::where('is_active', true)->where('dispatcher', $id)->get();
            if(!is_null($p)){ $data = $p->toArray();}
            return response([
                'status' => 200,
                'message' => 'data without dates',
                'data' => $this->format_earnings_s($data),
            ], 200);
        }
        $p = Load::where('is_active', true)
            ->where('dispatcher', $id)
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $data = $p->toArray();}
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'data' => $this->format_earnings_s($data),
        ], 200);
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
}
