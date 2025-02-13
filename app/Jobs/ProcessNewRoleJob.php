<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessNewRoleJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public array $defaultRoleSettings;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);

        // Set default role settings
        $this->defaultRoleSettings = [
            'color' => hexdec('FFFFFF'), // Default white color
            'hoist' => false,
        ];
    }

    public function handle(): void
    {
        // Ensure the user has permission to manage roles
        if (! GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage roles in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage roles.',
                statusCode: 403,
            );

            return;
        }

        // Parse command arguments
        $parts = explode(' ', trim($this->messageContent));

        // If not enough parameters, send usage message
        if (count($parts) < 2) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Extract role details
        $roleName = $parts[1];
        $roleColor = (int) $this->defaultRoleSettings['color']; // Convert to integer
        $roleHoist = $this->defaultRoleSettings['hoist'];

        // Handle optional color argument
        if (isset($parts[2]) && preg_match('/^#?([0-9a-fA-F]{6})$/', $parts[2], $matches)) {
            $roleColor = hexdec($matches[1]);
        }

        // Handle optional hoist argument
        if (isset($parts[3]) && strtolower($parts[3]) === 'yes') {
            $roleHoist = true;
        }

        // Fetch existing roles
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')
            ->get("{$this->baseUrl}/guilds/{$this->guildId}/roles");

        if ($rolesResponse->failed()) {
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

        $existingRoles = $rolesResponse->json();

        // Check if the role exists
        foreach ($existingRoles as $role) {
            if (strcasecmp($role['name'], $roleName) === 0) { // Case-insensitive comparison
                // dump("❌ Role '{$roleName}' already exists.");
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Role '{$roleName}' already exists.",
                ]);
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Role already exists.',
                    statusCode: 409,
                );

                return;
            }
        }

        // Create the role via Discord API
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'name' => $roleName,
                'color' => $roleColor,
                'hoist' => $roleHoist,
                'mentionable' => false,
            ]);

        // Handle API Response
        if ($apiResponse->failed()) {
            // dump("❌ Failed to create role '{$roleName}' in guild {$this->guildId}", $apiResponse->json());
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to create role '{$roleName}'.",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to create role.',
                statusCode: 500,
            );

            return;
        }

        // ✅ Success! Send confirmation message
        $createdRole = $apiResponse->json();
        // dump('✅ Role Created Successfully:', $createdRole);

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Role Created!',
            'embed_description' => "**Role Name:** {$createdRole['name']}\n**Color:** #" . strtoupper(str_pad(dechex($createdRole['color']), 6, '0', STR_PAD_LEFT)) . "\n**Displayed Separately:** " . ($createdRole['hoist'] ? '✅ Yes' : '❌ No'),
            'embed_color' => $createdRole['color'],
        ]);
        $this->updateNativeCommandRequestComplete();
    }
}
