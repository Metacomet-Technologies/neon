<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteChannelJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;
    public string $usageMessage = 'Usage: !delete-channel <channel-id>';
    public string $exampleMessage = 'Example: !delete-channel 123456789012345678';

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1️⃣ Parse the command
        $parts = explode(' ', $this->messageContent);

        if (count($parts) < 2) {
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->usageMessage]);
            SendMessage::sendMessage($this->channelId, ['is_embed' => false, 'response' => $this->exampleMessage]);

            return;
        }

        $targetChannelId = $parts[1];

        // Ensure the provided channel ID is numeric
        if (! is_numeric($targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please provide a valid numeric ID.',
            ]);

            return;
        }

        // 2️⃣ Ensure the user has permission to delete channels
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to delete channels.',
            ]);

            return;
        }

        // 3️⃣ Construct the delete API request
        $deleteUrl = $this->baseUrl . "/channels/{$targetChannelId}";

        // 4️⃣ Make the delete request with retries
        $deleteResponse = retry(3, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
        }, 200);

        if ($deleteResponse->failed()) {
            Log::error("Failed to delete channel '{$targetChannelId}' in guild {$this->guildId}", [
                'response' => $deleteResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete channel (ID: `{$targetChannelId}`).",
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Deleted!',
            'embed_description' => "**Channel ID:** `{$targetChannelId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
    }
}
