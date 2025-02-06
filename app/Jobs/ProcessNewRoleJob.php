<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessNewRoleJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage;
    public string $exampleMessage;
    public array $defaultRoleSettings;

    // 'slug' => 'new-role',
    // 'description' => 'Creates a new role with optional color and hoist settings.',
    // 'class' => \App\Jobs\ProcessNewRoleJob::class,
    // 'usage' => 'Usage: !new-role <role-name> [color] [hoist]',
    // 'example' => 'Example: !new-role VIP #3498db yes',
    // 'is_active' => true,

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = FacadesDB::table('native_commands')->where('slug', 'new-role')->first();

        // Set default role settings
        $this->defaultRoleSettings = [
            'color' => hexdec('FFFFFF'), // ✅ Ensure default is properly converted to decimal
            'hoist' => false,
        ];

        // Fetch command details from the database
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage roles
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage roles in this server.',
            ]);

            return;
        }

        // 1️⃣ Parse command arguments
        $parts = explode(' ', $this->messageContent);

        // If not enough parameters, send usage message
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        // 2️⃣ Extract role details
        $roleName = $parts[1];
        $roleColor = (int) $this->defaultRoleSettings['color']; // ✅ Ensure integer format
        $roleHoist = $this->defaultRoleSettings['hoist'];

        // 3️⃣ Handle optional color argument
        if (isset($parts[2]) && preg_match('/^#?([0-9a-fA-F]{6})$/', $parts[2], $matches)) {
            $roleColor = hexdec($matches[1]);
        }

        // 4️⃣ Handle optional hoist argument
        if (isset($parts[3]) && strtolower($parts[3]) === 'yes') {
            $roleHoist = true;
        }

        dump('Final Role Color (Decimal)', $roleColor); // ✅ Debugging output

        // 5️⃣ Fetch existing roles
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')
            ->get("{$this->baseUrl}/guilds/{$this->guildId}/roles");

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);

            return;
        }

        $existingRoles = $rolesResponse->json();

        // 6️⃣ Check if the role exists
        foreach ($existingRoles as $role) {
            if (strcasecmp($role['name'], $roleName) === 0) { // ✅ Case-insensitive comparison
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
            Log::error("Failed to create role '{$roleName}' in guild {$this->guildId}", [
                'response' => $apiResponse->json(),
            ]);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to create role '{$roleName}'.",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        $createdRole = $apiResponse->json();
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Role Created!',
            'embed_description' => "**Role Name:** {$createdRole['name']}\n**Color:** #" . strtoupper(str_pad(dechex($createdRole['color']), 6, '0', STR_PAD_LEFT)) . "\n**Displayed Separately:** " . ($createdRole['hoist'] ? '✅ Yes' : '❌ No'),
            'embed_color' => $createdRole['color'],
        ]);
    }
}
