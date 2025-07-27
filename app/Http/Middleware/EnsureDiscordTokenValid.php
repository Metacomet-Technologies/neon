<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Discord\DiscordService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDiscordTokenValid
{
    public function __construct(
        private DiscordService $discordService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Check if Discord access token is expired
        if ($user->hasExpiredDiscordToken()) {
            // Token is expired, try to refresh
            if ($user->canRefreshDiscordToken()) {
                $newToken = $this->discordService->refreshUserToken($user);

                if (! $newToken) {
                    // Refresh failed, redirect to login
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect()->route('login')->with('error', 'Your Discord session has expired. Please log in again.');
                }
            } else {
                // Refresh token is also expired
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your Discord session has expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
