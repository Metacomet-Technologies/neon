<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\SendMessage;
use App\Services\CommandAnalyticsService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
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

    /**
     * Execute the job.
     */
    public function handle(CommandAnalyticsService $analytics): void
    {
        try {
            // Fetch all roles from the Discord server
            $rolesResponse = Http::withHeaders([
                'Authorization' => 'Bot ' . config('discord.token'),
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/guilds/{$this->guildId}/roles");

            if ($rolesResponse->failed()) {
                Log::error("Failed to fetch roles for guild {$this->guildId}", [
                    'status_code' => $rolesResponse->status(),
                    'response' => $rolesResponse->json(),
                    'guild_id' => $this->guildId,
                    'user_id' => $this->discordUserId,
                ]);

                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => '❌ Failed to fetch server roles. Please try again later.',
                ]);

                Log::error('Failed to fetch roles from Discord API', [
                    'status' => $rolesResponse->status(),
                    'guild_id' => $this->guildId,
                ]);

                return;
            }

            $roles = $rolesResponse->json();

            // Filter out @everyone role and sort by position (higher position = higher in hierarchy)
            $filteredRoles = array_filter($roles, function ($role) {
                return $role['name'] !== '@everyone';
            });

            // Sort by position (descending - highest roles first)
            usort($filteredRoles, function ($a, $b) {
                return $b['position'] <=> $a['position'];
            });

            if (empty($filteredRoles)) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => true,
                    'embed_title' => '📋 Server Roles',
                    'embed_description' => 'No custom roles found in this server.',
                    'embed_color' => 3447003, // Blue color
                ]);

                Log::info('Roles listed successfully (none found)', [
                    'guild_id' => $this->guildId,
                ]);

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
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => true,
                    'embed_title' => '📋 Server Roles',
                    'embed_description' => $description,
                    'embed_color' => 3447003, // Blue color
                ]);
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

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ An error occurred while fetching server roles. Please try again later.',
            ]);

            Log::error('Error in ProcessListRolesJob', [
                'error' => $e->getMessage(),
                'guild_id' => $this->guildId,
            ]);
            throw $e;
        }
    }

    /**
     * Get the number of members with a specific role
     */
    private function getRoleMemberCount(string $roleId): int
    {
        try {
            $membersResponse = Http::withHeaders([
                'Authorization' => 'Bot ' . config('discord.token'),
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/guilds/{$this->guildId}/members", [
                'limit' => 1000, // Discord's max limit
            ]);

            if ($membersResponse->failed()) {
                return 0;
            }

            $members = $membersResponse->json();
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

            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => $title,
                'embed_description' => $description,
                'embed_color' => 3447003, // Blue color
            ]);

            // Small delay between messages
            sleep(1);
        }
    }
}
