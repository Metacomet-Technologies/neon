<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;
use Illuminate\Support\Facades\Log;

final class ProcessUnmuteUserJob extends ProcessBaseJob
{
    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireMemberPermission();

        // 2. Parse and validate input using service
        $targetUserId = Discord::parseUserCommand($this->messageContent, 'unmute');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Perform unmute by removing channel permission overrides
        $success = $this->unmuteUserInVoiceChannels($targetUserId);

        if (! $success) {
            $this->sendApiError('unmute user');
            throw new Exception('Failed to unmute user.', 500);
        }

        // 4. Send confirmation using trait
        $this->sendUserActionConfirmation('unmuted', $targetUserId, '🔊');
    }

    /**
     * Unmute user by removing permission overrides in all voice channels
     */
    private function unmuteUserInVoiceChannels(string $userId): bool
    {
        // Fetch all voice channels in the guild
        $discordService = app(DiscordApiService::class);
        $channelsResponse = retry($this->maxRetries, function () use ($discordService) {
            return $discordService->get("/guilds/{$this->guildId}/channels");
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

            // Delete the permission override to restore default permissions
            $permissionsResponse = retry($this->maxRetries, function () use ($discordService, $channelId, $userId) {
                return $discordService->delete("/channels/{$channelId}/permissions/{$userId}");
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedChannels[] = $channelId;
            }
        }

        // Return true if all channels were successfully processed (no failures)
        return empty($failedChannels);
    }
}
