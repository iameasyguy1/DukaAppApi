<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\ApiRegistration;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use App\Models\User;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules=[
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'email' => 'required|string|email|unique:users',
            'phone' => 'required|string|min:9|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
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
        if(!get_number_type($request->phone)){
            return response()->json([
                'success' => false,
                'errors' => ["phone"=>["Invalid Phone number"]]
            ], 422);
        }
        $user = new User([
            'name' => $request->first_name. " ".$request->last_name,
            'email' => $request->email,
            'phone' =>'254'.substr($request->phone, -9),
            'password' => Hash::make($request->password),
            'role'=>2
        ]);

        $user->save();
        event(new ApiRegistration($user));

        $token = $user->createToken('auth_token',['user'])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $rules=[
            'email' => 'required|string|email',
            'password' => 'required|string'
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

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            if(Auth::check() && Auth::user()->role === 1) {
                $token = Auth::user()->createToken('auth_token',['admin'])->plainTextToken;
            }else{
                $token = Auth::user()->createToken('auth_token',['user'])->plainTextToken;
            }


            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'token_type' => 'Bearer'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }


}
