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

use App\Models\Driver;

class DriverController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'fname' => 'required|string',
            'lname' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'license' => 'required|string',
            'experience' => 'required|string|not_in:nn',
            'rate_type' => 'required|string|not_in:nn',
            'rate' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        if(intval($input['rate_type']) == 1 && intval($input['rate']) >= 50 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check driver earnings rate value. Drivers should not earn more than 50%',
                'errors' => [],
            ], 403);
        }
        if( intval($input['rate']) < 1 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check driver earnings rate value. Value should be more than 1',
                'errors' => [],
            ], 403);
        }
        $created = Driver::create($input)->id;
        return response([
            'status' => 200,
            'message' => 'Driver entry created successfully',
            'id' => $created,
            'data' => $this->find_drivers(),
        ], 200);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'fname' => 'required|string',
            'lname' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'license' => 'required|string',
            'experience' => 'required|string|not_in:nn',
            'rate_type' => 'required|string|not_in:nn',
            'rate' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        if(intval($input['rate_type']) == 1 && intval($input['rate']) >= 50 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check driver earnings rate value. Drivers should not earn more than 50%',
                'errors' => [],
            ], 403);
        }
        if( intval($input['rate']) < 1 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check driver earnings rate value. Value should be more than 1',
                'errors' => [],
            ], 403);
        }
        Driver::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'Driver entry updated successfully',
            'id' => $id,
            'data' => $this->find_drivers(),
        ], 200);
    }
    public function find($id)
    {
        $data = Driver::find($id);
        return response([
            'status' => 200,
            'message' => 'Driver entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        $data = [];
        $p = Driver::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Driver entries fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function drop($id)
    {
        Driver::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Driver entry deleted successfully',
            'id' => null,
        ], 200);
    }
    protected function find_drivers()
    {
        $data = [];
        $p = Driver::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $data;
    }
}
