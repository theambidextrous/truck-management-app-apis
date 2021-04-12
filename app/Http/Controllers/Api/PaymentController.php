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
use App\Models\Pcode;
use App\Models\Setup;
use App\Models\Payment;
use App\Models\Invoice;
/** mail */
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentSuccess;
use App\Mail\Code;

class PaymentController extends Controller
{
    public function pricing(Request $req)
    {
        try{
            $account = Auth::user()->account;
            $validator = Validator::make($req->all(), [
                'group' => 'required|string',
            ]);
            if( $validator->fails() ){
                return response([
                    'status' => 201,
                    'message' => 'Select a valid period',
                    'errors' => [],
                ], 403);
            }
            $account_data  = Setup::where('account', $account)->first();
            if(is_null($account_data))
            {
                throw new \Exception("No valid account found for the current session");
            }
            $current_exp = $account_data->active_to;
            $stack = $this->extract_price($req->get('group'));
            $price = $stack[0];
            $days = $stack[1];
            $next_expiry = date("Y-m-d", strtotime("+" . $days . " day"));
            $data = [
                'end' => $next_expiry,
                'cost' => number_format(($price/100), 2),
            ];
            return response([
                'status' => 200,
                'message' => 'done',
                'data' => $data,
            ], 200);
        }catch(Exception $e)
        {
            return response([
                'status' => 201,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 403);
        }
    }
    public function pay(Request $req)
    {
        try{
            $account = Auth::user()->account;
            \Stripe\Stripe::setApiKey(Config::get('app.stripe_private_key'));
            $validator = Validator::make($req->all(), [
                'group' => 'required|string',
                'payment_method' => 'required|string',
            ]);
            if( $validator->fails() ){
                return response([
                    'status' => 201,
                    'message' => 'Select a valid period and provide valid card details',
                    'errors' => [],
                ], 403);
            }
            $input = $req->all();
            if( strlen($input['payment_method']) < 5 )
            {
                throw new \Exception("Data error. Invalid payment options. Try using a different card");
            }
            $account_data  = Setup::where('account', $account)->first();
            if(is_null($account_data->company) || is_null($account_data->phone))
            {
                throw new \Exception("Data error. You have not completed Account setup. Go to Dashboard to complete setup.");
            }
            if(is_null($account_data))
            {
                throw new \Exception("No valid account found for the current session");
            }
            if(!intval($input['group']))
            {
                throw new \Exception("Data error. No valid period selected.");
            }
            $input['organization'] = $account_data->company;
            $input['custodian_email'] = $account_data->custodian_email;
            $input['phone'] = $account_data->phone;
            $current_exp = $account_data->active_to;
            $stack = $this->extract_price($input['group']);
            $price = $stack[0];
            $days = $stack[1];
            $next_expiry = date("Y-m-d", strtotime("+" . $days . " day"));
            $stripe_user = $this->stripe_customer([
                'name' => $input['organization'],
                'email' => $input['custodian_email'],
                'phone' => $input['phone'],
                'description' => 'BlueTrux Subscriber'
            ]);
            /** link payment method to account */
            $p_method = \Stripe\PaymentMethod::retrieve($input['payment_method']);
            $p_method->attach(['customer' => $stripe_user ]);
            Setup::find($account_data->id)->update([
                'stripe_pay_method' => $input['payment_method'],
            ]);
            $inv_payload = [
                'account' => $account,
                'address' => $account_data->address,
                'invoice_amount' => intval($price),
                'paid_amount' => 0,
                'is_recurring' => false,
                'is_paid' => false,
                'next_auto_charge' => $next_expiry,
            ];
            $invoice_no = Invoice::create($inv_payload)->id;
            $_paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => intval($price),
                'currency' => 'usd',
                'description' => 'Payment for ' . $days . ' days subscription on ' . Config::get('app.name'),
                'metadata' => ['order_id' => $invoice_no],
                'customer' => $stripe_user,
                'payment_method' => $input['payment_method'],
                'error_on_requires_action' => true,
                'confirm' => true,
                'setup_future_usage' => 'on_session',
            ]);
            $payment_payload = [
                'account' => $account,
                'invoice' => $invoice_no,
                'amount' => intval($price),
                'is_paid' => false,
                'payload' => json_encode(json_decode($_paymentIntent, true)),
            ];
            $payid = Payment::create($payment_payload)->id;
            if( $_paymentIntent->status == 'succeeded')
            {
                Invoice::find($invoice_no)->update([ 'is_paid' => true, 'paid_amount' => intval($price) ]);
                Payment::find($payid)->update([ 'is_paid' => true, 'payload' => json_encode($_paymentIntent) ]);
                Setup::find($account_data->id)->update([
                    'active_to' => $next_expiry,
                ]);
                $maildata = [
                    'amount' => number_format((intval($price)/100), 2),
                ];
                Mail::to(Auth::user()->email)->send(new PaymentSuccess($maildata));
                $accessToken = Auth::user()->createToken('authToken')->accessToken;
                $user = Auth::user();
                $account = Auth::user()->account;
                $user['token'] = $accessToken;
                $user['has_setup'] = Setup::where('account', $account)
                    ->where('email', '!=', null)->count();
                $user['has_expired'] = $this->account_expired();
                $user['is_near'] = $this->account_is_near_expiry();
                return response([
                    'status' => 200,
                    'message' => "Payment successfull. Thank you.",
                    'data' => $user,
                ], 200);
            }
            return response([
                'status' => 201,
                'message' => 'Payment failed. Try again using a different credit card',
                'data' => [],
            ], 403);
        } catch (\Illuminate\Database\QueryException $e) {
            return response([
                'status' => 201,
                'message' => "Server error. Invalid data",
                'errors' => [],
            ], 403);
        } catch (PDOException $e) {
            return response([
                'status' => 201,
                'message' => "Db error. Invalid data",
                'errors' => [],
            ], 403);
        }
    }
    public function info()
    {
        $account = Auth::user()->account;
        $co = Setup::where('account', $account)->first();
        $data = Payment::where('account', $account)->where('is_paid', true)->get();
        return response([
            'status' => 200,
            'message' => "Db error. Invalid data",
            'account' => $co,
            'data' => $this->format_pay_data($data),
        ], 200);
    }
    public function status()
    {
        return response([
            'status' => 200,
            'message' => "Done",
            'expired' => $this->account_expired(),
        ], 200);
    }
    protected function format_pay_data($data)
    {
        if(is_null($data))
        {
            return [];
        }
        $data = $data->toArray();
        $rtn = [];
        foreach($data as $_data )
        {
            $_data['created_at'] = date("m/d/Y H:i:s", strtotime($_data['created_at']));
            $_data['updated_at'] = date("m/d/Y H:i:s", strtotime($_data['updated_at']));
            $_data['amount'] = number_format(($_data['amount']/100), 2);
            array_push($rtn, $_data);
        }
        return $rtn;
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
    protected function stripe_customer($in)
    {
        $account = Auth::user()->account;
        $account_data  = Setup::where('account', $account)->first();
        if(!is_null($account_data->stripe_user))
        {
            return $account_data->stripe_user;
        }
        $stripe = new \Stripe\StripeClient(Config::get('app.stripe_private_key'));
        $stripe_res = $stripe->customers->create([
            'name' => $in['name'],
            'email' => $in['email'],
            'phone' => $in['phone'],
            'description' => $in['description'],
        ]);
        if( is_null($stripe_res->id) )
        {
            throw new \Exception("We could not verify your information on stripe.");
        }
        $account_data->stripe_user = $stripe_res->id;
        $account_data->save();
        return $stripe_res->id;
    }
    protected function extract_price($group)
    {
        $array = explode(",", Config::get('app.price'));
        foreach( $array as $price )
        {
            $entry = explode("~", $price);
            if(intval($group) == intval($entry[1]))
            {
                return [intval($entry[0]), intval($entry[1])];//cents
            }
        }
        throw new \Exception("Invalid Plan price. Could not find valid price for selected package");
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
