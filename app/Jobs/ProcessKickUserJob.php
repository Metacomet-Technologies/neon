<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessKickUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !kick <@user>';
    public string $exampleMessage = 'Example: !kick @User1';

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
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message
        $this->targetUserId = $this->parseMessage($this->messageContent);

        // If parsing fails, send a help message
        if (! $this->targetUserId) {
            Log::error('Kick User Job Failed: Invalid input. Raw message: ' . $this->messageContent);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }
    }

    // TODO: Check if the user is the owner and send owner access token for elevated permissions. this whole file is fubar.
    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Check if the user has permission to kick members
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanKickMembers($this->guildId, $this->discordUserId, \App\Enums\DiscordPermissionEnum::KICK_MEMBERS);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to kick users in this server.',
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
}
