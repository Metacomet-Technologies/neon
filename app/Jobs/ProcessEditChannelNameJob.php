<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessEditChannelNameJob implements ShouldQueue
{
    use Queueable;

    private string $baseUrl;
    private string $channelId;       // The Discord channel where the command was sent
    private string $guildId;         // The guild (server) ID
    private string $targetChannelId; // The actual Discord channel ID to rename
    private string $newName;         // The new channel name

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !rename-channel <channel-id> <new-name>';
    public string $exampleMessage = 'Example: !rename-channel 123456789012345678 new-channel-name';

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
        [$this->targetChannelId, $this->newName] = $this->parseMessage($messageContent);

    }

    private function parseMessage(string $message): array
    {


        // Use regex to parse the command properly
        preg_match('/^!edit-channel-name\s+(<#\d{17,19}>|\d{17,19})\s+(.+)$/', $message, $matches);

        if (!isset($matches[1], $matches[2])) {
            return [null, null]; // Not enough valid parts
        }

        $channelIdentifier = $matches[1]; // Extracted channel mention or ID
        $newName = trim($matches[2]); // Extracted new name

        // If the channel is mentioned as <#channelID>, extract just the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelIdentifier, $idMatches)) {
            $channelIdentifier = $idMatches[1]; // Extract just the ID
        }

        return [$channelIdentifier, $newName];
    }


    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Ensure the input is a valid Discord channel ID
        if (!preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.'
            ]);
            return;
        }

        // Build API request
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}";
        $payload = ['name' => $this->newName];

        // Send the request to Discord API
        $apiResponse = retry(3, function () use ($url, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')->patch($url, $payload);
        }, 200);

        // Debug: Show API response
        dump(['API Response' => $apiResponse->json(), 'HTTP Status' => $apiResponse->status()]);

        if ($apiResponse->failed()) {
            Log::error("Failed to rename channel (ID: `{$this->targetChannelId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to rename channel.'
            ]);
            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Renamed!',
            'embed_description' => "**New Name:** #{$this->newName}",
            'embed_color' => 3447003
        ]);
    }
}
