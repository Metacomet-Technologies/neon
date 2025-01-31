<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessLockChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public string $usageMessage = 'Usage: !lock-channel <channel-id>';
    public int $retryDelay = 2000; // ✅ 2-second delay before retrying
    public int $maxRetries = 3; // ✅ Max retries per request

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $userId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Validate input
        $parts = explode(' ', $this->messageContent);
        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => $this->usageMessage,
            ]);
            return;
        }

        // 1️⃣ Get all roles in the guild
        $rolesUrl = config('services.discord.rest_api_url') . "/guilds/{$this->guildId}/roles";
        $rolesResponse = retry($this->maxRetries, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, $this->retryDelay);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);
            return;
        }

        $roles = $rolesResponse->json();
        $failedRoles = [];

        // 2️⃣ Lock the channel by updating permissions for all roles
        foreach ($roles as $role) {
            $roleId = $role['id'];
            $permissionsUrl = config('services.discord.rest_api_url') . "/channels/{$this->channelId}/permissions/{$roleId}";

            $payload = [
                'deny' => (1 << 11), // Deny SEND_MESSAGES permission
                'type' => 0, // Role
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedRoles[] = $role['name'];
            }
        }

        // 3️⃣ Send Response Message
        if (!empty($failedRoles)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔒 Lock Channel Failed',
                'embed_description' => "❌ Failed to lock for roles: " . implode(', ', $failedRoles),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔒 Channel Locked',
                'embed_description' => "✅ Channel <#{$this->channelId}> has been locked.",
                'embed_color' => 3066993, // Green
            ]);
        }
    }
}
