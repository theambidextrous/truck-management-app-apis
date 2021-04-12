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
/** mail */
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Mail\Code;

class AdminController extends Controller
{
    public function signup(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'fname' => 'required|string',
                'lname' => 'required|string',
                'email' => 'required|email',
                'password' => 'required|string',
                'c_password' => 'required|same:password',
            ]);
            if( $validator->fails() ){
                return response([
                    'status' => 201,
                    'message' => 'A required field was not filled. All fields with asteric (*) are required',
                    'errors' => $validator->errors()->all(),
                ], 403);
            }
            if( strlen($request->get('password')) < 8){
                return response([
                    'status' => 201,
                    'message' => 'Password error. Passwords must match and minimum length of 8 characters',
                    'errors' => [],
                ], 403);
            }
            $input = $request->all();
            if( User::where('email', $input['email'])->count() )
            {
                return response([
                    'status' => 201,
                    'message' => "Email address already used",
                    'errors' => [],
                ], 403);
            }
            $input['account'] = $this->createCode(16, 1);
            $input['password'] = Hash::make($input['password']);
            $user = User::create($input)->id;
            $user = User::find($user);
            /**auth */
            $login = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string'
            ]);
            if( !Auth::attempt( $login ) )
            {
                return response([
                    'status' => 201,
                    'message' => "Authentication after login failed. Please go to login page",
                    'errors' => [],
                ], 403);
            }
            /** end auth */
            $access_token = Auth::user()->createToken('authToken')->accessToken;
            $user = Auth::user();
            $account = Auth::user()->account;
            if(strlen($access_token) > 10)
            {
                /** user created we need to create company with defaults*/
                $now = date("Y-m-d");
                $trial_end = date("Y-m-d", strtotime("+" .Config::get('app.try'). " day"));
                $co_payload = [
                    'account' => $input['account'],
                    'active_from' => $now,
                    'active_to' => $trial_end,
                    'custodian_email' => $input['email'],
                ];
                $inst = Setup::create($co_payload)->id;
            }
            $user['token'] = $access_token;
            $user['has_setup'] = 0;
            $user['has_expired'] = 0;
            $user['is_near'] = $this->account_is_near_expiry();
            Mail::to($input['email'])->send(new WelcomeEmail($input));
            return response([
                'status' => 200,
                'message' => 'Success. Account created',
                'data' => $user,
            ], 200);
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
    public function update_info(Request $request)
    {
        $account = Auth::user()->account;
        try{
            $validator = Validator::make($request->all(), [
                'fname' => 'required|string',
                'lname' => 'required|string',
                'address' => 'required|string',
                'city' => 'required|string',
                'state' => 'required|string|not_in:nn',
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
            $input = $request->all();
            $instance = User::find(Auth::user()->id);
            $instance->fname = $input['fname'];
            $instance->lname = $input['lname'];
            $instance->address = $input['address'];
            $instance->city = $input['city'];
            $instance->state = $input['state'];
            $instance->zip = $input['zip'];
            $instance->email = $input['email'];
            $instance->phone = $input['phone'];
            $instance->save();
            $user = User::find(Auth::user()->id);
            $access_token = $user->createToken('authToken')->accessToken;
            $user['token'] = $access_token;
            $user['has_setup'] = Setup::where('account', $account)
                ->where('email', '!=', null)->count();
            $user['has_expired'] = $this->account_expired();
            $user['is_near'] = $this->account_is_near_expiry();
            return response([
                'status' => 200,
                'message' => 'Success. Account information updated',
                'data' => $user,
            ], 200);
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
    public function update_pwd(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
                'c_password' => 'required|same:password',
            ]);
            if( $validator->fails() ){
                return response([
                    'status' => 201,
                    'message' => 'Passwords do not match',
                    'errors' => $validator->errors()->all(),
                ], 403);
            }
            $input = $request->all();
            $input['password'] = Hash::make($input['password']);
            $user = User::find(Auth::user()->id);
            $user->password = $input['password'];
            $user->save();
            return response([
                'status' => 200,
                'message' => 'Success. Password changed',
                'data' => $user,
            ], 200);
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
    public function stream($file)
    {
        $filename = ('app/cls'.$file);
        return response()->download(storage_path($filename), null, [], null);
    }
    public function signin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => "Invalid Email or password",
                'errors' => $validator->errors()->all(),
            ], 403);
        }
        $login = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        if( !Auth::attempt( $login ) )
        {
            return response([
                'status' => 201,
                'message' => "Invalid username or password. Try again",
                'errors' => [],
            ], 403);
        }
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
            'message' => 'Success. logged in',
            'data' => $user,
        ], 200);
    }
    public function reqreset($email)
    {
        try{
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response([
                    'status' => 201,
                    'message' => "Invalid email address.",
                    'errors' => [],
                ], 403); 
            }
            $user = User::where('email', $email)->count();
            if(!$user){
                return response([
                    'status' => 201,
                    'message' => "There is no user with that email. Try again or create account",
                    'errors' => [],
                ], 403); 
            }
            Pcode::where('email', $email)->update(['used' => true]);
            $code = $this->createCode(6,1);
            $data = ['email' => $email, 'code' => $code ];
            if( Pcode::create($data)->id )
            {
                $msg = "Hi, use Verification code " . $code . " to validate your account.";
                $data['msg'] = $msg;
                Mail::to($data['email'])->send(new Code($data));
                return response([
                    'status' => 200,
                    'message' => "A verification code has been sent to your email address.",
                    'errors' => [],
                ], 200); 
            }
            return response([
                'status' => 201,
                'message' => "Error sending email",
                'errors' => [],
            ], 403); 
            
        }catch( Exception $e){
            return response([
                'status' => 201,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 403); 
        }
    }
    public function verifyreset($code, $email)
    {
        try{
            if( $this->isExpired($code) )
            {
                return response([
                    'status' => 201,
                    'message' => "Expired verification code",
                    'errors' => [],
                ], 403); 
            }
            $data = ['email' => $email, 'code' => $code ];
            $isValid = Pcode::where('email', $email)
                ->where('code', $code)
                ->where('used', false)
                ->orderBy('created_at', 'desc')
                ->first();
            if( !is_null($isValid) )
            {
                $isValid->used = true;
                User::where('email', $email)->update([ 
                    'email_verified_at' => date('Y-m-d H:i:s') 
                ]);
                $isValid->save();
                return response([
                    'status' => 200,
                    'message' => "Code verified!",
                    'data' => $data,
                ], 200); 
            }
            return response([
                'status' => 201,
                'message' => "Enter a valid verification code",
            ], 403); 
        }catch( Exception $e){
            return response([
                'status' => 201,
                'message' => "Invalid Access. No data",
            ], 403); 
        }
    }
    public function finishreset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'c_password' => 'required|same:password'
        ]);
        if( $validator->fails() ){
            return response([
                'status' => 201,
                'message' => 'Passwords do no match',
                'errors' => $validator->errors()
            ], 403);
        }
        $email = $request->get('email');
        $user = User::where('email', $email)->first();
        if(!is_null($user)){
            $user->password = Hash::make($request->get('password'));
            $user->save();
            return response([
                'status' => 200,
                'message' => 'Password was reset, Login now',
                'data' => $user->toArray(),
            ], 200);
        }
        return response([
            'status' => 201,
            'message' => 'Data error. We could not updte password',
            'errors' => []
        ], 403);
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
    protected function createCode($length = 20, $t = 0) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if( $t > 0 ){
            $characters = '0123456789';
        }
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    protected function isExpired($code)
    {
        $cnt = Pcode::where('code', $code)
        ->where('created_at', '<=', Carbon::now()
        ->subMinutes(5)->toDateTimeString())
        ->count();
        if( $cnt > 0 )
        {
            return true;
        }
        return false;
    }
}
