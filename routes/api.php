<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\MpesaController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::group([
    'prefix' => 'v1',
    'as' => 'api.'
], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('mpesa/stk', [MpesaController::class, 'PayWithMpesa']);
    Route::get('order/paid/{order_id}', [MpesaController::class,'MpesaSuccess']);
    Route::get('mpesa/status/{merchant_id}', [MpesaController::class,'MpesaStatus']);
    Route::get('pages/show/{slug}', [PageController::class, 'show'])->name('pages.show');
    Route::get('pages/search/{query}', [PageController::class, 'search']);
    Route::post('mpesa/stk', [MpesaController::class, 'PayWithMpesa']);
    Route::post('reset/password', [ForgotPasswordController::class, 'store'])->name('pass.reset');
    Route::post('sms/sender', [ProfileController::class, 'sms_sender']);
    Route::middleware('auth:sanctum')->get('/user', [ProfileController::class, 'profile']);
    Route::middleware('auth:sanctum')->get('/withdraw', [ProfileController::class, 'withdraw']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    Route::middleware('auth:sanctum')->post('/update-profile', [ProfileController::class, 'update']);

    Route::resource('pages', PageController::class)->middleware('auth:sanctum')->except(['show']);
    Route::resource('orders', OrderController::class)->middleware('auth:sanctum')->except(['store']);

});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Unauthenticated.',
    ], 401);
});




