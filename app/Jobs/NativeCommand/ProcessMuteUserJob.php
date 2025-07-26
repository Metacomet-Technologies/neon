<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;
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
        $targetUserId = Discord::parseUserCommand($this->messageContent, 'mute');

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

        try {
            $discord = new Discord;
            $guild = $discord->guild($this->guildId);

            // Get all voice channels
            $channels = $guild->channels()->voice()->get();

            // Extract channel IDs
            $channelIds = $channels->pluck('id')->toArray();

            // Use the member mute method
            $member = $guild->member($userId);
            $results = $member->muteInChannels($channelIds);

            // Check if all operations were successful
            $failedChannels = array_filter($results, fn ($success) => ! $success);

            if (! empty($failedChannels)) {
                Log::error('Failed to mute user in some channels', [
                    'user_id' => $userId,
                    'failed_channels' => array_keys($failedChannels),
                ]);
            }

            return empty($failedChannels);
        } catch (Exception $e) {
            Log::error('Failed to mute user in voice channels', [
                'user_id' => $userId,
                'guild_id' => $this->guildId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

    }
}
