<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Enums\DiscordPermissionEnum;
use Illuminate\Support\Facades\Http;

final class GetGuildsByDiscordUserId
{
    /**
     * Get all guilds for the user.
     */
    public static function getGuildRoles(string $guildId, string $userId): array
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/guilds/' . $guildId . '/members/' . $userId;
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();

        if (! isset($data['roles'])) {
            return [];
        }

        return $data['roles'];
    }

    public static function getRoleFromGuild(string $guildId, string $roleId): ?string
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/guilds/' . $guildId . '/roles/' . $roleId;
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (! isset($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * Get all guilds where the user has the given permission.
     */
    public static function getIfUserCanManageChannels(string $guildId, string $userId, DiscordPermissionEnum $permission = DiscordPermissionEnum::MANAGE_CHANNELS): string
    {
        $roles = self::getGuildRoles($guildId, $userId);

        if (empty($roles)) {
            return 'failed';
        }

        foreach ($roles as $role) {
            $rolePermission = self::getRoleFromGuild($guildId, $role);
            if (! $rolePermission) {
                continue;
            }

            if ($rolePermission & $permission->value) {
                return 'success';
            }
        }

        return 'failed';
    }
}
