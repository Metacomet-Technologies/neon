<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessUnmuteUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !unmute <user-id>';
    public string $exampleMessage = 'Example: !unmute 123456789012345678';

    private string $baseUrl;
    private string $targetUserId; // The user being unmuted

    private int $retryDelay = 2000; // ✅ 2-second delay before retrying
    private int $maxRetries = 3;    // ✅ Max retries per request

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
        $this->targetUserId = $this->parseMessage($this->messageContent);

        // Validate input
        if (! $this->targetUserId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid user ID.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            throw new Exception('Invalid input for !unmute. Expected a valid user ID.');
        }
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanMuteMembers($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to mute/unmute users in this server.',
            ]);

            return;
        }
        // Ensure the input is a valid Discord user ID
        if (! preg_match('/^\d{17,19}$/', $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Invalid user ID format. Please provide a valid Discord user ID.',
            ]);

            return;
        }

        // Step 1️⃣: Fetch all voice channels in the guild
        $channelsUrl = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $channelsResponse = retry($this->maxRetries, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, $this->retryDelay);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild (ID: `{$this->guildId}`).");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve server channels.',
            ]);

            return;
        }

        $channels = collect($channelsResponse->json());
        $voiceChannels = $channels->filter(fn ($ch) => $ch['type'] === 2); // Voice channels only

        // Step 2️⃣: Remove mute permission in each voice channel
        $failedChannels = [];

        foreach ($voiceChannels as $channel) {
            $channelId = $channel['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$channelId}/permissions/{$this->targetUserId}";

            $payload = [
                'deny' => 0, // Remove all deny flags (restores default)
                'type' => 1, // Member override
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedChannels[] = "<#{$channelId}>";
            }
        }

        // Step 3️⃣: Send Confirmation Message
        if (! empty($failedChannels)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔊 Unmute Failed',
                'embed_description' => '❌ Failed to unmute user in: ' . implode(', ', $failedChannels),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🔊 User Unmuted',
                'embed_description' => "✅ <@{$this->targetUserId}> has been unmuted in **all voice channels**.",
                'embed_color' => 3066993, // Green
            ]);
        }
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): ?string
    {
        // Use regex to extract user ID from mention OR raw ID
        preg_match('/^!unmute\s+(<@!?(\d{17,19})>|\d{17,19})$/', $message, $matches);

        // If user ID is wrapped in <@...>, extract only the ID
        return $matches[2] ?? $matches[1] ?? null;
    }
}
