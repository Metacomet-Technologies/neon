<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessMuteUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireMemberPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordService::parseUserCommand($this->messageContent, 'mute');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Check role hierarchy using service
        $senderRole = $this->getDiscord()->getUserHighestRolePosition($this->guildId, $this->discordUserId);
        $targetRole = $this->getDiscord()->getUserHighestRolePosition($this->guildId, $targetUserId);

        if ($senderRole <= $targetRole) {
            $this->sendErrorMessage('You cannot mute this user. Their role is equal to or higher than yours.');
            throw new Exception('Insufficient role hierarchy.', 403);
        }

        // 4. Perform mute using Discord service
        $success = $this->getDiscord()->muteUser($this->guildId, $targetUserId);

        if (! $success) {
            $this->sendApiError('mute user');
            throw new Exception('Failed to mute user.', 500);
        }

        // 5. Send confirmation using trait
        $this->sendUserActionConfirmation('muted', $targetUserId, '🔇');
    }
}
