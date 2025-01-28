<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DiscordRefreshToken
{
    public function __construct(private User $user) {}

    public function refreshToken(): ?string
    {
        Log::info('Refreshing Discord token for user', [
            'user_id' => $this->user->id,
        ]);

        $response = Http::post('https://discord.com/api/oauth2/token', [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.discord.client_id'),
            'client_secret' => config('services.discord.client_secret'),
            'refresh_token' => $this->user->refresh_token,
        ]);

        if ($response->failed()) {
            Log::error('Failed to refresh Discord token', [
                'user_id' => $this->user->id,
                'response' => $response->body(),
            ]);

            return null;
        }

        $data = $response->json();
        $this->user->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 0),
            'updated_at' => now(),
        ]);

        Log::info('Discord token refreshed successfully', [
            'user_id' => $this->user->id,
        ]);

        return $data['access_token'];
    }
}
