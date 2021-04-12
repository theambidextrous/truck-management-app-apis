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
        $input = $req->all();
        $account = Auth::user()->account;
        $input['account'] = $account;
        /** user meta */
        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = Auth::user();
        $user['token'] = $accessToken;
        /** end meta */
        $fax = $req->get('fax');
        if(!strlen($req->get('fax')))
        {
            $fax = 'N/A';
        }
        $s = Setup::where('account', $account)->first();
        $s->company = $req->get('company');
        $s->address = $req->get('address');
        $s->city = $req->get('city');
        $s->state = $req->get('state');
        $s->zip = $req->get('zip');
        $s->email = $req->get('email');
        $s->phone = $req->get('phone');
        $s->fax = $fax;
        $s->save();
        $user['has_expired'] = $this->account_expired();
        $user['is_near'] = $this->account_is_near_expiry();
        $user['has_setup'] = Setup::where('account', $account)
            ->where('email', '!=', null)->count();
        return response([
            'status' => 200,
            'message' => 'Setup info updated successfully',
            'id' => $s->id,
            'udata' => $user,
        ], 200);
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
        $account = Auth::user()->account;
        $d = Setup::where('account', $account)->first();
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
        $account = Auth::user()->account;
        $accessToken = Auth::user()->createToken('authToken')->accessToken;
        $user = Auth::user();
        $user['token'] = $accessToken;
        $user['has_expired'] = $this->account_expired();
        $user['has_setup'] = Setup::where('account', $account)
            ->where('email', '!=', null)->count();
        return response([
            'status' => 200,
            'message' => 'Success. new data',
            'data' => $user,
        ], 200);
    }

    protected function account_expired()
    {
        $account = Auth::user()->account;
        $co = Setup::where('account', $account)->first();
        if(is_null($co) || is_null($co->active_to))
        {
            return 1;
        }
        $exp = date('Y-m-d', strtotime($co->active_to));
        $now = date('Y-m-d');
        if( $now > $exp )
        {
            return 1;
        }
        return 0;
    }
    protected function account_is_near_expiry()
    {
        $account = Auth::user()->account;
        $co = Setup::where('account', $account)->first();
        if(is_null($co) || is_null($co->active_to))
        {
            return 1;
        }
        $exp = strtotime($co->active_to);
        $now = time();
        $diff = $exp - $now;
        if( $diff < 0 ){ return 1; }
        $days = round($diff / (60 * 60 * 24));
        if($days <= 7 )
        {
            return 1;
        }
        return 0;
    }
}
