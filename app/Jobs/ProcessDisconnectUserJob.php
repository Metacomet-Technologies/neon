<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ProcessDisconnectUserJob implements ShouldQueue
{
    use Queueable;

    /**
     * User-friendly instruction messages.
     */
    public string $usageMessage = 'Usage: !disconnect <@user1> [@user2] ...';
    public string $exampleMessage = 'Example: !disconnect @User1 @User2';

    public string $baseUrl;
    private array $targetUserIds = [];

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
        $this->targetUserIds = $this->parseMessage($this->messageContent);

        // If parsing fails, send help message
        if (empty($this->targetUserIds)) {
            Log::error("Disconnect User Job Failed: Invalid input. Raw message: " . $this->messageContent);
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);

            return;
        }
    }

    /**
     * Handles the job execution.
     */
    public function handle(): void
    {
        // Check if user has permission to manage channels
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to disconnect users from voice channels in this server.',
            ]);

            return;
        }

        $failedUsers = [];

        // Disconnect each user from their current voice channel
        foreach ($this->targetUserIds as $userId) {
            $kickUrl = "{$this->baseUrl}/guilds/{$this->guildId}/members/{$userId}";

            $response = retry($this->maxRetries, function () use ($kickUrl) {
                return Http::withToken(config('discord.token'), 'Bot')->patch($kickUrl, ['channel_id' => null]);
            }, $this->retryDelay);

            if ($response->failed()) {
                Log::error("Failed to disconnect user {$userId} from voice channel.");
                $failedUsers[] = "<@{$userId}>";
            }
        }

        // Send response message
        if (!empty($failedUsers)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '❌ Disconnect Failed',
                'embed_description' => 'Failed to remove: ' . implode(', ', $failedUsers),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '✅ Users Disconnected from Voice Channel',
                'embed_description' => 'Successfully disconnected users from voice chat.',
                'embed_color' => 3066993, // Green
            ]);
        }
    }

    /**
     * Parses the message content for user mentions.
     */
    private function parseMessage(string $message): array
    {
        preg_match_all('/<@!?(\d{17,19})>/', $message, $matches);

        return $matches[1] ?? [];
    }
}
