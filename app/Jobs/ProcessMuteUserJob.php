<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessMuteUserJob extends ProcessBaseJob
{
    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireMemberPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordParserService::parseUserCommand($this->messageContent, 'mute');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Check role hierarchy using service
        $senderRole = $this->discord->getUserHighestRolePosition($this->guildId, $this->discordUserId);
        $targetRole = $this->discord->getUserHighestRolePosition($this->guildId, $targetUserId);

        if ($senderRole <= $targetRole) {
            $this->sendErrorMessage('You cannot mute this user. Their role is equal to or higher than yours.');
            throw new Exception('Insufficient role hierarchy.', 403);
        }

        // 4. Perform mute by applying channel permissions to all voice channels
        $success = $this->muteUserInVoiceChannels($targetUserId);

        if (! $success) {
            $this->sendApiError('mute user');
            throw new Exception('Failed to mute user.', 500);
        }

        // 5. Send confirmation using trait
        $this->sendUserActionConfirmation('muted', $targetUserId, '🔇');
    }

    /**
     * Mute user by denying SPEAK and STREAM permissions in all voice channels
     */
    private function muteUserInVoiceChannels(string $userId): bool
    {
        // Fetch all voice channels in the guild
        $channelsUrl = "{$this->baseUrl}/guilds/{$this->guildId}/channels";
        $channelsResponse = retry($this->maxRetries, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($channelsUrl);
        }, $this->retryDelay);

        if ($channelsResponse->failed()) {
            Log::error("Failed to fetch channels for guild {$this->guildId}");

            return false;
        }

        $channels = collect($channelsResponse->json());
        $voiceChannels = $channels->filter(fn ($ch) => $ch['type'] === 2); // Voice channels only

        $failedChannels = [];

        foreach ($voiceChannels as $channel) {
            $channelId = $channel['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$channelId}/permissions/{$userId}";

            $payload = [
                'deny' => (1 << 11) | (1 << 23), // Deny SPEAK and STREAM permissions
                'type' => 1, // Member override
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedChannels[] = $channelId;
            }
        }

        // Return true if all channels were successfully processed (no failures)
        return empty($failedChannels);
    }
}
