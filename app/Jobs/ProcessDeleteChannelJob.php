<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDeleteChannelJob extends ProcessBaseJob
{
    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

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
            throw new Exception('Missing required parameters.', 400);
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

        if (! $success) {
            $this->sendApiError('delete channel');
            throw new Exception('Failed to delete channel.', 500);
        }

        // 4. Send confirmation
        $this->sendSuccessMessage(
            'Channel Deleted!',
            "🗑️ Channel (ID: `{$targetChannelId}`) has been successfully removed.",
            15158332 // Red color
        );
    }

    /**
     * Parse delete channel command to extract channel ID.
     */
    private function parseDeleteChannelCommand(string $messageContent): ?string
    {
        // Remove invisible characters and normalize spaces
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $messageContent);
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage));

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
