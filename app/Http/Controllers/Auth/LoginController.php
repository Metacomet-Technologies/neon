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
    public function __invoke(Request $request): \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
    {
        // Check if the request is an Inertia request
        if ($request->header('X-Inertia')) {
            // Return a response that instructs the client to perform a full page reload
            return Inertia::location(route('login'));
        }

        // Handle the request normally
        // @phpstan-ignore method.notFound
        return Socialite::driver('discord')
            ->scopes(['email', 'guilds', 'guilds.members.read'])
            ->redirect();
    }
}
