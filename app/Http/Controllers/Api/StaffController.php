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

use App\Models\User;

/** mail */
use Illuminate\Support\Facades\Mail;
use App\Mail\NewSignUp;
use App\Mail\Code;

class StaffController extends Controller
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
            'user_type' => 'required|string|not_in:nn',
            'rate_type' => 'required|not_in:nn',
            'rate' => 'required',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        $input['account'] = Auth::user()->account;
        if(intval($input['rate_type']) == 1 && intval($input['rate']) >= 50 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check dispatcher earnings rate value. Dispatchers should not earn more than 50%',
                'errors' => [],
            ], 403);
        }
        if( intval($input['rate']) < 1 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check dispatcher earnings rate value. Value should be more than 1',
                'errors' => [],
            ], 403);
        }
        if( $this->exists_mail($input['email']) )
        {
            return response([
                'status' => 201,
                'message' => 'Email address already used. Try another.',
                'errors' => [],
            ], 403);
        }
        if( $this->exists_phone($input['phone']) )
        {
            return response([
                'status' => 201,
                'message' => 'Phone number already used. Try another.',
                'errors' => [],
            ], 403);
        }
        $input['dpass'] = substr((string)Str::uuid(), 11, 10);
        $input['password'] = Hash::make($input['dpass']);
        $created = User::create($input)->id;
        Mail::to($input['email'])->send(new NewSignUp($input));
        return response([
            'status' => 200,
            'message' => 'User entry created successfully. Login information has been sent via email',
            'id' => $created,
            'data' => $this->find_staffs(),
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
            'user_type' => 'required|not_in:nn',
            'rate_type' => 'required|not_in:nn',
            'rate' => 'required',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        $input['account'] = Auth::user()->account;
        if(intval($input['rate_type']) == 1 && intval($input['rate']) >= 50 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check dispatcher earnings rate value. Dispatchers should not earn more than 50%',
                'errors' => [],
            ], 403);
        }
        if( intval($input['rate']) < 1 )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Check dispatcher earnings rate value. Value should be more than 1',
                'errors' => [],
            ], 403);
        }
        if( $this->exists_mail($input['email'], $id) )
        {
            return response([
                'status' => 201,
                'message' => 'Email address already used. Try another.',
                'errors' => [],
            ], 403);
        }
        if( $this->exists_phone($input['phone'], $id) )
        {
            return response([
                'status' => 201,
                'message' => 'Phone number already used. Try another.',
                'errors' => [],
            ], 403);
        }
        User::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'User entry updated successfully',
            'id' => $id,
            'data' => $this->find_staffs(),
        ], 200);
    }
    public function find($id)
    {
        $data = User::find($id);
        return response([
            'status' => 200,
            'message' => 'User entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = User::where('is_active', true)
            ->where('account', $account)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'User entries fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function drop($id)
    {
        User::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'User entry deleted successfully',
            'id' => null,
        ], 200);
    }
    protected function exists_mail($email, $id = 0)
    {
        if( $id > 0 )
        {
            return User::where('email', $email)->where('id', '!=', $id)->count() > 0;
        }
        return User::where('email', $email)->count() > 0;
    }
    protected function exists_phone($phone, $id = 0)
    {
        if( $id > 0 )
        {
            return User::where('phone', $phone)->where('id', '!=', $id)->count() > 0;
        }
        return User::where('phone', $phone)->count() > 0;
    }
    protected function find_staffs()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = User::where('is_active', true)
            ->where('account', $account)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $data;
    }
}
