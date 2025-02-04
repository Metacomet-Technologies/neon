<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelAutohideJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !edit-channel-autohide <channel-id> <minutes>';
    public string $exampleMessage = 'Example: !edit-channel-autohide 123456789012345678 1440';

    public string $baseUrl;
    public string $targetChannelId; // The actual Discord channel ID
    public int $autoHideDuration;   // Auto-hide duration in minutes

    /**
     * Allowed auto-hide durations (in minutes).
     */
    private array $allowedDurations = [60, 1440, 4320, 10080];

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
        [$this->targetChannelId, $this->autoHideDuration] = $this->parseMessage($this->messageContent);

        // Validate that we got valid values
        if (! $this->targetChannelId || ! in_array($this->autoHideDuration, $this->allowedDurations, true)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid auto-hide duration.\n\n{$this->usageMessage}\n{$this->exampleMessage}\n\nAllowed values: `60, 1440, 4320, 10080` minutes.",
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
                'response' => '❌ You do not have permission to edit channels in this server.',
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
        $payload = ['default_auto_archive_duration' => $this->autoHideDuration];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update auto-hide setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update auto-hide setting.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Auto-Hide Updated!',
            'embed_description' => "**Auto-hide Duration:** ⏲️ `{$this->autoHideDuration} minutes`",
            'embed_color' => 3447003,
        ]);
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Use regex to parse the command properly
        preg_match('/^!edit-channel-autohide\s+(<#\d{17,19}>|\d{17,19})\s+(\d+)$/', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }

        $channelIdentifier = trim($matches[1]);
        $autoHideDuration = (int) trim($matches[2]);

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1];
        }

        return [$channelIdentifier, $autoHideDuration];
    }
}
