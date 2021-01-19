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

use App\Models\Truck;
use App\Models\Owner;

class TruckController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'number' => 'required|string',
            'owner' => 'required|string|not_in:nn',
            'make' => 'required|string',
            'vin' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        $input['number'] = strtoupper($input['number']);
        $input['make'] = strtoupper($input['make']);
        $input['vin'] = strtoupper($input['vin']);
        $created = Truck::create($input)->id;
        return response([
            'status' => 200,
            'message' => 'Truck entry created successfully',
            'id' => $created,
            'data' => $this->find_trucks(),
        ], 200);
    }
    public function import(Request $req)
    {
        try
        {
            if( !$req->hasfile('csv') )
            {
                return response([
                    'status' => 201,
                    'message' => 'Truck Import error. No CSV file uploaded',
                    'id' => null,
                ], 403);
            }
            $csv = $req->file('csv');
            $extension = $csv->getClientOriginalExtension();
            if( strtolower($extension) != 'csv')
            {
                return response([
                    'status' => 201,
                    'message' => 'Truck Import error. Invalid CSV file extension',
                    'id' => null,
                ], 403);
            }
            $file_name = (string)Str::uuid() . '.' . $extension;
            Storage::disk('local')->putFileAs('cls', $csv, $file_name);
            $csv_data = Truck::csvToArray($file_name);
            $created = [];
            foreach( $csv_data as $_truck ):
                if(!isset( $_truck['number'] ))
                {
                    return response([
                        'status' => 201,
                        'message' => 'Import error. Uploaded file has no "number" column',
                        'id' => [],
                    ], 403);
                }
                if(!isset( $_truck['make'] ))
                {
                    return response([
                        'status' => 201,
                        'message' => 'Import error. Uploaded file has no "make" column',
                        'id' => [],
                    ], 403);
                }
                if(!isset( $_truck['owner'] ))
                {
                    return response([
                        'status' => 201,
                        'message' => 'Import error. Uploaded file has no "owner" column',
                        'id' => [],
                    ], 403);
                }
                if(!isset( $_truck['vin'] ))
                {
                    return response([
                        'status' => 201,
                        'message' => 'Import error. Uploaded file has no "vin" column',
                        'id' => [],
                    ], 403);
                }
                $_truck['number'] = strtoupper($_truck['number']);
                $_truck['make'] = strtoupper($_truck['make']);
                $_truck['vin'] = strtoupper($_truck['vin']);
                if( !intval($_truck['owner']))
                {
                    $_truck['owner'] = 0;
                }
                $created[] = Truck::create($_truck)->id;
            endforeach;
            return response([
                'status' => 200,
                'message' => count($created) . ' truck entries created successfully',
                'id' => [],
                'data' => $this->find_trucks(),
            ], 200);
        }catch( Exception $e )
        {
            return response([
                'status' => 201,
                'message' => $e->getMessage(),
                'id' => [],
            ], 403);
        }
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'number' => 'required|string',
            'owner' => 'required|string',
            'make' => 'required|string',
            'vin' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        $input['number'] = strtoupper($input['number']);
        $input['make'] = strtoupper($input['make']);
        $input['vin'] = strtoupper($input['vin']);
        Truck::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'Truck entry updated successfully',
            'id' => $id,
            'data' => $this->find_trucks(),
        ], 200);
    }
    public function find($id)
    {
        $data = Truck::find($id);
        return response([
            'status' => 200,
            'message' => 'Truck entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        $data = [];
        $p = Truck::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Truck entries fetched successfully',
            'data' => $this->format_trucks($data),
        ], 200);
    }
    public function drop($id)
    {
        Truck::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Truck entry deleted successfully',
            'id' => null,
        ], 200);
    }
    protected function find_trucks()
    {
        $data = [];
        $p = Truck::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $this->format_trucks($data);
    }
    protected function format_trucks($in)
    {
        $data = [];
        foreach( $in as $_truck ):
            $_truck['company'] = $this->find_co_name($_truck['owner']);
            array_push($data, $_truck);
        endforeach;

        return $data;
    }
    protected function find_co_name($owner)
    {
        $p = Owner::find($owner);
        if(is_null($p))
        {
            return 'None';
        }
        return $p->company;
    }
}
