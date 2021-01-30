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
use App\Models\User;
use App\Models\Advance;

class AdvanceController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'payfrom' => 'required|string',
            'payto' => 'required|string',
            'user' => 'required|string|not_in:nn',
            'user_type' => 'required|string|not_in:nn',
            'amount' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        $created = Advance::create($input)->id;
        return response([
            'status' => 200,
            'message' => 'Advance payment entry created successfully',
            'id' => $created,
            'data' => $this->find_advances(),
        ], 200);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'payfrom' => 'required|string',
            'payto' => 'required|string',
            'user' => 'required|string|not_in:nn',
            'user_type' => 'required|string|not_in:nn',
            'amount' => 'required|string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $input = $req->all();
        Advance::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'Advance payment entry updated successfully',
            'id' => $id,
            'data' => $this->find_advances(),
        ], 200);
    }
    public function find($id)
    {
        $data = Advance::find($id);
        return response([
            'status' => 200,
            'message' => 'Advance payment entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function users($type)
    {
        if( $type == 'nn')
        {
            $type = 1;
        }
        if( intval($type) == 1 )
        {
            $drivers = $dispatchers = [];
            $d = Driver::where('is_active', true)->get();
            if( !is_null($d) )
            {
                $drivers = $d->toArray();
            }
            return response([
                'status' => 200,
                'message' => 'Advance payment entry fetched successfully',
                'data' => $drivers,
            ], 200);
        }

        if( intval($type) == 2 )
        {
            $ds = User::where('is_active', true)->get();
            if( !is_null($ds) )
            {
                $dispatchers = $ds->toArray();
            }
            return response([
                'status' => 200,
                'message' => 'Advance payment entry fetched successfully',
                'data' => $dispatchers,
            ], 200);
        }        
    }
    public function findall()
    {
        $data = [];
        $p = Advance::where('is_active', true)->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Advance payment entries fetched successfully',
            'data' => $this->format_advances($data),
        ], 200);
    }
    public function drop($id)
    {
        Advance::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Advance payment entry deleted successfully',
            'id' => null,
        ], 200);
    }
    protected function find_advances()
    {
        $data = [];
        $p = Advance::where('is_active', true)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $this->format_advances($data);
    }
    protected function format_advances($in)
    {
        $data = [];
        foreach( $in as $_advance ):
            $la = $this->find_user_labels($_advance['user'], $_advance['user_type']);
            $_advance['user_label'] = $la;
            $_advance['created_at'] = date('Y-m-d', strtotime($_advance['created_at']));
            array_push($data, $_advance);
        endforeach;

        return $data;
    }
    protected function find_user_labels($user, $type)
    {
        $dlabel = 'none';
        if( intval($type) == 1 )
        {
            $d = Driver::find($user);
            if(!is_null($d))
            {
                $dlabel = $d->fname . ' ' . $d->lname;
            }
        }
        elseif( intval($type) == 2 )
        {
            $d = User::find($user);
            if(!is_null($d))
            {
                $dlabel = $d->fname . ' ' . $d->lname;
            }
        }
        return $dlabel;
    }
}
