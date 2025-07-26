<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\DiscordApiService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDiscordTokenValid
{
    public function __construct(
        private DiscordApiService $discordApiService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if Discord access token is expired
        if ($user->hasExpiredDiscordToken()) {
            // Token is expired, try to refresh
            if ($user->canRefreshDiscordToken()) {
                $newToken = $this->discordApiService->refreshUserToken($user);

                if (! $newToken) {
                    // Refresh failed, redirect to login
                    Auth::logout();

                    return redirect()->route('login')->with('error', 'Your Discord session has expired. Please log in again.');
                }
            } else {
                // Refresh token is also expired
                Auth::logout();

                return redirect()->route('login')->with('error', 'Your Discord session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
