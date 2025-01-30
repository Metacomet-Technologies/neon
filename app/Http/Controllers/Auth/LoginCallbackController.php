<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

final class LoginCallbackController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): \Illuminate\Http\RedirectResponse
    {
        /** @var \Laravel\Socialite\Two\User $socialiteUser */
        $socialiteUser = Socialite::driver('discord')->user();

        $now = Carbon::now()->toImmutable();

        $user = User::updateOrCreate([
            'email' => $socialiteUser->getEmail(),
        ], [
            'name' => $socialiteUser->getName(),
            'password' => bcrypt($socialiteUser->token),
            'avatar' => $socialiteUser->getAvatar(),
            'created_at' => $now,
            'updated_at' => $now,
            'email_verified_at' => $now,
            'discord_id' => $socialiteUser->getId(),
            'access_token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'refresh_token_expires_at' => $now->addSeconds($socialiteUser->expiresIn),
            'is_on_mailing_list' => true,
        ]);

        if ($user->wasRecentlyCreated) {
            Mail::to($user)->queue(new WelcomeEmail);
        }

        try {
            Auth::login($user, true);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return redirect('/');
        }

        return redirect()->intended(route('profile'));
    }
}
