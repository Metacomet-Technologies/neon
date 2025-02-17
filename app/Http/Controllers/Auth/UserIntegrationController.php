<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

final class UserIntegrationController
{
    public function store(Request $request, string $provider)
    {
        /** @var \Laravel\Socialite\Two\User $socialiteUser */
        $socialiteUser = Socialite::driver($provider)->user();

        $user = $request->user();

        $user->integrations()->updateOrCreate([
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
        ], [
            'data' => json_decode(json_encode($socialiteUser), true),
        ]);

        return redirect()->route('profile');
    }

    public function destroy(Request $request, string $provider)
    {
        $request->user()->integrations()
            ->where('provider', $provider)
            ->delete();

        return redirect()->route('profile');
    }

    public function create(Request $request, string $provider)
    {
        $scopes = match ($provider) {
            'discord' => ['email', 'guilds', 'guilds.members.read'],
            'twitch' => ['user:read:email'],
            default => [],
        };

        return Socialite::driver($provider)
            ->scopes($scopes)
            ->redirect();
    }
}
