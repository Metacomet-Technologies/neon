<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessKickUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage;
    public string $exampleMessage;

    // 'slug' => 'kick',
    // 'description' => 'Kicks a user from the server.',
    // 'class' => \App\Jobs\ProcessKickUserJob::class,
    // 'usage' => 'Usage: !kick <user-id>',
    // 'example' => 'Example: !kick 123456789012345678',
    // 'is_active' => true,

    public string $baseUrl;

    private ?string $targetUserId = null;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        // Fetch command details from the database
        $command = DB::table('native_commands')->where('slug', 'kick')->first();

        // Set usage and example messages dynamically
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;

        $this->baseUrl = config('services.discord.rest_api_url');

        // Normalize curly quotes to straight quotes for better parsing
        $normalizedMessage = str_replace(['“', '”'], '"', $this->messageContent);

        // Parse the message
        $this->targetUserId = $this->parseMessage($normalizedMessage);
    }
    // TODO: Check if the user is the owner and send owner access token for elevated permissions. this whole file is fubar.

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Validate input: If no user was provided, return the help message
        if (! $this->targetUserId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }

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

            return;
        }

        // Ensure sender's role is higher than target user's role
        if (! $this->canKickUser($this->discordUserId, $this->targetUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You cannot kick this user. Their role is equal to or higher than yours.',
            ]);

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

            return;
        }

        // Success message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '✅ User Kicked',
            'embed_description' => "Successfully removed <@{$this->targetUserId}> from the server.",
            'embed_color' => 3066993, // Green
        ]);
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
