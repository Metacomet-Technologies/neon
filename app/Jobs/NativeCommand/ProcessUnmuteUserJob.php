<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessUnmuteUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireMemberPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordService::parseUserCommand($this->messageContent, 'unmute');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Perform unmute using Discord service
        $success = $this->getDiscord()->unmuteUser($this->guildId, $targetUserId);

        if (! $success) {
            $this->sendApiError('unmute user');
            throw new Exception('Failed to unmute user.', 500);
        }

        // 4. Send confirmation using trait
        $this->sendUserActionConfirmation('unmuted', $targetUserId, '🔊');
    }
}
