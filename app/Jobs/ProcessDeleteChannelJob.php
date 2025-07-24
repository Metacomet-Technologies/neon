<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteChannelJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Ensure the user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to delete channels in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to delete channels.',
                statusCode: 403,
            );

            return;
        }

        // Parse the command message
        $targetChannelInput = $this->parseMessage($this->messageContent);

        if (! $targetChannelInput) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No channel ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Resolve channel name to ID if necessary
        $targetChannelId = $this->resolveChannelToId($targetChannelInput);

        if (! $targetChannelId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Channel '{$targetChannelInput}' not found. Please provide a valid channel name or ID.",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Channel not found.',
                statusCode: 404,
            );

            return;
        }

        // Construct the delete API request with rate limiting protection
        sleep(1); // Rate limiting delay
        $deleteUrl = $this->baseUrl . "/channels/{$targetChannelId}";

        // Make the delete request with enhanced retries
        $deleteResponse = retry(5, function () use ($deleteUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(30)
                ->delete($deleteUrl);
        }, [1000, 2000, 3000, 5000, 8000]);

        if ($deleteResponse->failed()) {
            Log::error("Failed to delete channel '{$targetChannelId}' in guild {$this->guildId}", [
                'response' => $deleteResponse->json(),
            ]);

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to delete channel (ID: `{$targetChannelId}`).",
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to delete channel.',
                statusCode: 500,
            );

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ Channel Deleted!',
            'embed_description' => "**Channel ID:** `{$targetChannelId}` has been successfully removed.",
            'embed_color' => 15158332, // Red embed
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for extracting the target channel ID.
     */
    private function parseMessage(string $message): ?string
    {
        // Remove invisible characters (zero-width spaces, control characters)
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message); // Removes control characters
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage)); // Normalize spaces

        // More flexible regex to handle various input formats
        if (preg_match('/^!delete-channel\s+(.+)$/iu', $cleanedMessage, $matches)) {
            $channelInput = trim($matches[1]);

            // If channel mention format (<#channelID>), extract the numeric ID
            if (preg_match('/^<#(\d{17,19})>$/', $channelInput, $channelMatches)) {
                return $channelMatches[1]; // Extract numeric channel ID
            }

            // Return the input as-is (could be channel name or numeric ID)
            return $channelInput;
        }

        return null; // Invalid input
    }

    /**
     * Resolve channel name or ID to Discord channel ID
     */
    private function resolveChannelToId(string $channelInput): ?string
    {
        // If it's already a numeric Discord ID, return it
        if (preg_match('/^\d{17,19}$/', $channelInput)) {
            return $channelInput;
        }

        // Fetch all channels in the guild with circuit breaker
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";
        $cacheKey = "discord_api_failure_{$this->guildId}";
        $failureCount = Cache::get($cacheKey, 0);

        if ($failureCount >= 3) {
            Log::warning("Circuit breaker: Too many API failures for guild {$this->guildId}");
            return null; // Fail gracefully
        }

        $channelsResponse = retry(5, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(30)
                ->get($channelsUrl);
        }, [1000, 2000, 3000, 5000, 8000]);

        if ($channelsResponse->failed()) {
            Cache::put($cacheKey, $failureCount + 1, now()->addMinutes(5));
            Log::error("Failed to fetch channels for guild {$this->guildId}");
            return null;
        }

        // Reset circuit breaker on success
        Cache::forget($cacheKey);

        $channels = collect($channelsResponse->json());

        // Find channel by name (case insensitive) - types 0 (text) and 2 (voice)
        $channel = $channels->first(function ($ch) use ($channelInput) {
            return in_array($ch['type'], [0, 2]) && strcasecmp($ch['name'], $channelInput) === 0;
        });

        return $channel ? $channel['id'] : null;
    }
}
