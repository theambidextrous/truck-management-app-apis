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
use App\Models\Driver;

class LoadController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'booking_date' => 'required|string',
            'number' => 'required|string',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'rate' => 'required|string',
            'weight' => 'required|string',
            'truck' => 'required|string|not_in:nn',
            'driver' => 'required|string|not_in:nn',
            'mileage' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        if($this->exists_load_number($input['number']))
        {
            return response([
                'status' => 201,
                'message' => 'Data error. Load number is already used. Enter a different number',
                'errors' => [],
            ], 403);
        }
        $input['dispatcher'] = Auth::user()->id;
        // $input['number'] = (string)Str::uuid();
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
            'booking_date' => 'required|string',
            'number' => 'required|string',
            'origin' => 'required|string',
            'destination' => 'required|string',
            'rate' => 'required|string',
            'weight' => 'required|string',
            'truck' => 'required|string|not_in:nn',
            'driver' => 'required|string|not_in:nn',
            'mileage' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        if($this->exists_load_number($input['number']))
        {
            return response([
                'status' => 201,
                'message' => 'Data error. Load number is already used. Enter a different number',
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
        $data = [];
        $p = Load::where('is_active', true)->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Load entries fetched successfully',
            'data' => $this->format_loads($data),
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
            $la = $this->find_load_labels($_load['truck'],$_load['driver']);
            $_load['truck_label'] = $la[0];
            $_load['driver_label'] = $la[1];
            array_push($data, $_load);
        endforeach;

        return $data;
    }
    protected function find_load_labels($truck, $driver)
    {
        $dlabel = 'none';
        $tlabel = 'none';
        $d = Driver::find($driver);
        if(!is_null($d))
        {
            $dlabel = $d->fname . ' ' . $d->lname;
        }

        $t = Truck::find($truck);
        if(!is_null($t))
        {
            $tlabel = $t->make . '-' . $t->vin;
        }
        return [ $tlabel, $dlabel ];
    }
    protected function exists_load_number($no)
    {
        return Load::where('number', $no)->count() > 0;
    }
}
