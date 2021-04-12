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
use File;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\Truck;
use App\Models\Load;
use App\Models\Driver;
use App\Models\User;
use App\Models\Freport;
use App\Models\Setup;
use App\Models\Owner;
use App\Models\Client;

class ReportController extends Controller
{
    public function archives()
    {
        $account = Auth::user()->account;
        $data = Freport::where('account', $account)->get();
        $rtn = [];
        if(!is_null($data))
        {
            $rtn = $data->toArray();
        }
        return response([
            'status' => 201,
            'message' => 'done',
            'data' => $this->format_archives($rtn),
        ], 403);

    }
    protected function format_archives($data)
    {
        $rtn = [];
        if(!count($data))
        {
            return [];
        }
        foreach( $data as $_data )
        {
            $_data['link'] = route('stream', ['file' => $_data['download']]);
            $_data['dated'] = date("jS M, Y", strtotime($_data['created_at']));
            array_push($rtn, $_data);
        }
        return $rtn;
    }
    public function weekly(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'truck' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select truck, rate and dates correctly',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $deductions = [];
        $scheduled = [];
        $fuel = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
            'd' => '0.00',
            'e' => '0.00',
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
        $account = Auth::user()->account;
        $p = Load::where('is_active', true)
            ->where('truck', $input['truck'])
            ->where('account', $account)
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        /** dispatcher, driver based on rate */
        $automatic_deductions = $this->find_auto_charges($trips);
        /**end */
        $trips_meta = $this->f_trips($trips, $input['rate']);
        $deductions_meta = $this->find_deductions($input, 2);
        $scheduled_meta = $this->find_deductions($input, 1, 4);
        $fuel_meta = $this->find_deductions($input, 3);
        $check_amt = $this->clean_n($trips_meta[1]) - ($this->clean_n($deductions_meta[1])+$this->clean_n($fuel_meta[1])+$this->clean_n($scheduled_meta[1]) + $automatic_deductions);
        $final_Deductions = $this->format_ded_special($deductions_meta[0], $trips);
        $summations = [
            'a' => $trips_meta[1],
            'b' => $final_Deductions[1],
            'c' => $fuel_meta[1],
            'd' => $scheduled_meta[1],
            'e' => number_format($check_amt, 2),
        ];
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'trips' => $trips_meta[0],
            'deductions' => $final_Deductions[0],
            'fuel' => $this->f_deductions($fuel_meta[0]),
            'scheduled' => $this->f_deductions_sch($scheduled_meta[0]),
            'summations' => $summations,
        ], 200);
    }

    public function download_weekly(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $validator = Validator::make($req->all(), [
            'truck' => 'required|string|not_in:nn',
            'rate' => 'required|string|not_in:nn',
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select truck, rate and dates correctly',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $trips = [];
        $deductions = [];
        $scheduled = [];
        $fuel = [];
        $summations = [
            'a' => '0.00',
            'b' => '0.00',
            'c' => '0.00',
            'd' => '0.00',
            'e' => '0.00',
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
        $account = Auth::user()->account;
        $p = Load::where('is_active', true)
            ->where('truck', $input['truck'])
            ->where('account', $account)
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $trips = $p->toArray();}
        /** dispatcher, driver based on rate */
        $automatic_deductions = $this->find_auto_charges($trips);
        /**end */
        $trips_meta = $this->f_trips($trips, $input['rate']);
        $deductions_meta = $this->find_deductions($input, 2);
        $scheduled_meta = $this->find_deductions($input, 1, 4);
        $fuel_meta = $this->find_deductions($input, 3);
        $check_amt = $this->clean_n($trips_meta[1]) - ($this->clean_n($deductions_meta[1])+$this->clean_n($fuel_meta[1])+$this->clean_n($scheduled_meta[1]) + $automatic_deductions);
        $final_Deductions = $this->format_ded_special($deductions_meta[0], $trips);
        $summations = [
            'a' => $trips_meta[1],
            'b' => $final_Deductions[1],
            'c' => $fuel_meta[1],
            'd' => $scheduled_meta[1],
            'e' => number_format($check_amt, 2),
        ];
        $truck_meta = Truck::find($input['truck']);
        $pdf_data = [
            'trips' => $trips_meta[0],
            'deductions' => $final_Deductions[0],
            'fuel' => $this->f_deductions($fuel_meta[0]),
            'scheduled' => $this->f_deductions_sch($scheduled_meta[0]),
            'summations' => $summations,
            'setup' => $this->find_setup(),
            'owner' => $this->find_truck_owner($input['truck']),
            'truck' => $truck_meta->make . '-' . $truck_meta->number,
        ];
        $filename = ('app/cls/' . $uuid_string);
        PDF::loadView('reports.weekly', $pdf_data)->save(storage_path($filename));
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $uuid_string]),
            'errors' => [],
        ], 200);
    }
    public function find_loads_delivered()
    {
        $trips = [];
        $account = Auth::user()->account;
        $p = Load::where('is_active', true)
            ->where('is_delivered', true)
            ->where('account', $account)
            ->where('is_paid', false)
            ->orderBy('id', 'desc')
            ->get();
        $summation_dollar = Load::where('is_active', true)
            ->where('is_delivered', true)
            ->where('account', $account)
            ->where('is_paid', false)
            ->sum('rate');
        if(!is_null($p))
        { 
            $trips = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'data' => $this->formatFactoringTrips($trips),
            'count' => count($trips),
            'total' => number_format($summation_dollar, 1),
            'company' => null,
        ], 200);
    }
    public function factoring(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'company' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. Please select company',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $trips = [];
        $account = Auth::user()->account;
        $company = $req->get('company');
        $company_trucks = $this->find_co_trucks($company);
        $p = Load::where('is_active', true)
            ->where('is_delivered', true)
            ->where('account', $account)
            ->where('is_paid', false)
            ->whereIn('truck', $company_trucks)
            ->orderBy('id', 'desc')
            ->get();
        $summation_dollar = Load::where('is_active', true)
            ->where('is_delivered', true)
            ->where('account', $account)
            ->where('is_paid', false)
            ->whereIn('truck', $company_trucks)
            ->sum('rate');
        if(!is_null($p))
        { 
            $trips = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'data' => $this->formatFactoringTrips($trips),
            'count' => count($trips),
            'total' => number_format($summation_dollar, 1),
            'company' => $company,
        ], 200);
    }
    protected function formatFactoringTrips($data, $div = '')
    {
        if( !count($data) ) { return []; }
        $rtn = [];
        foreach( $data as $_data ):
            $_data['created_at'] = date("m/d/Y", strtotime($_data['created_at']));
            $_data['updated_at'] = date("m/d/Y", strtotime($_data['updated_at']));
            $_data['division'] = $div;
            $_data['status'] = 'Delivered';
            $_data['rate'] = number_format($_data['rate'], 1);
            array_push($rtn, $_data);
        endforeach;
        return $rtn;
    }
    protected function find_co_trucks($co)
    {
        $account = Auth::user()->account;
        $d = Truck::select('id')->where('owner', $co)->where('account', $account)->get();
        if(is_null($d))
        {
            return [];
        }
        return $d->toArray();
    }
    public function export_invoices(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $validator = Validator::make($req->all(), [
            'loads' => 'required|array',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No report entries selected',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $trips = [];
        $account = Auth::user()->account;
        $totals = 0;
        $loadIds = $req->get('loads');
        sort($loadIds);
        if( !count($loadIds) )
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No entries to export',
                'errors' => [],
            ], 403);
        }
        $firstId = $loadIds[0];
        $reportName = 'exp-inv-loads-' . $loadIds[0] . '-to-load-' . $loadIds[count($loadIds)-1];
        $p = Load::where('is_active', true)
            ->where('account', $account)
            ->whereIn('id', $loadIds)
            ->get();
        $sum = Load::where('is_active', true)
            ->where('account', $account)
            ->whereIn('id', $loadIds)
            ->sum('rate');
        if(!is_null($p)){ 
            $trips = $p->toArray();
        }
        $pdf_data = [
            'trips' => $this->formatFactoringTrips($trips),
            'total' => number_format($sum, 1),
            'count' => count($trips),
            'setup' => $this->find_setup(),
            'owner' => $this->findTruckOwnerByLoad($firstId),
        ];
        $filename = ('app/cls/' . $uuid_string);
        PDF::loadView('reports.exportInvoices', $pdf_data, [], [
            'orientation' => 'L'
        ])->save(storage_path($filename));
        $freportData = [
            'account' => $account,
            'name' => $reportName,
            'download' => $uuid_string,
            'items' => implode(',', $loadIds),
        ];
        Freport::where('name', $reportName)->where('account', $account)->delete();
        Freport::create($freportData);
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $uuid_string]),
            'errors' => [],
        ], 200);
    }
    public function export_invoices_paperwork(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $zip_uuid_string = (string)Str::uuid() . '.zip';
        $validator = Validator::make($req->all(), [
            'loads' => 'required|array',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No report entries selected',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $trips = [];
        $account = Auth::user()->account;
        $totals = 0;
        $loadIds = $req->get('loads');
        sort($loadIds);
        if( !count($loadIds) )
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No entries to export',
                'errors' => [],
            ], 403);
        }
        $firstId = $loadIds[0];
        $reportName = 'exp-inv-loads-' . $loadIds[0] . '-to-load-' . $loadIds[count($loadIds)-1] . '.pdf';
        $p = Load::where('is_active', true)
            ->whereIn('id', $loadIds)
            ->where('account', $account)
            ->get();
        $sum = Load::where('is_active', true)
            ->whereIn('id', $loadIds)
            ->where('account', $account)
            ->sum('rate');
        if(!is_null($p)){ 
            $trips = $p->toArray();
        }
        $pdf_data = [
            'trips' => $this->formatFactoringTrips($trips),
            'total' => number_format($sum, 1),
            'count' => count($trips),
            'setup' => $this->find_setup(),
            'owner' => $this->findTruckOwnerByLoad($firstId),
        ];
        $filename = ('app/cls/' . $reportName);
        PDF::loadView('reports.exportInvoices', $pdf_data, [], [
            'orientation' => 'L'
        ])->save(storage_path($filename));
        $zipables = $this->pullPaperWorkFromLoads($trips);
        array_push($zipables, $reportName . '~~' . $reportName);
        /** zipper */
        $zip = new \ZipArchive();
        $zipFileName = ('app/cls/' . $zip_uuid_string);
        if ($zip->open(storage_path($zipFileName), \ZipArchive::CREATE)== TRUE)
        {
            foreach ( $zipables as $zipable ){
                $flArr = explode('~~', $zipable);
                $fl = new \Illuminate\Http\File(storage_path('app/cls/' . $flArr[0]));
                $relativeName = $flArr[1];
                $zip->addFile($fl, $relativeName);
            }
            $zip->close();
        }
        /** end zipper */
        $freportData = [
            'account' => $account,
            'name' => $reportName,
            'download' => $zip_uuid_string,
            'items' => implode(',', $loadIds),
        ];
        Freport::where('name', $reportName)->where('account', $account)->delete();
        Freport::create($freportData);
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $zip_uuid_string]),
            'errors' => [],
        ], 200);
    }
    public function export_paperwork(Request $req)
    {
        $uuid_string = (string)Str::uuid() . '.pdf';
        $zip_uuid_string = (string)Str::uuid() . '.zip';
        $validator = Validator::make($req->all(), [
            'loads' => 'required|array',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No report entries selected',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $trips = [];
        $account = Auth::user()->account;
        $totals = 0;
        $loadIds = $req->get('loads');
        sort($loadIds);
        if( !count($loadIds) )
        {
            return response([
                'status' => 201,
                'message' => 'Invalid report data. No entries to export',
                'errors' => [],
            ], 403);
        }
        $firstId = $loadIds[0];
        $reportName = 'exp-paperwork-loads-' . $loadIds[0] . '-to-load-' . $loadIds[count($loadIds)-1] . '.zip';
        $p = Load::where('is_active', true)
            ->whereIn('id', $loadIds)
            ->where('account', $account)
            ->get();
        if(!is_null($p)){ 
            $trips = $p->toArray();
        }
        $zipables = $this->pullPaperWorkFromLoads($trips);
        /** zipper */
        $zip = new \ZipArchive();
        $zipFileName = ('app/cls/' . $zip_uuid_string);
        if ($zip->open(storage_path($zipFileName), \ZipArchive::CREATE)== TRUE)
        {
            foreach ( $zipables as $zipable ){
                $flArr = explode('~~', $zipable);
                $fl = new \Illuminate\Http\File(storage_path('app/cls/' . $flArr[0]));
                $relativeName = $flArr[1];
                $zip->addFile($fl, $relativeName);
            }
            $zip->close();
        }
        /** end zipper */
        $freportData = [
            'account' => $account,
            'name' => $reportName,
            'download' => $zip_uuid_string,
            'items' => implode(',', $loadIds),
        ];
        Freport::where('name', $reportName)->where('account', $account)->delete();
        Freport::create($freportData);
        return response([
            'status' => 200,
            'message' => 'Report generated',
            'fileurl' => route('stream', ['file' => $zip_uuid_string]),
            'errors' => [],
        ], 200);
    }
    protected function pullPaperWorkFromLoads($data)
    {
        $rtn = [];
        foreach( $data as $_data ):
            if(!is_null($_data['delivery_docs']))
            {
                $ext = explode('.', $_data['delivery_docs'])[1];
                $zipable = 'paperwork-load-' . $_data['id'] . '.' . $ext;
                $fn = $_data['delivery_docs'] . '~~' . $zipable;
                array_push($rtn, $fn);
            }
        endforeach;
        return $rtn;
    }
    protected function findTruckOwnerByLoad($load)
    {
        $l = Load::find($load);
        if(is_null($l))
        {
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
        // $t = Truck::find($l->truck);
        // if(is_null($t))
        // {
        //     return [
        //         'company' => null,
        //         'address' => null,
        //         'city' => null,
        //         'state' => null,
        //         'zip' => null,
        //         'email' => null,
        //         'phone' => null,
        //     ]; 
        // }
        $o = Client::find($l->company);
        if(is_null($o))
        {
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
        return $o->toArray();
    }
    public function stream($file)
    {
        $filename = ('app/cls/' . $file);
        return response()->download(storage_path($filename), null, [], null); 
    }

    protected function find_auto_charges($trips)
    {
        $rtn = [];
        foreach( $trips as $_trip ):
            $driver_rate = $this->clean_n($this->get_driver_rate($_trip));
            $dispatcher_rate = $this->clean_n($this->get_dispatcher_rate($_trip));
            $deduction_sum = $driver_rate + $dispatcher_rate;
            array_push($rtn, $deduction_sum);
        endforeach;
        return array_sum($rtn);
    }
    protected function get_driver_rate($data)
    {
        $d = Driver::find($data['driver_a']);
        if(is_null($d))
        {
            return 0;
        }
        return $this->calc_driver_earn($d->rate_type, $d->rate, $data['rate']);
    }
    protected function get_driverb_rate($data)
    {
        $d = Driver::find($data['driver_b']);
        if(is_null($d))
        {
            return 0;
        }
        return $this->calc_driver_earn($d->rate_type, $d->rate, $data['rate']);
    }
    protected function get_dispatcher_rate($data)
    {
        $d = User::find($data['dispatcher']);
        if(is_null($d))
        {
            return 0;
        }
        return $this->calc_driver_earn($d->rate_type, $d->rate, $data['rate']);
    }
    protected function find_deductions($arr, $t = 2, $f = 0)
    {
        $from_date = date('Y-m-d', strtotime($arr['from_date']));
        $to_date = date('Y-m-d', strtotime($arr['to_date']));
        $account = Auth::user()->account;

        if( $f > 0 )
        {
            $p = Expense::where('type', $t)
                ->where('is_active', true)
                ->where('account', $account)
                ->where('is_paid', false)
                ->where('next_due', '!=', null)
                ->where('truck', $arr['truck'])
                ->where('next_due', '<=', $to_date)
                ->get();
            $sum = Expense::where('type', $t)
                ->where('is_active', true)
                ->where('account', $account)
                ->where('is_paid', false)
                ->where('next_due', '!=', null)
                ->where('truck', $arr['truck'])
                ->where('next_due', '<=', $to_date)
                ->sum('installment');
            // $sum_misc = Expense::where('type', $t)
            //     ->where('is_active', true)
            //     ->where('is_paid', false)
            //     ->where('next_due', '!=', null)
            //     ->where('truck', $arr['truck'])
            //     ->where('next_due', '>=', $from_date)
            //     ->where('next_due', '<=', $to_date)
            //     ->sum('misc_amount');
            $sum_f = number_format($sum, 2);
            if(!is_null($p))
            { 
                return [ $p->toArray(), $sum_f ];
            }
            return [ [], $sum_f ];
        }
        $p = Expense::where('type', $t)
            ->where('is_active', true)
            ->where('account', $account)
            ->where('truck', $arr['truck'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        $sum = Expense::where('type', $t)
            ->where('is_active', true)
            ->where('account', $account)
            ->where('truck', $arr['truck'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->sum('amount');
        $sum_misc = Expense::where('type', $t)
            ->where('is_active', true)
            ->where('account', $account)
            ->where('truck', $arr['truck'])
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->sum('misc_amount');
        $sum_f = number_format($sum + $sum_misc, 2);
        if(!is_null($p))
        { 
            return [ $p->toArray(), $sum_f ];
        }
        return [ [], $sum_f ];
    }
    protected function f_trips_factor($in)
    {
        $data = [];
        $additions = [];
        foreach($in as $_trip ):
            $_trip['rate'] = number_format($_trip['rate'], 2);
            // $_trip['net'] = $this->compute_net_rate($_trip['rate'], intval($rate));
            array_push($data, $_trip);
            array_push($additions, $this->clean_n($_trip['rate']));
        endforeach;
        return [ $data, number_format(array_sum($additions), 2) ];
    }
    protected function f_trips($in, $rate)
    {
        $data = [];
        $additions = [];
        foreach($in as $_trip ):
            $_trip['rate'] = number_format($_trip['rate'], 2);
            $_trip['net'] = $this->compute_net_rate($_trip['rate'], intval($rate));
            array_push($data, $_trip);
            array_push($additions, $this->clean_n($_trip['net']));
        endforeach;
        return [ $data, number_format(array_sum($additions), 2) ];
    }
    protected function f_deductions($in, $trips_list = [] )
    {
        $data = [];
        $autodriver = [];
        foreach($in as $_ded ):
            $_ded['amount_f'] = number_format($_ded['amount'], 2);
            $_ded['misc_amount_f'] = number_format($_ded['misc_amount'], 2);
            $_ded['total'] = number_format($_ded['amount'] + $_ded['misc_amount'], 2);
            array_push($data, $_ded);
        endforeach;
        return $data;
    }
    protected function f_deductions_sch($in, $trips_list = [] )
    {
        $data = [];
        $autodriver = [];
        foreach($in as $_ded ):
            $_ded['amount_f'] = number_format($_ded['installment'], 2);
            $_ded['misc_amount_f'] = number_format($_ded['misc_amount'], 2);
            $_ded['total'] = number_format($_ded['installment'] + $_ded['misc_amount'], 2);
            array_push($data, $_ded);
        endforeach;
        return $data;
    }
    protected function find_truck_owner($trk)
    {
        $truck = Truck::find($trk);
        if( is_null($truck) )
        {
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
        $owner = Owner::find($truck->owner);
        if( is_null($owner) )
        {
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
        return $owner->toArray();
    }
    protected function find_setup()
    {
        $account = Auth::user()->account;
        $s = Setup::where('account', $account)->first();
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
    protected function format_ded_special($in, $trips_list = [] )
    {
        $data = [];
        $autodriver = [];
        $autodispatcher = [];
        $added = [];
        foreach($in as $_ded ):
            $_ded['amount_f'] = number_format($_ded['amount'], 2);
            $_ded['misc_amount_f'] = number_format($_ded['misc_amount'], 2);
            $_ded['total'] = number_format($_ded['amount'] + $_ded['misc_amount'], 2);
            array_push($added, $this->clean_n($_ded['total']));
            array_push($data, $_ded);
        endforeach;
        /**  */
        if( count($trips_list) )
        {
            foreach( $trips_list as $_trip):
                $driver = Driver::find($_trip['driver_a']);
                $driver_b = Driver::find($_trip['driver_b']);
                $dispatcher = User::find($_trip['dispatcher']);

                $dispatcher_rtype = $dispatcher->rate_type;
                $dispatcher_rate = $dispatcher->rate;
                $dispatcher_earn = $this->calc_driver_earn($dispatcher_rtype,$dispatcher_rate,$_trip['rate']);

                $driver_rtype = $driver->rate_type;
                $driver_rate = $driver->rate;
                $driver_earn = $this->calc_driver_earn($driver_rtype,$driver_rate,$_trip['rate']);
                
                $ddd = [
                    'description' => $this->driver_desc($driver_rtype,$driver_rate),
                    'amount_f' => $driver_earn,
                    'misc_amount_f' => '0.00',
                    'total' => $driver_earn,
                    'created_at' => $_trip['created_at'],
                ];
                $ddd2 = [
                    'description' => $this->dispatcher_desc($dispatcher_rtype,$dispatcher_rate),
                    'amount_f' => $dispatcher_earn,
                    'misc_amount_f' => '0.00',
                    'total' => $dispatcher_earn,
                    'created_at' => $_trip['created_at'],
                ];
                array_push($added, $this->clean_n($ddd['total']));
                array_push($added, $this->clean_n($ddd2['total']));
                array_push($autodriver, $ddd);
                array_push($autodispatcher, $ddd2);
            endforeach;
            $data = array_merge($data, $autodriver, $autodispatcher);
        }
        return [$data, number_format(array_sum($added), 2) ];
    }
    protected function driver_desc($type, $r)
    {
        if(intval($type) == 2)
        {
            return "Driver's pay(Rate $".$r.")";
        }
        return "Driver's pay(Rate ".$r."%)";
    }
    protected function dispatcher_desc($type, $r)
    {
        if(intval($type) == 2)
        {
            return "Dispatcher's pay(Rate $".$r.")";
        }
        return "Dispatcher's pay(Rate ".$r."%)";
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
}
