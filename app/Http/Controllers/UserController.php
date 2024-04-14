<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use App\Models\User;
use App\Mail\ConfirmMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;
use Twilio\Rest\Client;
use Validator;
use Stripe;
use Illuminate\Support\Facades\Hash;
use App\Mail\ResetCodeMail;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
class UserController extends Controller
{



    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:8',
        ]);
       if ($validator->fails()) {
            return response()->json(["message" =>$validator->errors()->all(), 422]);
        }
        Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        $customer = Stripe\Customer::create([
            'email' => $request->email,
        ]);

        $input=$request->all();
        $input['stripe_customer_id'] = $customer->id;
        $input['password'] = bcrypt($request->password);
        $code =  rand(100000, 999999);
        $input['reset_code'] = $code;
         $user = User::create($input);
        $date = \Illuminate\Support\Carbon::now();
        $end_data = $date->addDays(430);
        Subscription::create
        ([
            'user_id' =>$user->id,
            'plan_id' => 1,
            'payment_method' => 'free',
            'payment_status' => 1,
            'start_date' => \Illuminate\Support\Carbon::now(),
            'end_date' => $end_data,
        ]);
        $token = Auth::login($user);
        $user->update(['subscribed'=>true]);
        Mail::to($request->email)->send(new ResetCodeMail($code));

        return response()->json([
            'message' => "Data registered successfully,with a code sent to this email for verification",
            'status' =>true,
            'token' => $token,
        ]);
    }

    public function confirmEmail($id)
    {
        User::where('id',$id)->update(['status'=>1]);
        return view('main.success-mail');
    }

      public function getLoginUserDataByID($id)
    {
        $data['user'] = User::with('subscription')->find($id);
        $data['chat_2_days_ago'] = Chat::where('user_id',auth()->id())->WhereDate('created_at','<=', Carbon::now())->whereDate('created_at','>=', Carbon::now()->subDays(2))->latest()->limit(5)->get();
        $data['chat_4_days_ago'] = Chat::where('user_id',auth()->id())->WhereDate('created_at','<', Carbon::now()->subDays(2))->whereDate('created_at','>=', Carbon::now()->subDays(4))->latest()->limit(5)->get();
        $data['chat_7_days_ago'] = Chat::where('user_id',auth()->id())->whereDate('created_at','<', Carbon::now()->subDays(4))->WhereDate('created_at','>=', Carbon::now()->subDays(7))->latest()->limit(5)->get();

        $response =[
            'data'=>$data,
            'status' =>true,
            'message' => "data get successfully"
        ];
        return response()->json($response);
    }

    public function userProfile() {
        $data['user'] = User::with('subscription')->find(Auth::id());
        if($data['user']->subscription->plan_id!==1) {
            $data['used_token'] = $data['user']->searches()->whereBetween('created_at', [$data['user']->subscription->start_date, $data['user']->subscription->end_date])->sum('text_tokens_used') + $data['user']->searches()->whereBetween('created_at', [$data['user']->subscription->start_date, $data['user']->subscription->end_date])->sum('audio_tokens_used');
            $data['remaining_token'] = $data['user']->subscription->plan->monthly_text_tokens - $data['used_token'];
        }
        $data['chats'] = Chat::where('user_id',auth()->id())->latest()->orderBy('created_at','DESC')->get();
        $response =[
            'data'=>$data,
            'status' =>true,
            'message' => "data get successfully"
        ];
        return response()->json($response);
    }



    public function sendWelcomeEmail()
    {
        Mail::to('sufian.ahmed@ssasoft.com')->send(new WelcomeEmail());

        return "Welcome email sent successfully!";
    }



    public function sendResetCode(Request $request)
    {

        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset password link sent to your email'], 200)
            : response()->json(['message' => 'Unable to send reset password link'], 400);

    }
    public function updatePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_code' => null,
            'reset_code_expires_at' => null,
        ]);

        return response()->json(['status' =>true,'message' => 'Password updated successfully']);
    }
    public function verifyCode(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'code'=>"required"
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json( array("status" => 400, "message" => $validator->errors()->first()));
        } else {

            $user = User::where('id', auth()->id())
                ->where('reset_code', $request->code)
                ->first();

            if (!$user) {
                return response()->json(['status' => 'false', 'message' => 'Invalid or expired reset code'], 422);
            } else {
               $user->update(['status'=>1]);
                return response()->json(['status' => 'true', 'message' => 'Code verified successfully', 'user' => $user->email], 200);
            }

        }
    }
}
