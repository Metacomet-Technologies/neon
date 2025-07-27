<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessMoveUserJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        $this->requireMemberPermission();

        [$userId, $targetChannelId] = $this->parseMessage($this->messageContent);

        if (! $userId || ! $targetChannelId) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateUserId($userId);
        $this->validateChannelId($targetChannelId);

        $success = $this->getDiscord()->moveUserToChannel($this->guildId, $userId, $targetChannelId);

        if (! $success) {
            $this->sendApiError('move user');
            throw new Exception('Failed to move user.', 500);
        }

        $this->sendUserActionConfirmation('moved', $userId, '🔄');
        $this->sendInfoMessage(
            'User Moved',
            "Successfully moved <@{$userId}> to <#{$targetChannelId}>."
        );
    }

    private function parseMessage(string $message): array
    {
        $parts = explode(' ', trim($message));

        if (count($parts) !== 3) {
            return [null, null];
        }

        $userId = DiscordService::extractUserId($parts[1]);
        $channelId = DiscordService::extractChannelId($parts[2]);

        return [$userId, $channelId];
    }
}
