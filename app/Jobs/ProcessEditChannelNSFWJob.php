<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelNSFWJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !edit-channel-nsfw <channel-id> <true|false>';
    public string $exampleMessage = 'Example: !edit-channel-nsfw 123456789012345678 true';

    private string $baseUrl;
    private string $channelId;       // The Discord channel where the command was sent
    private string $guildId;         // The guild (server) ID
    private string $targetChannelId; // The actual Discord channel ID to edit
    private bool $nsfwSetting;       // Whether the channel should be NSFW or not

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->channelId = $channelId;
        $this->guildId = $guildId;

        // Parse the message
        [$this->targetChannelId, $this->nsfwSetting] = $this->parseMessage($messageContent);
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

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['nsfw' => $this->nsfwSetting];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        if ($apiResponse->failed()) {
            Log::error("Failed to update NSFW setting (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to update NSFW setting.',
            ]);

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ NSFW Setting Updated!',
            'embed_description' => '**NSFW Enabled:** ' . ($this->nsfwSetting ? 'Yes' : 'No'),
            'embed_color' => 3447003,
        ]);
    }

    private function parseMessage(string $message): array
    {
        // Use regex to parse the command properly
        preg_match('/^!edit-channel-nsfw\s+(<#\d{17,19}>|\d{17,19})\s+(true|false)$/i', $message, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }

        $channelIdentifier = $matches[1]; // Extracted channel mention or ID
        $nsfwSetting = strtolower(trim($matches[2])) === 'true'; // Convert to boolean

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1]; // Extract just the ID
        }

        return [$channelIdentifier, $nsfwSetting];
    }
}
