<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Http;

final class ProcessNewRoleJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage;
    public string $exampleMessage;
    public array $defaultRoleSettings;

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = FacadesDB::table('native_commands')->where('slug', 'new-role')->first();

        // dump('Command Data:', $command);

        if (! $command) {
            throw new Exception('Command configuration missing from database.');
        }

        // Set correct usage & example messages
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        // Set default role settings
        $this->defaultRoleSettings = [
            'color' => hexdec('FFFFFF'), // Default white color
            'hoist' => false,
        ];

        $this->baseUrl = config('services.discord.rest_api_url');
    }

    public function handle(): void
    {
        // dump("Processing !new-role command from {$this->discordUserId} in guild {$this->guildId}");

        // Ensure the user has permission to manage roles
        if (! GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId)) {
            // dump('❌ User does not have permission to manage roles.');
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage roles in this server.',
            ]);

            return;
        }

        // 1️⃣ Parse command arguments
        $parts = explode(' ', trim($this->messageContent));

        // dump('Parsed Command Parts:', $parts);

        // If not enough parameters, send usage message
        if (count($parts) < 2) {
            // dump('❌ Not enough parameters. Sending help message.');
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // 2️⃣ Extract role details
        $roleName = $parts[1];
        $roleColor = (int) $this->defaultRoleSettings['color']; // Convert to integer
        $roleHoist = $this->defaultRoleSettings['hoist'];

        // 3️⃣ Handle optional color argument
        if (isset($parts[2]) && preg_match('/^#?([0-9a-fA-F]{6})$/', $parts[2], $matches)) {
            $roleColor = hexdec($matches[1]);
        }

        // 4️⃣ Handle optional hoist argument
        if (isset($parts[3]) && strtolower($parts[3]) === 'yes') {
            $roleHoist = true;
        }

        // dump('Final Role Color (Decimal)', $roleColor);

        // 5️⃣ Fetch existing roles
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')
            ->get("{$this->baseUrl}/guilds/{$this->guildId}/roles");

        if ($rolesResponse->failed()) {
            // dump("❌ Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);

            return;
        }

        $existingRoles = $rolesResponse->json();

        // 6️⃣ Check if the role exists
        foreach ($existingRoles as $role) {
            if (strcasecmp($role['name'], $roleName) === 0) { // Case-insensitive comparison
                // dump("❌ Role '{$roleName}' already exists.");
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => "❌ Role '{$roleName}' already exists.",
                ]);

                return;
            }
        }

        // 7️⃣ Create the role via Discord API
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $apiResponse = Http::withToken(config('discord.token'), 'Bot')
            ->post($url, [
                'name' => $roleName,
                'color' => $roleColor,
                'hoist' => $roleHoist,
                'mentionable' => false,
            ]);

        // 8️⃣ Handle API Response
        if ($apiResponse->failed()) {
            // dump("❌ Failed to create role '{$roleName}' in guild {$this->guildId}", $apiResponse->json());
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to create role '{$roleName}'.",
            ]);

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
    }
}
