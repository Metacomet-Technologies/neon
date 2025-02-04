<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Enums\DiscordPermissionEnum;

final class ProcessArchiveChannelJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !archive-channel <channel-id> <true|false>';
    public string $exampleMessage = 'Example: !archive-channel 123456789012345678 true';

    public string $baseUrl;
    public string $targetChannelId; // The actual Discord channel ID
    public bool $archiveStatus;     // Archive (true) or unarchive (false)

    private int $retryDelay = 2000; // ✅ 2-second delay before retrying
    private int $maxRetries = 3;     // ✅ Max retries per request

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId, // The channel where the command was sent
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        [$this->targetChannelId, $this->archiveStatus] = $this->parseMessage($this->messageContent);

        // Validate input
        if (! $this->targetChannelId || ! is_bool($this->archiveStatus)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);
        }
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage channels in this server.',
            ]);

            return;
        }
        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);

            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['archived' => $this->archiveStatus];

        // Send the request to Discord API
        $apiResponse = retry($this->maxRetries, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            Log::error("Failed to update archive status for channel (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update channel archive status.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => $this->archiveStatus ? '📂 Channel Archived' : '📂 Channel Unarchived',
            'embed_description' => "✅ Channel <#{$this->targetChannelId}> has been " . ($this->archiveStatus ? 'archived' : 'unarchived') . '.',
            'embed_color' => $this->archiveStatus ? 15158332 : 3066993, // Red for archive, Green for unarchive
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to extract the channel ID or mention and archive/unarchive flag
        preg_match('/^!archive-channel\s+(<#?(\d{17,19})>)?\s*(true|false)$/i', $message, $matches);

        if (!isset($matches[2], $matches[3])) {
            return [null, null]; // Invalid input
        }

        $channelIdentifier = trim($matches[2]); // Extracted numeric channel ID
        $archiveStatus = strtolower(trim($matches[3])) === 'true'; // Convert to boolean

        return [$channelIdentifier, $archiveStatus];
    }

}
