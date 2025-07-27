<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand\Base;

use App\Services\Discord\DiscordService;
use Exception;

abstract class ProcessChannelVisibilityBaseJob extends ProcessBaseJob
{
    /**
     * Get the permission changes to apply.
     *
     * @return array{deny: string, allow: string}
     */
    abstract protected function getPermissions(): array;

    /**
     * Get the action name for confirmation message.
     */
    abstract protected function getActionName(): string;

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse channel mention
        $targetChannelId = $this->parseChannelMention($this->messageContent);
        if (! $targetChannelId) {
            $this->sendUsageAndExample();
            throw new Exception('Channel not specified.', 400);
        }

        $this->validateChannelId($targetChannelId);

        // 3. Get everyone role
        $everyoneRole = $this->getDiscord()->getEveryoneRole($this->guildId);
        if (! $everyoneRole) {
            $this->sendApiError('find @everyone role');
            throw new Exception('Could not find @everyone role.', 500);
        }

        // 4. Update channel permissions
        $permissions = $this->getPermissions();
        $success = $this->getDiscord()->updateChannelPermissions($targetChannelId, $everyoneRole['id'], [
            'type' => 0, // Role permission
            'deny' => (int) $permissions['deny'],
            'allow' => (int) $permissions['allow'],
        ]);

        if (! $success) {
            $this->sendApiError('update channel permissions');
            throw new Exception('Failed to update channel permissions.', 500);
        }

        // 5. Send confirmation
        $this->sendChannelActionConfirmation($this->getActionName(), $targetChannelId);
    }

    private function parseChannelMention(string $content): ?string
    {
        $parts = explode(' ', trim($content));
        $mentionedChannel = $parts[1] ?? null;

        if (! $mentionedChannel) {
            return null;
        }

        return DiscordService::extractChannelId($mentionedChannel);
    }
}
