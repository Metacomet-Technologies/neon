<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessKickUserJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?string $targetUserId = null;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    // TODO: Check if the user is the owner and send owner access token for elevated permissions. this whole file is fubar.

    public function handle(): void
    {
        // Normalize curly quotes to straight quotes for better parsing
        $normalizedMessage = str_replace(['“', '”'], '"', $this->messageContent);

        // Parse the message
        $this->targetUserId = $this->parseMessage($normalizedMessage);

        // Check if the user has permission to kick members
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanKickMembers(
            $this->guildId,
            $this->discordUserId
        );

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to kick users in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to kick',
                statusCode: 403,
            );

            return;
        }

        // Validate input: If no user was provided, return the help message
        if (! $this->targetUserId) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No user ID provided.',
                statusCode: 400,
            );

            return;
        }

        // Ensure sender's role is higher than target user's role
        if (! $this->canKickUser($this->discordUserId, $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You cannot kick this user. Their role is equal to or higher than yours.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User cannot kick target user. Peer or higher role required.',
                statusCode: 403,
            );

            return;
        }

        // Kick the user from the guild
        $kickUrl = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$this->targetUserId}";

        $response = retry($this->maxRetries, function () use ($kickUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->delete($kickUrl);
        }, $this->retryDelay);

        if ($response->failed()) {
            Log::error("Failed to kick user {$this->targetUserId} from the guild.");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Kick Failed',
                'embed_description' => "Failed to remove <@{$this->targetUserId}> from the server.",
                'embed_color' => 15158332, // Red
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to rename channel.',
                statusCode: $response->status(),
                details: $response->json(),
            );

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ User Kicked',
            'embed_description' => "Successfully removed <@{$this->targetUserId}> from the server.",
            'embed_color' => 3066993, // Green
        ]);
        $this->updateNativeCommandRequestComplete();
    }

    /**
     * Parses the message content for the mentioned user ID.
     */
    private function parseMessage(string $message): ?string
    {
        preg_match('/<@!?(\d{17,19})>/', $message, $matches);

        return $matches[1] ?? null;
    }

    /**
     * ✅ Ensure the sender has a higher role than the target user.
     */
    private function canKickUser(string $senderId, string $targetId): bool
    {
        $senderRole = $this->getUserHighestRole($senderId);
        $targetRole = $this->getUserHighestRole($targetId);

        return $senderRole > $targetRole;
    }

    /**
     * ✅ Fetch the highest role position for a user.
     */
    private function getUserHighestRole(string $userId): int
    {
        $url = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return 0;
        }

        $userData = $response->json();
        $roles = $userData['roles'] ?? [];

        if (empty($roles)) {
            return 0;
        }

        $rolesUrl = "{$this->baseUrl}/guilds/{$this->guildId}/roles";
        $rolesResponse = Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);

        if ($rolesResponse->failed()) {
            return 0;
        }

        $allRoles = collect($rolesResponse->json());
        $userRoles = $allRoles->whereIn('id', $roles);

        return $userRoles->max('position') ?? 0;
    }
}
