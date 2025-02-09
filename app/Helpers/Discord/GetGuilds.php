<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Enums\DiscordPermissionEnum;
use App\Helpers\DiscordRefreshToken;
use App\Models\User;
use DateInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GetGuilds
{
    /**
     * The amount of time to cache the guilds.
     */
    public DateInterval $dateInterval;

    /**
     * The base url for the discord api.
     */
    public string $baseUrl;

    /**
     * The cache key for the user's guilds.
     */
    public string $cacheKey;

    /**
     * The user's access token.
     */
    private ?string $token;

    /**
     * Create a instange of GetGuilds class.
     *
     * @return void
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __construct(public User $user)
    {
        $this->dateInterval = DateInterval::createFromDateString('90 seconds');
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->cacheKey = 'guilds_' . $this->user->discord_id;
        $this->token = $this->user->access_token;
    }

    /**
     * Get all guilds for the user.
     *
     * @return array<string, mixed>
     */
    public function getAllGuilds(): array
    {
        if (! $this->token) {
            Log::warning('User token is missing. Attempting to get a new token.', [
                'user_id' => $this->user->id,
            ]);
            $this->token = (new DiscordRefreshToken($this->user))->refreshToken();
        }

        if (! $this->token) {
            Log::error('Failed to get user token', [
                'user_id' => $this->user->id,
            ]);

            return [];
        }

        $guilds = Cache::remember($this->cacheKey, $this->dateInterval, function () {
            $url = $this->baseUrl . '/users/@me/guilds';
            $response = Http::withToken($this->token ?? '')->get($url);

            if ($response->status() === 401 || $response->status() === 403) {
                $newToken = (new DiscordRefreshToken($this->user))->refreshToken();
                if (! $newToken) {
                    Log::error('Failed to get user token', [
                        'user_id' => $this->user->id,
                    ]);

                    return [];
                }
                $response = Http::withToken($newToken)->get($url);
            }

            return $response->successful() ? $response->json() : [];
        });

        return $guilds;
    }

    /**
     * Get all guilds where the user has the given permission.
     *
     * @return list<array<string, mixed>>
     */
    public function getGuildsWhereUserHasPermission(DiscordPermissionEnum $permission = DiscordPermissionEnum::ADMINISTRATOR): array
    {
        $guilds = $this->getAllGuilds();

        $guilds = array_filter($guilds, function ($guild) use ($permission) {
            return (bool) ($guild['permissions'] & $permission->value);
        });

        $guilds = array_values($guilds);

        return $guilds;
    }
}
