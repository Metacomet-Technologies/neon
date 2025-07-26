<?php

declare(strict_types=1);

namespace App\Traits;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;

trait DiscordPermissionTrait
{
    /**
     * Check if user has role management permissions and send error if not.
     */
    protected function requireRolePermission(): void
    {
        $this->requirePermission(
            GetGuildsByDiscordUserId::getIfUserCanManageRoles($this->guildId, $this->discordUserId),
            'You do not have permission to manage roles in this server.'
        );
    }

    /**
     * Check if user has channel management permissions and send error if not.
     */
    protected function requireChannelPermission(): void
    {
        $this->requirePermission(
            GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId),
            'You do not have permission to manage channels in this server.'
        );
    }

    /**
     * Check if user has member management permissions and send error if not.
     */
    protected function requireMemberPermission(): void
    {
        $this->requirePermission(
            GetGuildsByDiscordUserId::getIfUserCanKickMembers($this->guildId, $this->discordUserId),
            'You do not have permission to manage members in this server.'
        );
    }

    /**
     * Check if user has ban permissions and send error if not.
     */
    protected function requireBanPermission(): void
    {
        $this->requirePermission(
            GetGuildsByDiscordUserId::getIfUserCanKickMembers($this->guildId, $this->discordUserId),
            'You do not have permission to ban members in this server.'
        );
    }

    /**
     * Check if user has admin permissions and send error if not.
     */
    protected function requireAdminPermission(): void
    {
        $this->requirePermission(
            GetGuildsByDiscordUserId::getIfUserIsAdmin($this->guildId, $this->discordUserId),
            'You do not have administrator permissions in this server.'
        );
    }

    /**
     * Generic permission checker with error handling.
     */
    private function requirePermission(string $permissionCheck, string $errorMessage): void
    {
        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ {$errorMessage}",
            ]);
            throw new Exception($errorMessage, 403);
        }
    }
}
