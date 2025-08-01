<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessBanUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireBanPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordService::parseUserCommand($this->messageContent, 'ban');
        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Check role hierarchy using service
        $senderRole = $this->getDiscord()->getUserHighestRolePosition($this->guildId, $this->discordUserId);
        $targetRole = $this->getDiscord()->getUserHighestRolePosition($this->guildId, $targetUserId);

        if ($senderRole <= $targetRole) {
            $this->sendErrorMessage('You cannot ban this user. Their role is equal to or higher than yours.');
            throw new Exception('Insufficient role hierarchy.', 403);
        }

        // 4. Perform ban using service
        $success = $this->getDiscord()->banUser($this->guildId, $targetUserId);

        if (! $success) {
            $this->sendApiError('ban user');
            throw new Exception('Failed to ban user.', 500);
        }

        // 5. Send confirmation using trait
        $this->sendUserActionConfirmation('banned', $targetUserId, '🔨');
    }
}
