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
use App\Models\Expense;
use App\Models\Deduction;

class DeductionController extends Controller
{
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'expense' => 'required|not_in:nn',
            'deducted' => 'required|string',
            'payment_date' => 'required|string',
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
        $input['initiator'] = Auth::user()->id;
        $exp_meta = Expense::find($input['expense']);
        $paid_sum = $this->find_paid_so_far($input['expense']);
        $bal = $exp_meta->amount - $paid_sum;
        $post_amt = $input['deducted'];
        $is_paid = false;
        $next_due = $this->find_next_due($input['payment_date'], $exp_meta->frequency);
        $now = date('Y-m-d');
        $pd = date('Y-m-d', strtotime($input['payment_date']));
        if( $input['deducted'] < $exp_meta->installment )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Deduction amount is less than the ' . $exp_meta->frequency . ' installment of $' . $exp_meta->installment,
                'errors' => [],
            ], 403);
        }
        if( $input['deducted'] > $exp_meta->installment )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Deduction amount is more than the ' . $exp_meta->frequency . ' installment of $' . $exp_meta->installment,
                'errors' => [],
            ], 403);
        }
        if( $now > $pd )
        {
            return response([
                'status' => 201,
                'message' => 'Error. Deduction date is in the past. It should be today or in the future',
                'errors' => [],
            ], 403);
        }
        if( $exp_meta->installment > $bal )
        {
            $post_amt = $bal;
            $is_paid = true;
            $next_due = null;
        }
        $pay_load = [
            'account' => $account,
            'expense' => $input['expense'],
            'deducted' => $post_amt,
            'payment_date' => $input['payment_date'],
            'initiator' => $input['initiator'],
        ];
        $created = Deduction::create($pay_load)->id;
        if( $created > 0 )
        {
            Expense::find($input['expense'])->update([
                'is_paid' => $is_paid,
                'next_due' => $next_due,
            ]);
        }
        return response([
            'status' => 200,
            'message' => 'Deduction entry created successfully',
            'id' => $created,
            'data' => $this->find_exp_deductions(),
        ], 200);
    }
    public function edit(Request $req, $id)
    {
        return true;
    }
    public function find($id)
    {
        $data = Deduction::find($id)->toArray();
        return response([
            'status' => 200,
            'message' => 'Deduction entry fetched successfully',
            'data' => $data,
        ], 200);
    }
    public function findall()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = Deduction::where('account', $account)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Deduction entries fetched successfully',
            'data' => $this->format_ded($data),
        ], 200);
    }
    public function drop($id)
    {
        Deduction::find($id)->update([ 'is_active' => false ]);
        return response([
            'status' => 200,
            'message' => 'Deduction entry deleted successfully',
            'id' => null,
        ], 200);
    }
    public function find_scheduled()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = Expense::where('type', 1)
            ->where('is_paid', false)
            ->where('account', $account)
            ->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return response([
            'status' => 200,
            'message' => 'Entries fetched successfully',
            'data' => $this->format_sch($data),
        ], 200);
    }
    protected function find_exp_deductions()
    {
        $data = [];
        $account = Auth::user()->account;
        $p = Deduction::where('account', $account)->orderBy('id', 'desc')->get();
        if(!is_null($p))
        {
            $data = $p->toArray();
        }
        return $this->format_ded($data);
    }
    protected function find_paid_so_far($id)
    {
        $account = Auth::user()->account;
        $d = Deduction::where('expense', $id)->where('account', $account)->sum('deducted');
        return $d;
    }
    protected function find_next_due($date, $period)
    {
        if( strtoupper($period) == 'WEEKLY')
        {
            return date('Y-m-d', strtotime($date . ' +7 day'));
        }
        return date('Y-m-d', strtotime($date . ' +30 day'));
    }
    protected function format_sch($data)
    {
        $rtn = [];
        foreach( $data as $_data ):
            $_data['label'] = $_data['description'] . ' (' . $this->find_exp_label($_data['truck']) . ')';
            $_data['datelabel'] = date('m/d/Y', strtotime($_data['payment_date']));
            array_push($rtn, $_data);
        endforeach;

        return $rtn;
    }
    protected function format_ded($data)
    {
        $rtn = [];
        foreach( $data as $_data ):
            $exp_meta = Expense::find($_data['expense']);
            if(!is_null($exp_meta))
            {
                $desc = $exp_meta->description;
                $truck = $this->find_exp_label($exp_meta->truck);
                $_data['explabel'] = $desc . '(' . $truck . ')';
                $_data['total'] = $exp_meta->amount;
                $_data['paid_sf'] = $this->find_paid_so_far($_data['expense']);
                $_data['bal'] = $_data['total'] - $_data['paid_sf'];
                array_push($rtn, $_data);
            }
        endforeach;

        return $rtn;
    }
    protected function find_exp_label($truck)
    {
        $t = Truck::find($truck);
        return $t->make . ' - ' . $t->number;
    }

}
