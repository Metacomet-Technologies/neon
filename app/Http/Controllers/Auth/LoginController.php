<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;

final class LoginController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): \Illuminate\Http\RedirectResponse
    {
        return Socialite::driver('discord')->redirect();
    }
}
