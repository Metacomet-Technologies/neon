<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\DiscordParserService;
use Exception;

final class ProcessVanishChannelJob extends ProcessBaseJob
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
        $this->requireChannelPermission();

        $channelId = $this->parseChannelMention($this->messageContent);
        if (! $channelId) {
            $this->sendUsageAndExample();
            throw new Exception('Channel not specified.', 400);
        }

        $this->validateChannelId($channelId);

        // Custom logic for permission overrides using everyone role
        $everyoneRole = $this->discord->getEveryoneRole($this->guildId);
        $permissions = [
            'type' => 0, // Role permission
            'id' => $everyoneRole['id'],
            'deny' => '1024', // VIEW_CHANNEL permission bit
            'allow' => '0',
        ];

        $success = $this->discord->updateChannelPermissions($channelId, $everyoneRole['id'], $permissions);

        if (! $success) {
            $this->sendApiError('update channel permissions');
            throw new Exception('Failed to update channel permissions.', 500);
        }

        $this->sendChannelActionConfirmation('vanished', $channelId);
    }

    private function parseChannelMention(string $content): ?string
    {
        $parts = explode(' ', trim($content));
        $mentionedChannel = $parts[1] ?? null;

        if (! $mentionedChannel) {
            return null;
        }

        return DiscordParserService::extractChannelId($mentionedChannel);
    }
}
