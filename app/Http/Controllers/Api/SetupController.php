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

use App\Models\Setup;
use App\Models\User;

class SetupController extends Controller
{
    public function set(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'company' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            // 'fax' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }

        /** user meta */
        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = Auth::user();
        $user['token'] = $accessToken;
        $user['has_setup'] = Setup::count();
        /** end meta */
        $fax = $req->get('fax');
        if(!strlen($req->get('fax')))
        {
            $fax = 'N/A';
        }
        if( Setup::count() > 0 )
        {
            $s = Setup::where('id', '!=', 0)->first();
            $s->company = $req->get('company');
            $s->address = $req->get('address');
            $s->city = $req->get('city');
            $s->state = $req->get('state');
            $s->zip = $req->get('zip');
            $s->email = $req->get('email');
            $s->phone = $req->get('phone');
            $s->fax = $fax;
            $s->save();
            return response([
                'status' => 200,
                'message' => 'Setup info updated successfully',
                'id' => $s->id,
                'udata' => $user,
            ], 200);
        }
        else
        {
            $input = $req->all();
            $input['fax'] = $fax;
            $created = Setup::create($input)->id;
            return response([
                'status' => 200,
                'message' => 'Setup info updated successfully',
                'id' => $created,
                'udata' => $user,
            ], 200);
        }
    }
    public function find()
    {
        $data = [
            'company' => '',
            'address' => '',
            'city' => '',
            'state' => 'nn',
            'zip' => '',
            'email' => '',
            'phone' => '',
            'fax' => '',
        ];
        $d = Setup::where('id', '!=', 0)->first();
        if(!is_null($d))
        {
            $data = $d->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Setup info found',
            'data' => $data,
        ], 200);
    }

    public function refresh()
    {
        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = Auth::user();
        $user['token'] = $accessToken;
        $user['has_setup'] = Setup::count();
        return response([
            'status' => 200,
            'message' => 'Success. new data',
            'data' => $user,
        ], 200);
    }
}
