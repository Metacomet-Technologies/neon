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
    public string $usageMessage = 'Usage: !delete-channel <channel-id|channel-name>';
    public string $exampleMessage = 'Example: !delete-channel 123456789012345678 or !delete-channel #general';

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
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to delete channels in this server.',
            ]);

            return;
        }

        // Parse the command message
        $targetChannelId = $this->parseMessage($this->messageContent);

        if (! $targetChannelId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
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

    /**
     * Parses the message content for extracting the target channel ID.
     */
    private function parseMessage(string $message): ?string
    {
        // Remove invisible characters (zero-width spaces, control characters)
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message); // Removes control characters
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage)); // Normalize spaces

        // Use regex to extract the channel ID or name
        preg_match('/^!delete-channel\s+(<#?(\d{17,19})>|[\w-]+)$/iu', $cleanedMessage, $matches);

        if (! isset($matches[2])) {
            return null; // Invalid input
        }

        $channelInput = trim($matches[2]); // This could be <#channelID> or channel name

        // If channel mention format (<#channelID>), extract the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelInput, $channelMatches)) {
            return $channelMatches[1]; // Extract numeric channel ID
        }

        return $channelInput;
    }
}
