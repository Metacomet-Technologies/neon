<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;

final class ProcessDeleteRoleJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireRolePermission();

        $params = DiscordParserService::extractParameters($this->messageContent, 'delete-role');
        $this->validateRequiredParameters($params, 1, 'Role name is required.');

        $roleName = $params[0];
        $role = $this->discord->findRoleByName($this->guildId, $roleName);
        $this->validateTarget($role, 'Role', $roleName);

        $success = $this->discord->deleteRole($this->guildId, $role['id']);

        if (! $success) {
            $this->sendApiError('delete role');
            throw new Exception('Failed to delete role.', 500);
        }

        $this->sendRoleActionConfirmation('deleted', $roleName);
        // 2️⃣ Extract role name
        $roleName = $parts[1];

        // 3️⃣ Fetch all roles with retry logic
        $rolesUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles";

        $rolesResponse = retry(3, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, 200);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to retrieve roles from the server.',
                statusCode: 500,
            );

            return;
        }

        // 4️⃣ Find the role by name (with enhanced matching for special characters)
        $roles = $rolesResponse->json();

        Log::info("Looking for role to delete", [
            'target_role' => $roleName,
            'available_roles' => array_map(fn($r) => $r['name'], $roles),
            'guild_id' => $this->guildId
        ]);

        // Try exact match first
        $role = collect($roles)->first(fn ($r) => strcasecmp($r['name'], $roleName) === 0);

        // If no exact match, try trimming whitespace and quotes
        if (!$role) {
            $cleanRoleName = trim($roleName, ' "\'"');
            $role = collect($roles)->first(fn ($r) => strcasecmp(trim($r['name'], ' "\'"'), $cleanRoleName) === 0);

            if ($role) {
                Log::info("Found role with special character handling", [
                    'original_search' => $roleName,
                    'cleaned_search' => $cleanRoleName,
                    'found_role' => $role['name']
                ]);
            }
        }

        if (! $role) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Role '{$roleName}' not found.",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: "Role '{$roleName}' not found.",
                statusCode: 404,
            );

            return;
        }

        $roleId = $role['id']; // Extract role ID

        // 5️⃣ Delete the role with retry logic
        $deleteUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles/{$roleId}";

        $deleteResponse = retry(3, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
        }, 200);

        if ($deleteResponse->failed()) {
            $errorDetails = $deleteResponse->json();
            $statusCode = $deleteResponse->status();

            Log::error("Failed to delete role '{$roleName}' in guild {$this->guildId}", [
                'role_name' => $roleName,
                'role_id' => $roleId,
                'status_code' => $statusCode,
                'response' => $errorDetails,
                'guild_id' => $this->guildId,
                'user_id' => $this->discordUserId
            ]);

            // Check for specific Discord API errors
            $errorMessage = "❌ Failed to delete role '{$roleName}'.";
            if (isset($errorDetails['message'])) {
                $discordError = $errorDetails['message'];
                if (str_contains($discordError, 'Missing Permissions')) {
                    $errorMessage = "❌ Bot lacks permission to delete role '{$roleName}'. Role may be higher than bot's highest role.";
                } elseif (str_contains($discordError, 'Unknown Role')) {
                    $errorMessage = "❌ Role '{$roleName}' not found or already deleted.";
                } elseif (str_contains($discordError, 'role hierarchy')) {
                    $errorMessage = "❌ Cannot delete role '{$roleName}' - it's higher in hierarchy than bot's role.";
                } else {
                    $errorMessage = "❌ Failed to delete role '{$roleName}': {$discordError}";
                }
            }

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $errorMessage,
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: "Failed to delete role '{$roleName}': " . ($errorDetails['message'] ?? 'Unknown error'),
                statusCode: $statusCode,
            );

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Role Deleted!',
            'embed_description' => "**Role Name:** {$roleName}\n**Successfully removed from server.**",
            'embed_color' => 15158332, // Red embed
        ]);
        $this->updateNativeCommandRequestComplete();
    }
}
