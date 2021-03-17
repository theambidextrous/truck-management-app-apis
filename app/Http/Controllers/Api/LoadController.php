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

use App\Models\Load;
use App\Models\Truck;
use App\Models\Broker;
use App\Models\Driver;

class LoadController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'date' => 'required|string',
            'bol' => 'required|string',
            'company' => 'required|string',
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|not_in:nn',
            'zip' => 'required|string',
            'broker' => 'required|string',
            'd_date' => 'required|string',
            'pol' => 'required|string',
            'd_company' => 'required|string',
            'd_street' => 'required|string',
            'd_city' => 'required|string',
            'd_state' => 'required|string|not_in:nn',
            'd_zip' => 'required|string',
            'truck' => 'required|string|not_in:nn',
            'trailer' => 'required|string',
            'miles' => 'required',
            'weight' => 'required|string',
            'rate' => 'required|string',
            'driver_a' => 'required|string|not_in:nn',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Missing data error. All fields with an Asteric(*) are required' . json_encode($validator->errors()->all()),
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        try
        {
            $this->has_expired_docs($input['truck']);
        }
        catch( Exception $excep )
        {
            return response([
                'status' => 201,
                'message' => $excep->getMessage(),
                'errors' => [],
            ], 403);
        }
        if($this->exists_load_number($input['bol']))
        {
            return response([
                'status' => 201,
                'message' => 'Data error. BOL number is already used. Enter a different number',
                'errors' => [],
            ], 403);
        }
        $input['dispatcher'] = Auth::user()->id;
        $created = Load::create($input)->id;
        return response([
            'status' => 200,
            'message' => 'Load entry created successfully',
            'id' => $created,
            'data' => $this->find_loads(),
        ], 200);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'date' => 'required|string',
            'bol' => 'required|string',
            'company' => 'required|string',
            'street' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|not_in:nn',
            'zip' => 'required|string',
            'broker' => 'required|string',
            'd_date' => 'required|string',
            'pol' => 'required|string',
            'd_company' => 'required|string',
            'd_street' => 'required|string',
            'd_city' => 'required|string',
            'd_state' => 'required|string|not_in:nn',
            'd_zip' => 'required|string',
            'truck' => 'required|string|not_in:nn',
            'trailer' => 'required|string',
            'miles' => 'required',
            'weight' => 'required|string',
            'rate' => 'required|string',
            'driver_a' => 'required|string|not_in:nn',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Missing data error. All fields with an Asteric(*) are required',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        try
        {
            $this->has_expired_docs($input['truck']);
        }
        catch( Exception $excep )
        {
            return response([
                'status' => 201,
                'message' => $excep->getMessage(),
                'errors' => [],
            ], 403);
        }
        Load::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'Load entry updated successfully',
            'id' => $id,
            'data' => $this->find_loads(),
        ], 200);
    }
    public function find($id)
    {
        $data = Load::find($id);
        return response([
            'status' => 200,
            'message' => 'Load entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        return response([
            'status' => 200,
            'message' => 'Load entries fetched successfully',
            'data' => $this->find_loads(),
        ], 200);
    }
    public function drop($id)
    {
        Load::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Load entry deleted successfully',
            'id' => null,
        ], 200);
    }
    public function loadUpload(Request $req, $id)
    {
        try {
            if( !$req->hasfile('delivery_docs') )
            {
                return response([
                    'status' => 201,
                    'message' => 'Upload error. No valid file uploaded',
                    'id' => null,
                ], 403);
            }
            $delivery_docs = $req->file('delivery_docs');
            $extension = $delivery_docs->getClientOriginalExtension();
            if( !in_array(strtolower($extension), ['png', 'jpg', 'jpeg', 'pdf']) )
            {
                return response([
                    'status' => 201,
                    'message' => 'Upload error. Kindly upload an IMAGE or a PDF document',
                    'id' => null,
                ], 403);
            }
            $file_name = (string)Str::uuid() . '.' . $extension;
            Storage::disk('local')->putFileAs('cls', $delivery_docs, $file_name);
            Load::find($id)->update([
                'delivery_docs' => $file_name,
                'is_delivered' => true,
            ]);
            return response([
                'status' => 200,
                'message' => 'Cargo entry has updated from dispatched to delivered',
                'data' => null,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'status' => 201,
                'message' => 'File upload error. Check the file size. maximum allowed is 20mb',
                'data' => null,
            ], 403);
        }
    }
    public function brokers()
    {
        $b = Broker::where('id', '!=', 0)->orderBy('name', 'asc')->get();
        if( !is_null($b) )
        {
            $b = $b->toArray();
        }
        else
        {
            $b = [];
        }
        return response([
            'status' => 200,
            'message' => null,
            'data' => $b,
        ], 200);
    }
    protected function find_loads()
    {
        $data = [];
        $p = Load::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $this->format_loads($data);
    }
    protected function format_loads($in)
    {
        $data = [];
        foreach( $in as $_load ):
            $la = $this->find_load_labels($_load['truck'],$_load['driver_a'], $_load['driver_b']);
            $_load['truck_label'] = $la[0];
            $_load['driver_label'] = $la[1];
            $_load['driverb_label'] = $la[2];
            $brok = Broker::find($_load['broker']);
            if(!is_null($brok))
            {
                $_load['broker_label'] = $brok->name;
            }
            array_push($data, $_load);
        endforeach;

        return $data;
    }
    protected function find_load_labels($truck, $driver, $driver_b)
    {
        $dlabel = 'none';
        $dblabel = 'none';
        $tlabel = 'none';
        $d = Driver::find($driver);
        if(!is_null($d))
        {
            $dlabel = $d->fname . ' ' . $d->lname;
        }
        if(!is_null($driver_b) && $driver_b != 'nn')
        {
            $d = Driver::find($driver_b);
            if(!is_null($d))
            {
                $dblabel = $d->fname . ' ' . $d->lname;
            }
        }
        $t = Truck::find($truck);
        if(!is_null($t))
        {
            $tlabel = $t->make . '-' . $t->number;
        }
        return [ $tlabel, $dlabel, $dblabel ];
    }
    protected function exists_load_number($no)
    {
        return Load::where('bol', $no)->count() > 0;
    }
    protected function has_expired_docs($truck)
    {
        $now = date('Y-m-d');
        $truck_meta = Truck::find($truck);
        if(is_null($truck_meta))
        {
            return;
        }

        if( $now > date('Y-m-d', strtotime($truck_meta->insurance_expires)) )
        {
            throw new \Exception('Error. The insurance cover for the selected truck has expired.');
        }
        if( $now > date('Y-m-d', strtotime($truck_meta->inspection_expires)) )
        {
            throw new \Exception('Error. This truck requires Inspection once again.');
        }
        if( $now > date('Y-m-d', strtotime($truck_meta->registration_expires)) )
        {
            throw new \Exception('Error. The registration for the selected truck has expired.');
        }
        return;
    }
    public function getDistance($from, $to)
    {
        try{
            $cURLConnection = curl_init("https://maps.googleapis.com/maps/api/distancematrix/json?origins=". $from ."&destinations=". $to ."&mode=driving&units=imperial&sensor=false&key=". Config::get('app.mapskey'));
            curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
            $apiResponse = curl_exec($cURLConnection);
            $apiResponse = json_decode($apiResponse, true);
            $data = 0;
            if( isset($apiResponse['rows']) )
            {
                $d = $apiResponse['rows'][0]['elements'][0];
                if(isset($d['distance']))
                {
                    $fdata = $d['distance']['value'];
                    $data = round($fdata/1609.34, 1);
                }
            }
            curl_close($cURLConnection);
            return response([
                'status' => 200,
                'message' => null,
                'data' => $data,
            ], 200);
        }catch( \Throwable $e )
        {
            return response([
                'status' => 200,
                'message' => $e->getMessage(),
                'data' => 0.00,
            ], 403);
        }
    }
}
