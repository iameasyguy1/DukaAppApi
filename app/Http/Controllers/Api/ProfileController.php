<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BasicMail;
use App\Mail\BasicNotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Illuminate\Routing\UrlGenerator;
class ProfileController extends Controller
{
    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }


    public function update(Request $request)
    {

        $rules=[
            'first_name' => 'sometimes|required|string|min:2',
            'last_name' => 'sometimes|required|string|min:2',
            'email' => 'sometimes|required|string|email|unique:users,email,' . Auth::id(),
            'phone' => 'sometimes|required|string|min:9|unique:users,phone,' . Auth::id(),
            'image'=>'sometimes|string',
            'password' => ['sometimes','confirmed','required', Rules\Password::defaults()],
        ];

        if ($request->phone) {
            $request['phone'] ='254' . substr($request->phone, -9);
        }

// Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);
// If validation fails, return a JSON error response
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }
        if ($request->phone) {
            if (!get_number_type($request->phone)) {
                return response()->json([
                    'success' => false,
                    'errors' => ["phone"=>["Invalid Phone number"]]
                ], 422);
            }
        }

        $user = Auth::user();
        $user->name = $request->first_name. " ".$request->last_name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        if ($request->phone) {
            $user->phone = '254' . substr($request->phone, -9);
        }
        if ($request->has('image')) {

//            //store file into document folder
//            $file = $request->image->store('public/profiles/images');
//            $url = $this->url->to(Storage::url($file));
            $user->image = $request->image;
        }
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user]);
    }

    public function profile(Request $request)
    {
        try {
            $user=$request->user();
            $user['balance']=seller_balance($request->user()->email);
            return $user;
        } catch (\Throwable $th) {
            return response()->json([
            'status' => false,
            'message' => $th->getMessage()
            ], 500);
        }

    }

    public function withdraw(Request $request){
        try {
        $user=$request->user();
        $email = $user->email;
        $phone = $user->phone;
        $amount = seller_balance($user->email);
            if (is_numeric($amount)) {
                $formattedAmount = number_format((float)$amount, 1, '.', '');
            } else {
                $formattedAmount = 0;
            }
       $withdraw= seller_withdraw_request($formattedAmount,$email,$phone);
            if(json_decode($withdraw)->Code=="200"){
                Log::error("hhhhhh".$withdraw);
                try{
                    $sub = __('You have a withdrawal request from Dukaapp.com');
                    $shop_owner_message="Your withdrawal request for your KES ".$formattedAmount." balance on dukaapp.com has been received, please wait for approval.";
                    $super_admin_message ="A new withdrawal request has been sent by Dukaapp.com\nTotal Amount:".$formattedAmount."\nPlease Verify and approve";
                    send_sms_notification($phone,$shop_owner_message);
                    send_sms_notification(env('ADMIN_NUMBER'),$super_admin_message);
//                    \Mail::to(['mosesk@paysokosystems.com','collinss@paysokosystems.com','Josepho@paysokosystems.com','Petem@paysokosystems.com'])->send(new BasicNotify($super_admin_message,$sub));
                    return $withdraw;
                }catch(\Exception $e){
                    Log::error($e->getMessage());
                    return response()->json([
                        'status' => false,
                        'error' => $e->getMessage(),
                        'message'=>'An error occured while processing your request'
                    ], 500);
                }
            }

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function sms_sender(Request $request){

        $rules=[
            'message' => 'required|string|min:2',
            'phone' => 'required|string|min:9'

        ];


// Run the validation on the request data
        $validator = Validator::make($request->all(), $rules);
// If validation fails, return a JSON error response
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }

        $phone = $request->phone;
        $receiver = "254". substr($phone, -9);
        $message = $request->message;
       $response =send_sms_notification($receiver, $message);
       return $response;

    }
}
