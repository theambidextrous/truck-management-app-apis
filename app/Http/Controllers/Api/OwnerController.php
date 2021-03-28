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
use DB;
use Carbon\Carbon;

use App\Models\Owner;
use App\Models\Freport;

class OwnerController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'company' => 'required|string',
            // 'fname' => 'required|string',
            // 'lname' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $created = Owner::create($req->all())->id;
        return response([
            'status' => 200,
            'message' => 'Entry created successfully',
            'id' => $created,
            'data' => $this->find_owners(),
        ], 200);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'company' => 'required|string',
            // 'fname' => 'required|string',
            // 'lname' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        Owner::find($id)->update($req->all());
        return response([
            'status' => 200,
            'message' => 'Entry updated successfully',
            'id' => $id,
            'data' => $this->find_owners(),
        ], 200);
    }
    public function find($id)
    {
        $data = Owner::find($id);
        return response([
            'status' => 200,
            'message' => 'Entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        return response([
            'status' => 200,
            'message' => 'Entries fetched successfully',
            'data' => $this->find_owners(),
            'next_report' => $this->find_next_rpt(),
        ], 200);
    }
    protected function find_next_rpt()
    {
        $prefix = '1000';
        $d = Freport::select('id')->where('id', '!=', 0)->first();
        if(is_null($d))
        {
            return $prefix . '1';
        }
        return  $prefix . $d->id;
    }
    protected function find_owners()
    {
        $data = [];
        $p = Owner::where('is_active', true)->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $data;
    }
    public function drop($id)
    {
        Owner::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Entry deleted successfully',
            'id' => null,
        ], 200);
    }
}
