<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\BasicMail;
use App\Mail\BasicNotify;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
class ForgotPasswordController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $rules=[
            'email' => ['required', 'email'],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }
        $user = DB::table('users')->where('email', '=', $request->email)
            ->first();
//Check if the user exists
        if (!$user) {
            return response()->json(['status' => __(404),'message'=>'User does not exist']);
        }
        try {
            $token =rand(1231,7879);
            //Create Password Reset Token
            $reset = DB::table('password_resets')->where('email', '=', $request->email)
                ->first();
            if(!$reset){
                DB::table('password_resets')->insert([
                    'email' => $request->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
            }else{
                DB::table('password_resets')->where('email', '=', $request->email)->update([
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
            }

            Mail::to($request->only('email'))->send(new BasicMail($token,__('Password Reset Code'),$request->email));
            return response()->json(['status' => __(200),'message'=>'OTP Sent to the email address']);
        } catch (\Exception $e) {
            //hanle error
            return response()->json(['status' => __(500),'message'=>$e->getMessage()]);
        }
        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.

    }

    public function verify_code(Request $request): JsonResponse
    {
        $rules =[
            'email' => ['required', 'email','exists:users,email'],
            'token'=>['required'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422); // 422 is the HTTP status code for unprocessable entity
        }

        $reset = DB::table('password_resets')->where('email', '=', $request->email)->where('token', '=', $request->token)
            ->first();
        if(!$reset){
            return response()->json([
                'success' => false,
                'errors' => 'Invalid OTP code'
            ], 404);
        }

        $user = User::where('email', $reset->email)->first();
        if(!$user){
            return response()->json([
                'success' => false,
                'errors' => 'User not found'
            ], 404);
        }
        //Hash and update the new password
        $user->password = Hash::make($request->password);
        $user->save();
        //generate toke immediately they change password successfully
        if($user->role===1){
            $new_token = $user->createToken('auth_token',['user'])->plainTextToken;
        }else{
            $new_token = $user->createToken('auth_token',['admin'])->plainTextToken;
        }


        //Delete the reset token
        DB::table('password_resets')->where('email', $user->email)
            ->delete();
        //Send Email Reset Success Email
        try {
            $msg = "Your Password was changed successfully.";
            Mail::to($request->only('email'))->send(new BasicNotify($msg,__('Successful Password Reset'),$request->email));
            return response()->json([
                'success' => true,
                'message' => 'Password Reset was successfully',
                'token' => $new_token,
                'token_type' => 'Bearer'
            ], 201);
        }catch(\Exception $e){
            return response()->json(['status' => __(500),'message'=>$e->getMessage()]);
        }
    }
}
