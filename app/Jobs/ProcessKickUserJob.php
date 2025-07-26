<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;

final class ProcessKickUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireMemberPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordParserService::parseUserCommand($this->messageContent, 'kick');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Check role hierarchy using service
        $senderRole = $this->discord->getUserHighestRolePosition($this->guildId, $this->discordUserId);
        $targetRole = $this->discord->getUserHighestRolePosition($this->guildId, $targetUserId);

        if ($senderRole <= $targetRole) {
            $this->sendErrorMessage('You cannot kick this user. Their role is equal to or higher than yours.');
            throw new Exception('Insufficient role hierarchy.', 403);
        }

        // 4. Perform kick using service
        $success = $this->discord->kickUser($this->guildId, $targetUserId);

        if (! $success) {
            $this->sendApiError('kick user');
            throw new Exception('Failed to kick user.', 500);
        }

        // 5. Send confirmation using trait
        $this->sendUserActionConfirmation('kicked', $targetUserId, '👢');
    }
}
