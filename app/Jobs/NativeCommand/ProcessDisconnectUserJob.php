<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Support\Facades\Log;

final class ProcessDisconnectUserJob extends ProcessBaseJob
{
    private array $targetUserIds = [];

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

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

    // TODO: May want to add logic to have channel id instead of user, which would disonnect all users in that channel.
    protected function executeCommand(): void
    {
        // Parse the message
        $this->targetUserIds = $this->parseMessage($this->messageContent);

        // 🚨 **Moved validation here to ensure job does not execute unnecessarily**
        if (empty($this->targetUserIds)) {
            $this->sendUsageAndExample();

            throw new Exception('No user ID provided.', 400);
        }
        // Check if user has permission to manage channels
        $discord = app(DiscordService::class);
        if (! $discord->guild($this->guildId)->member($this->discordUserId)->canManageChannels()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to disconnect users from voice channels in this server.');
            throw new Exception('User does not have permission to manage channels.', 403);
        }
        $failedUsers = [];

        // Disconnect each user from their current voice channel
        $discordService = app(DiscordService::class);
        foreach ($this->targetUserIds as $userId) {
            $response = retry($this->maxRetries, function () use ($discordService, $userId) {
                return $discordService->patch("/guilds/{$this->guildId}/members/{$userId}", ['channel_id' => null]);
            }, $this->retryDelay);

            if ($response->failed()) {
                Log::error("Failed to disconnect user {$userId} from voice channel.");
                $failedUsers[] = "<@{$userId}>";
            }
        }
        // Send response message
        if (! empty($failedUsers)) {
            $discord->channel($this->channelId)->sendEmbed(
                '❌ Disconnect Failed',
                'Failed to remove: ' . implode(', ', $failedUsers),
                15158332 // Red
            );
            throw new Exception('Operation failed', 500);
        } else {
            $discord->channel($this->channelId)->sendEmbed(
                '✅ Users Disconnected from Voice Channel',
                'Successfully disconnected users from voice chat.',
                3066993 // Green
            );
        }
    }

    private function parseMessage(string $message): array
    {
        preg_match_all('/<@!?(\d{17,19})>/', $message, $matches);

        return $matches[1] ?? [];
    }
}
