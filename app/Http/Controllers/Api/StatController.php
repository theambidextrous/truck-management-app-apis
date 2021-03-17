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
use PDF;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\Truck;
use App\Models\Load;
use App\Models\Driver;
use App\Models\Setup;
use App\Models\Owner;

class StatController extends Controller
{
    public function dashboard()
    {
        
        $rem_meta = $this->find_truck_reminders();
        $stat = [
            'trucks' => $this->find_trucks_count(),
            'trips' => $this->find_trips_count(),
            'revenue' => '$' . $this->find_revenue_avr(),
            'expenses' => '$' . $this->find_expenses_avr(),
            'revexp' => $this->find_rev_exp_data(),
            'top_ten' => $this->find_top_ten_trucks(),
            'top_five_exp' => $this->find_top_five_exp(),
            'top_five_loads' => $this->find_top_five_loads(),
            'top_five_mileage' => $this->find_top_mileage_loads(),
            'has_reminders' => $rem_meta[0],
            'reminders' => $rem_meta[1],
        ];
        return response([
            'status' => 200,
            'data' => $stat,
        ], 200);
    }
    protected function find_rev_exp_data()
    {
        $six_months = $this->find_six_m_ago();
        $data = [];
        foreach( array_reverse($six_months) as $_six):
            $exp_a = Expense::where('created_at', 'like', '%' . date('Y-m', strtotime($_six)). '%')
                ->sum('amount');
            $exp_b = Expense::where('created_at', 'like', '%' . date('Y-m', strtotime($_six)). '%')
                ->sum('misc_amount');
            $exp_sum = $exp_a + $exp_b;
            $rev_sum = Load::where('created_at', 'like', '%' . date('Y-m', strtotime($_six)). '%')
                ->sum('rate');
            $entry = [
                $_six . '~' . date('F', strtotime($_six)) => $this->format_in_1000($rev_sum) . '~' . $this->format_in_1000($exp_sum),
            ];
            array_push($data, $entry);
        endforeach;

        return $data;
    }
    protected function format_in_1000($k)
    {
        if(intval($k) == 0 )
        {
            return 0;
        }
        return intval($k/1000);
    }
    protected function find_six_m_ago()
    {
        return [
            date('Y-m-d'),
            date("Y-m-d", strtotime("-1 months")),
            date("Y-m-d", strtotime("-2 months")),
            date("Y-m-d", strtotime("-3 months")),
            date("Y-m-d", strtotime("-4 months")),
            date("Y-m-d", strtotime("-5 months")),
        ];
    }
    protected function find_top_mileage_loads()
    {
        $summations = Load::groupBy('truck')
            ->selectRaw('sum(miles) as sum, truck')
            ->orderBy('sum', 'desc')
            ->pluck('sum','truck');
        $data = [];
        // return $summations;
        foreach( $summations as $_truck => $weight ):
            $trk_meta = Truck::find($_truck);
            $entry = [
                $trk_meta->make . ' - ' . $trk_meta->number => intval($weight),
            ];
            array_push($data, $entry);
        endforeach;
        return $data;
    }
    protected function find_top_five_loads()
    {
        $summations = Load::groupBy('truck')
            ->selectRaw('sum(weight) as sum, truck')
            ->orderBy('sum', 'desc')
            ->pluck('sum','truck');
        $data = [];
        // return $summations;
        foreach( $summations as $_truck => $weight ):
            $trk_meta = Truck::find($_truck);
            $entry = [
                $trk_meta->make . ' - ' . $trk_meta->number => intval($weight),
            ];
            array_push($data, $entry);
        endforeach;
        return $data;
    }
    protected function find_top_five_exp()
    {
        $summations = Expense::groupBy('type')
            ->selectRaw('sum( amount + misc_amount ) as sum, type')
            ->orderBy('sum', 'desc')
            ->pluck('sum','type');
        $data = [];
        // return $summations;
        foreach( $summations as $_type => $exp ):
            $trk_meta = ExpenseGroup::find($_type);
            $entry = [
                $trk_meta->name . '($)' => intval($exp),
            ];
            array_push($data, $entry);
        endforeach;
        return $data;
    }
    protected function find_top_ten_trucks()
    {
        $summations = Load::groupBy('truck')
            ->selectRaw('sum(rate) as sum, truck')
            ->orderBy('sum', 'desc')
            ->pluck('sum','truck');
        $data = [];
        // return $summations;
        foreach( $summations as $_truck => $rev ):
            $trk_meta = Truck::find($_truck);
            $entry = [];
            if(!is_null($trk_meta))
            {
                $entry = [
                    $trk_meta->make . ' - ' . $trk_meta->number => intval($rev),
                ];
            }
            array_push($data, $entry);
        endforeach;
        return $data;
    }
    protected function find_revenue_avr()
    {
        $now = date('Y-m-d');
        $first_trip_date = Load::where('is_active', true)->orderBy('id', 'asc')->first();
        $first_trip_date = $first_trip_date->created_at;
        $months_count = $this->count_months($first_trip_date, $now);
        $sum_loads = Load::where('is_active', true)->sum('rate');
        $avr = $sum_loads/$months_count;
        $avr = $this->format_ks($avr);
        return $avr;
    }
    protected function find_expenses_avr()
    {
        $now = date('Y-m-d');
        $first_exp_date = Expense::where('is_active', true)->orderBy('id', 'asc')->first();
        if(is_null($first_exp_date))
        {
            return 0;
        }
        $first_exp_date = $first_exp_date->created_at;
        $months_count = $this->count_months($first_exp_date, $now);
        $sum_exp_a = Expense::where('is_active', true)->sum('amount');
        $sum_exp_b = Expense::where('is_active', true)->sum('misc_amount');
        $sum_exp = $sum_exp_a + $sum_exp_b;
        $avr = $sum_exp/$months_count;
        $avr = $this->format_ks($avr);
        return $avr;
    }
    protected function find_trips_count()
    {
        return Load::where('is_active', true)->count();
    }
    protected function find_trucks_count()
    {
        return Truck::where('is_active', true)->count();
    }
    protected function count_months($fdate, $sdate)
    {
        $ts1 = strtotime($fdate);
        $ts2 = strtotime($sdate);

        $year1 = date('Y', $ts1);
        $year2 = date('Y', $ts2);
        
        $month1 = intval(date('m', $ts1));
        $month2 = intval(date('m', $ts2));

        $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
        if( $diff == 0 )
        {
            return 1;
        }
        return $diff;
    }
    protected function find_truck_reminders()
    {
        $now = date('Y-m-d');
        $insurance = Truck::where('insurance_expires', '<', $now)->get();
        $inspection = Truck::where('inspection_expires', '<', $now)->get();
        $registration = Truck::where('registration_expires', '<', $now)->get();
        $insur = $this->format_insu($insurance);
        $inspec = $this->format_inspect($inspection);
        $regi = $this->format_reg($registration);
        $final = array_merge($regi, $insur, $inspec);
        if( count($final) )
        {
            return [1, $final];
        }
        return [0, $final];
    }
    protected function format_insu($obj)
    {
        if(is_null($obj))
        {
            return [];
        }
        $rtn = [];
        $data = $obj->toArray();
        foreach( $data as $_data ):
            $fl = [];
            $fl['tlabel'] = $_data['make'] . ' Reg No. ' . $_data['number'] . ' - Insurance is expired';
            array_push($rtn, $fl);
        endforeach;
        return $rtn;
    }
    protected function format_inspect($obj)
    {
        if(is_null($obj))
        {
            return [];
        }
        $rtn = [];
        $data = $obj->toArray();
        foreach( $data as $_data ):
            $fl = [];
            $fl['tlabel'] = $_data['make'] . ' Reg No. ' . $_data['number'] . ' - Inspection is overdue';
            array_push($rtn, $fl);
        endforeach;
        return $rtn;
    }
    protected function format_reg($obj)
    {
        if(is_null($obj))
        {
            return [];
        }
        $rtn = [];
        $data = $obj->toArray();
        foreach( $data as $_data ):
            $fl = [];
            $fl['tlabel'] = $_data['make'] . ' Reg No. ' . $_data['number'] . ' - Registration is expired';
            array_push($rtn, $fl);
        endforeach;
        return $rtn;
    }
    protected function format_ks($k)
    {
        if(intval($k) > 1000 )
        {
            return number_format($k/1000, 2) . 'k';
        }
        return number_format($k, 2);
    }
}
