<?php

declare(strict_types=1);

namespace App\Traits;

use Exception;

/**
 * Trait for handling Discord permission checks.
 * Requires channelId, guildId, and discordUserId properties.
 */
trait DiscordPermissionTrait
{
    use DiscordBaseTrait;

    /**
     * Check if user has role management permissions and send error if not.
     */
    protected function requireRolePermission(): void
    {
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageRoles()) {
            $this->sendPermissionError('You do not have permission to manage roles in this server.');
        }
    }

    /**
     * Check if user has channel management permissions and send error if not.
     */
    protected function requireChannelPermission(): void
    {
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageChannels()) {
            $this->sendPermissionError('You do not have permission to manage channels in this server.');
        }
    }

    /**
     * Check if user has member management permissions and send error if not.
     */
    protected function requireMemberPermission(): void
    {
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canKickMembers()) {
            $this->sendPermissionError('You do not have permission to manage members in this server.');
        }
    }

    /**
     * Check if user has ban permissions and send error if not.
     */
    protected function requireBanPermission(): void
    {
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canKickMembers()) {
            $this->sendPermissionError('You do not have permission to ban members in this server.');
        }
    }

    /**
     * Check if user has admin permissions and send error if not.
     */
    protected function requireAdminPermission(): void
    {
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->isAdmin()) {
            $this->sendPermissionError('You do not have administrator permissions in this server.');
        }
    }

    /**
     * Send permission error and throw exception.
     */
    private function sendPermissionError(string $errorMessage): void
    {
        $this->getDiscord()->channel($this->channelId)->send("❌ {$errorMessage}");
        throw new Exception($errorMessage, 403);
    }
}
