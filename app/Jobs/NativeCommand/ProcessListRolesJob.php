<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class ProcessListRolesJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        try {
            // Fetch all roles from the Discord server
            $roles = $this->getDiscord()->guild($this->guildId)->roles();

            if (! $roles) {
                $this->sendApiError('fetch server roles');
                throw new Exception('Failed to fetch roles.', 500);
            }

            // Filter out @everyone role and sort by position (higher position = higher in hierarchy)
            $filteredRoles = array_filter($roles->toArray(), function ($role) {
                return $role['name'] !== '@everyone';
            });

            // Sort by position (descending - highest roles first)
            usort($filteredRoles, function ($a, $b) {
                return $b['position'] <=> $a['position'];
            });

            if (empty($filteredRoles)) {
                $this->getDiscord()->channel($this->channelId)->sendEmbed(
                    '📋 Server Roles',
                    'No custom roles found in this server.',
                    3447003 // Blue
                );

                return;
            }

            // Format roles list
            $rolesList = [];
            $totalMembers = 0;

            foreach ($filteredRoles as $role) {
                $memberCount = $this->getRoleMemberCount($role['id']);
                $totalMembers += $memberCount;

                $color = $role['color'] ? sprintf('#%06X', $role['color']) : 'Default';
                $hoisted = $role['hoist'] ? '📌 ' : '';
                $mentionable = $role['mentionable'] ? '🔔 ' : '';

                $rolesList[] = sprintf(
                    '**%s%s%s** • %d members • %s',
                    $hoisted,
                    $mentionable,
                    $role['name'],
                    $memberCount,
                    $color
                );
            }

            // Create embed description
            $description = '**Total Roles:** ' . count($filteredRoles) . "\n";
            $description .= '**Total Role Members:** ' . $totalMembers . "\n\n";
            $description .= "📌 = Display separately (hoisted)\n";
            $description .= "🔔 = Mentionable by everyone\n\n";
            $description .= implode("\n", $rolesList);

            // Split into multiple messages if too long (Discord embed limit is 4096 characters)
            if (strlen($description) > 4000) {
                $this->sendRoleListInChunks($filteredRoles);
            } else {
                $this->getDiscord()->channel($this->channelId)->sendEmbed(
                    '📋 Server Roles',
                    $description,
                    3447003 // Blue
                );
            }

            Log::info('Roles listed successfully', [
                'guild_id' => $this->guildId,
                'role_count' => count($filteredRoles ?? []),
            ]);

        } catch (Exception $e) {
            Log::error('Error in ProcessListRolesJob', [
                'error' => $e->getMessage(),
                'guild_id' => $this->guildId,
                'user_id' => $this->discordUserId,
                'trace' => $e->getTraceAsString(),
            ]);

            $this->sendErrorMessage('An error occurred while fetching server roles. Please try again later.');
            throw $e;
        }
    }

    /**
     * Get the number of members with a specific role
     */
    private function getRoleMemberCount(string $roleId): int
    {
        try {
            $members = $this->getDiscord()->getGuildMembers($this->guildId, 1000);

            if (! $members) {
                return 0;
            }
            $count = 0;

            foreach ($members as $member) {
                if (in_array($roleId, $member['roles'])) {
                    $count++;
                }
            }

            return $count;
        } catch (Exception $e) {
            Log::warning("Failed to get member count for role {$roleId}", [
                'error' => $e->getMessage(),
                'guild_id' => $this->guildId,
            ]);

            return 0;
        }
    }

    /**
     * Send role list in multiple chunks if it's too long
     */
    private function sendRoleListInChunks(array $roles): void
    {
        $chunks = array_chunk($roles, 10); // 10 roles per message

        foreach ($chunks as $index => $chunk) {
            $rolesList = [];

            foreach ($chunk as $role) {
                $memberCount = $this->getRoleMemberCount($role['id']);
                $color = $role['color'] ? sprintf('#%06X', $role['color']) : 'Default';
                $hoisted = $role['hoist'] ? '📌 ' : '';
                $mentionable = $role['mentionable'] ? '🔔 ' : '';

                $rolesList[] = sprintf(
                    '**%s%s%s** • %d members • %s',
                    $hoisted,
                    $mentionable,
                    $role['name'],
                    $memberCount,
                    $color
                );
            }

            $title = $index === 0 ? '📋 Server Roles' : '📋 Server Roles (continued)';
            $description = implode("\n", $rolesList);

            if ($index === 0) {
                $description = '**Total Roles:** ' . count($roles) . "\n\n" .
                              "📌 = Display separately (hoisted)\n" .
                              "🔔 = Mentionable by everyone\n\n" .
                              $description;
            }

            $this->getDiscord()->channel($this->channelId)->sendEmbed(
                $title,
                $description,
                3447003 // Blue
            );

            // Small delay between messages
            sleep(1);
        }
    }
}
