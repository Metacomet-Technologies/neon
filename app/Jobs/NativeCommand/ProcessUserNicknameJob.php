<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessUserNicknameJob extends ProcessBaseJob
{
    protected function executeCommand(): void
    {
        // Parse message content
        [$targetUserId, $newNickname] = $this->parseMessage($this->messageContent);

        // If parsing failed, send an error message and abort execution
        if (is_null($targetUserId) || is_null($newNickname)) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid input for !set-nickname. Expected a valid user mention and nickname.');
        }

        // Check if the user has permission to change nicknames
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageNicknames()) {
            $this->sendPermissionDenied('change nicknames');
            throw new Exception('User lacks permission to change nicknames.', 403);
        }

        // Validate target user ID format
        $this->validateUserId($targetUserId);

        // TODO: Check role hierarchy to prevent elevation

        // Update nickname using Discord service
        $success = $this->getDiscord()->updateUserNickname($this->guildId, $targetUserId, $newNickname);

        if (! $success) {
            $this->sendApiError('update nickname');
            throw new Exception('Failed to update nickname.', 500);
        }

        // Send confirmation
        $this->sendSuccessMessage(
            'Nickname Updated',
            "✅ <@{$targetUserId}>'s nickname has been updated to **{$newNickname}**.",
            3447003
        );
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Normalize message format
        $message = trim(preg_replace('/\s+/', ' ', $message));

        // Match command format: `!set-nickname <@UserID> NewNickname`
        preg_match('/^!set-nickname\s+<@!?(\d{17,19})>\s+(.+)$/', $message, $matches);

        if (! isset($matches[1]) || ! isset($matches[2])) {
            return [null, null];
        }

        return [$matches[1], trim($matches[2])];
    }
}
