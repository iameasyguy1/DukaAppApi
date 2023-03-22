<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create-admin {id}', function ($id) {
    $user = User::findOrFail($id);
    $user->tokens()->delete();
    $user->createToken('auth_token',['admin'])->plainTextToken;
    $this->info("Admin created successfully.");
})->purpose('make a user super admin');
