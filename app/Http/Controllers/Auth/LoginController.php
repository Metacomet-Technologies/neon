<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

final class LoginController
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        // Check if the request is an Inertia request
        if ($request->header('X-Inertia')) {
            // Return a response that instructs the client to perform a full page reload
            return Inertia::location(route('login'));
        }

        // Handle the request normally
        return Socialite::driver('discord')->redirect();
    }
}
