<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelSlowmodeJob implements ShouldQueue
{
    use Queueable;

    public string $baseUrl;

    public string $usageMessage = 'Usage: !edit-channel-slowmode <channel-id> <seconds>';
    public string $exampleMessage = 'Example: !edit-channel-slowmode 123456789012345678 10';
    public string $targetChannelId;
    public int $slowmodeSetting;

    /**
     * The minimum and maximum allowed slowmode durations in seconds.
     *
     * @var array<int>
     */
    public array $slowmodeRange = [0, 21600];

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

        // Parse the message
        [$targetChannelId, $slowmodeSetting] = $this->parseMessage($this->messageContent);

        // If parsing failed, send an error message and exit
        if ($targetChannelId === null || $slowmodeSetting === null) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input format.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

        // Assign validated values
        $this->targetChannelId = $targetChannelId;
        $this->slowmodeSetting = $slowmodeSetting;
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Ensure the input is a valid Discord channel ID
        if (! preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.',
            ]);

            return;
        }

        // Ensure slowmode setting is within Discord's allowed range (0-21600 seconds)
        if ($this->slowmodeSetting < $this->slowmodeRange[0] || $this->slowmodeSetting > $this->slowmodeRange[1]) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Slowmode must be between {$this->slowmodeRange[0]} and {$this->slowmodeRange[1]} seconds (6 hours).",
            ]);

            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['rate_limit_per_user' => $this->slowmodeSetting];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update slowmode setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update slowmode setting.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Slowmode Updated!',
            'embed_description' => "**Slowmode Duration:** {$this->slowmodeSetting} seconds",
            'embed_color' => 3447003,
        ]);
    }

    private function parseMessage(string $message): array
    {
        // Use regex to parse the command properly (ensure slowmode is strictly numeric)
        preg_match('/^!edit-channel-slowmode\s+(<#\d{17,19}>|\d{17,19})\s+(\d+)$/', $message, $matches);

        // Validate if both channel and slowmode duration were provided
        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Ensure we return null values explicitly
        }

        $channelIdentifier = trim($matches[1]); // Extracted channel mention or ID
        $slowmodeSetting = (int) trim($matches[2]); // Convert to integer

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1]; // Extract just the ID
        }

        return [$channelIdentifier, $slowmodeSetting];
    }
}
