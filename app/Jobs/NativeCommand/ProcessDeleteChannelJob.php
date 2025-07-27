<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\DiscordService;
use Exception;

final class ProcessDeleteChannelJob extends ProcessBaseJob
{
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
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse channel ID from command
        $targetChannelId = $this->parseDeleteChannelCommand($this->messageContent);

        if (! $targetChannelId) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($targetChannelId);

        // 3. Delete channel using service
        $discordApiService = app(DiscordService::class);
        $success = $discordApiService->deleteChannel($targetChannelId);

        if (! $success) {
            $this->sendApiError('delete channel');
            throw new Exception('Failed to delete channel.', 500);
        }

        // 4. Send confirmation
        $this->sendSuccessMessage(
            'Channel Deleted!',
            "🗑️ Channel (ID: `{$targetChannelId}`) has been successfully removed.",
            15158332 // Red color
        );
    }

    /**
     * Parse delete channel command to extract channel ID.
     */
    private function parseDeleteChannelCommand(string $messageContent): ?string
    {
        // Remove invisible characters and normalize spaces
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $messageContent);
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage));

        // Pattern: !delete-channel <#channelID> or !delete-channel channelID
        preg_match('/^!delete-channel\s+(<#(\d{17,19})>|\d{17,19})$/i', $cleanedMessage, $matches);

        if (! isset($matches[1])) {
            return null;
        }

        // Extract channel ID using parser service
        return DiscordService::extractChannelId($matches[1]);
    }
}
