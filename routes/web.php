<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return inertia('Home');
});

Route::get('login', function () {
    return Socialite::driver('discord')->redirect();
});

Route::get('discord/callback', function () {
    $user = Socialite::driver('discord')->user();

    $now = Carbon::now()->toImmutable();

    $auth_user = User::updateOrCreate([
        'email' => $user->email,
    ], [
        'name' => $user->name,
        'password' => bcrypt($user->token),
        'avatar' => $user->avatar,
        'created_at' => $now,
        'updated_at' => $now,
        'email_verified_at' => $now,
        'discord_id' => $user->id,
        'access_token' => $user->token,
        'refresh_token' => $user->refreshToken,
        'refresh_token_expires_at' => $now->addSeconds($user->expiresIn),
    ]);

    try {
        Auth::login($auth_user, true);
    } catch (\Exception $e) {
        Log::error($e->getMessage());

        return redirect('/');
    }

    return redirect()->intended('/');
});
