<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

final class LoginCallbackController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): \Illuminate\Http\RedirectResponse
    {
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
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect('/');
        }

        return redirect()->intended('/');
    }
}
