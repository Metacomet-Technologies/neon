<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessMoveUserJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public string $targetChannelId;
    public string $userId;
    public int $retryDelay = 2000; // 2-second delay before retrying
    public int $maxRetries = 3; // Max retries per request

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    // TODO: Add the handle method to move the user to the target channel based on name and not ID only.


    public function handle(): void
    {
        // Parse the message
        [$this->userId, $this->targetChannelId] = $this->parseMessage($this->messageContent);

        // Check if the user has permission to move members
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanMoveMembers($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to move users.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to move members',
                statusCode: 403,
            );

            return;
        }

        // Validate the user and channel IDs
        if (! $this->userId || ! $this->targetChannelId) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Construct the API request to move the user
        $moveUrl = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$this->userId}";
        $payload = ['channel_id' => $this->targetChannelId];

        // Retry logic to move the user
        $apiResponse = retry($this->maxRetries, function () use ($moveUrl, $payload) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->patch($moveUrl, $payload);
        }, $this->retryDelay);

        if ($apiResponse->failed()) {
            Log::error("Failed to move user '{$this->userId}' to channel '{$this->targetChannelId}' in guild {$this->guildId}", [
                'response' => $apiResponse->json(),
            ]);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to move user.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to move user.',
                details: $apiResponse->json(),
                statusCode: $apiResponse->status(),
            );

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ User Moved',
            'embed_description' => "Successfully moved <@{$this->userId}> to the channel <#{$this->targetChannelId}>.",
            'embed_color' => 3066993, // Green
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    private function parseMessage(string $message): array
    {
        // Remove extra spaces before processing
        $message = trim($message);

        // Split message into parts
        $parts = explode(' ', $message);

        if (count($parts) != 3) {
            $this->sendUsageAndExample();

            return [null, null];
        }

        $userId = $parts[1]; // User ID (raw or mention)
        $channelId = $parts[2]; // Channel ID (raw)

        // Validate that the channel ID is a valid numeric ID
        if (! preg_match('/^\d{17,19}$/', $channelId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid channel ID format: {$channelId}",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid channel ID format.',
                statusCode: 400,
            );

            return [null, null]; // Invalid format
        }

        // Validate user ID: it can be a mention or raw ID
        $userId = $this->extractIdFromMention($userId);

        if (! $userId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid user ID format: {$parts[1]}",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Invalid user ID format.',
                statusCode: 400,
            );

            return [null, null]; // Invalid user ID format
        }

        return [$userId, $channelId];
    }

    /**
     * Helper function to extract the raw ID from a mention or raw ID input.
     */
    private function extractIdFromMention(string $mentionOrId): ?string
    {
        // If it's a mention, remove the <@...> or <@!userID> tags
        if (preg_match('/^<@!?(\d{17,19})>$/', $mentionOrId, $matches)) {
            return $matches[1]; // Return the raw user ID
        }

        // If it's already a raw ID, return it
        if (preg_match('/^\d{17,19}$/', $mentionOrId)) {
            return $mentionOrId;
        }

        return null; // Invalid format
    }
}
