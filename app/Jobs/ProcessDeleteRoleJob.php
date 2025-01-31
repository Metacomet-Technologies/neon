<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteRoleJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !delete-role <role-name>';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->usageMessage,
            ]);

            return;
        }

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

            return;
        }

        // 4️⃣ Find the role by name
        $roles = $rolesResponse->json();
        $role = collect($roles)->first(fn ($r) => strcasecmp($r['name'], $roleName) === 0);

        if (! $role) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Role '{$roleName}' not found.",
            ]);

            return;
        }

        $roleId = $role['id']; // Extract role ID

        // 5️⃣ Delete the role with retry logic
        $deleteUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles/{$roleId}";

        $deleteResponse = retry(3, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
        }, 200);

        if ($deleteResponse->failed()) {
            Log::error("Failed to delete role '{$roleName}' in guild {$this->guildId}", [
                'response' => $deleteResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete role '{$roleName}'.",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Role Deleted!',
            'embed_description' => "**Role Name:** {$roleName}\n**Successfully removed from server.**",
            'embed_color' => 15158332, // Red embed
        ]);
    }
}
