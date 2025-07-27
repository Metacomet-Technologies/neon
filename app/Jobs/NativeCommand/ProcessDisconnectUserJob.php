<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessDisconnectUserJob extends ProcessBaseJob
{
    // TODO: May want to add logic to have channel id instead of user, which would disconnect all users in that channel.
    protected function executeCommand(): void
    {
        // Check if user has permission to manage channels
        $this->requireChannelPermission();

        // Parse the message
        $targetUserIds = DiscordService::extractUserIds($this->parseMessage($this->messageContent));

        if (empty($targetUserIds)) {
            $this->sendUsageAndExample();
            throw new Exception('No user ID provided.', 400);
        }

        // Disconnect each user from their current voice channel using batch operation
        $results = $this->getDiscord()->batchOperation($targetUserIds, function ($userId) {
            return $this->getDiscord()->disconnectUser($this->guildId, $userId);
        });

        // Send response message
        if (! empty($results['failed'])) {
            $failedUsers = array_map(fn ($userId) => "<@{$userId}>", $results['failed']);
            $this->sendBatchResults('Voice disconnect', [], $failedUsers);
            throw new Exception('Operation failed', 500);
        } else {
            $this->sendSuccessMessage(
                'Users Disconnected from Voice Channel',
                'Successfully disconnected users from voice chat.'
            );
        }
    }

    private function parseMessage(string $message): array
    {
        preg_match_all('/<@!?(\d{17,19})>/', $message, $matches);

        return $matches[1] ?? [];
    }
}
