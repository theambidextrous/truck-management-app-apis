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

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\Truck;

class ExpenseController extends Controller
{
    public function g_add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string',
            'description' => 'string',
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
        $created = ExpenseGroup::create($input)->id;
        return response([
            'status' => 200,
            'message' => 'Category entry created successfully',
            'id' => $created,
            'data' => $this->find_exp_groups(),
        ], 200);
    }
    public function g_edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string',
            'description' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'A required field was not found',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        if( ExpenseGroup::find($id)->account == 1 )
        {
            return response([
                'status' => 201,
                'message' => 'Could not update system values',
                'id' => $id,
                'data' => [],
            ], 403);
        }
        $input = $req->all();
        // $input['account'] = Auth::user()->account;
        ExpenseGroup::find($id)->update($input);
        return response([
            'status' => 200,
            'message' => 'Category entry updated successfully',
            'id' => $id,
            'data' => $this->find_exp_groups(),
        ], 200);
    }
    public function g_find($id)
    {
        $data = ExpenseGroup::find($id);
        return response([
            'status' => 200,
            'message' => 'Category entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function g_findall()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = ExpenseGroup::whereIn('account', [$account, 1])->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Category entries fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function g_drop($id)
    {
        ExpenseGroup::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Category entry deleted successfully',
            'id' => null,
        ], 200);
    }




    /** expes */
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'type' => 'required|string|not_in:nn',
            'truck' => 'required|string|not_in:nn',
            'amount' => 'required|string',
            'description' => 'required|string',
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
        try
        {
            if( intval($input['type']) == 1)
            {
                $this->validate_scheduled($input);
                $input['city'] = null;
                $input['state'] = null;
                $input['misc_amount'] = 0;
            }
            else
            {
                $this->validate_other($input); 
                $input['installment'] = 0;
                $input['frequency'] = null;
            }
            $created = Expense::create($input)->id;
            return response([
                'status' => 200,
                'message' => 'Expense entry created successfully',
                'id' => $created,
                'data' => $this->find_expenses(),
            ], 200);
        }catch( \Exception $e )
        {
            return response([
                'status' => 201,
                'message' => $e->getMessage(),
                'id' => null,
            ], 403);
        }
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'type' => 'required|string|not_in:nn',
            'truck' => 'required|string|not_in:nn',
            'amount' => 'required|string',
            'description' => 'required|string',
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
        try
        {
            if( intval($input['type']) == 1)
            {
                $this->validate_scheduled($input);
                $input['city'] = null;
                $input['state'] = null;
                $input['misc_amount'] = 0;
            }
            else
            {
                $this->validate_other($input); 
                $input['installment'] = 0;
                $input['frequency'] = null;
            }
            Expense::find($id)->update($input);
            return response([
                'status' => 200,
                'message' => 'Expense entry updated successfully',
                'id' => $id,
                'data' => $this->find_expenses(),
            ], 200);
        }catch( \Exception $e )
        {
            return response([
                'status' => 201,
                'message' => $e->getMessage(),
                'id' => null,
            ], 403);
        }
    }
    public function find($id)
    {
        $data = Expense::find($id);
        return response([
            'status' => 200,
            'message' => 'Expense entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = Expense::where('is_active', true)
            ->where('account', $account)->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Expense entries fetched successfully',
            'data' => $this->format_expenses($data),
        ], 200);
    }
    public function by_truck(Request $req, $id)
    {
        $account = Auth::user()->account;
        $validator = Validator::make($req->all(), [
            'from_date' => 'string',
            'to_date' => 'string',
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Either "Date from" or "Date to" is missing',
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $from_date = date('Y-m-d', strtotime($req->get('from_date')));
        $to_date = date('Y-m-d', strtotime($req->get('to_date')));
        $data = [];
        if( $to_date < $from_date )
        {
            return response([
                'status' => 201,
                'message' => '"Date from" cannot be greater than "Date to"',
                'errors' => [],
                'data' => $data,
            ], 403);
        }
        if(!strlen($req->get('from_date')) || !strlen($req->get('to_date')))
        {
            $p = Expense::where('is_active', true)
                ->where('account', $account)->where('truck', $id)->get();
            if(!is_null($p)){ $data = $p->toArray();}
            return response([
                'status' => 200,
                'message' => 'data without dates',
                'data' => $this->format_expenses($data),
            ], 200);
        }
        $p = Expense::where('is_active', true)
            ->where('truck', $id)
            ->where('account', $account)
            ->where('created_at', '>=', $from_date)
            ->where('created_at', '<=', $to_date)
            ->get();
        if(!is_null($p)){ $data = $p->toArray();}
        return response([
            'status' => 200,
            'message' => 'data found with dates',
            'data' => $this->format_expenses($data),
        ], 200);
    }
    public function drop($id)
    {
        Expense::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Expense entry deleted successfully',
            'id' => null,
        ], 200);
    }

    protected function validate_scheduled($data)
    {
        
        if(!strlen($data['installment']))
        {
            throw new \Exception('installment field is required for scheduled expenses.');
        }
        if(!strlen($data['frequency']))
        {
            throw new \Exception('Frequency field is required for scheduled expenses.');
        }
    }
    protected function validate_other($data)
    {
        if(!strlen($data['city']))
        {
            throw new \Exception('City field is required for non-scheduled expenses.');
        }
        if($data['state'] == 'nn' || !strlen($data['state']))
        {
            throw new \Exception('State field is required for non-scheduled expenses.');
        }
        if(!strlen($data['misc_amount']))
        {
            throw new \Exception('Misc amount field is required for non-scheduled expenses.');
        }
    }
    protected function find_exp_groups()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = ExpenseGroup::whereIn('account', [$account, 1])->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $data;
    }
    protected function find_expenses()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = Expense::where('is_active', true)
            ->where('account', $account)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $this->format_expenses($data);
    }
    protected function format_expenses($in)
    {
        $data = [];
        foreach( $in as $_expense ):
            $la = $this->find_expense_labels($_expense['type'],$_expense['truck']);
            $_expense['glabel'] = $la[0];
            $_expense['tlabel'] = $la[1];
            array_push($data, $_expense);
        endforeach;

        return $data;
    }
    protected function find_expense_labels($type, $truck)
    {
        $glabel = 'none';
        $tlabel = 'none';
        $g = ExpenseGroup::find($type);
        if(!is_null($g))
        {
            $glabel = $g->name;
        }

        $t = Truck::find($truck);
        if(!is_null($t))
        {
            $tlabel = $t->make . ' - ' . $t->number;
        }
        return [ $glabel, $tlabel ];
    }
}
