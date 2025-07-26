<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;

final class ProcessUnbanUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // 1. Check permissions using trait
        $this->requireBanPermission();

        // 2. Parse and validate input using service
        $targetUserId = DiscordParserService::parseUserCommand($this->messageContent, 'unban');

        if (! $targetUserId) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        $this->validateUserId($targetUserId);

        // 3. Perform unban using service
        $success = $this->discord->unbanUser($this->guildId, $targetUserId);

        if (! $success) {
            $this->sendApiError('unban user');
            throw new Exception('Failed to unban user.', 500);
        }

        // 4. Send confirmation using trait
        $this->sendUserActionConfirmation('unbanned', $targetUserId, '🔓');
    }
}
