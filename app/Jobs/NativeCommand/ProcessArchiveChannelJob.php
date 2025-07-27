<?php

declare (strict_types = 1);

namespace App\Jobs\NativeCommand;

// Helpers replaced by SDK
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessArchiveChannelJob extends ProcessBaseJob
{
    private readonly ?string $targetChannelId;
    private readonly ?bool $archiveStatus;

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

        // Parse the message in constructor
        [$this->targetChannelId, $this->archiveStatus] = $this->parseMessage($messageContent);
    }

    protected function executeCommand(): void
    {
        // Validate input
        if (!$this->targetChannelId || is_null($this->archiveStatus)) {
            $this->sendUsageAndExample();

            return;
        }

        // Check permissions using SDK
        $discord = app(DiscordService::class);
        $member = $discord->guild($this->guildId)->member($this->discordUserId);

        if (!$member->canManageChannels()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to manage channels in this server.');
            throw new Exception('User does not have permission to manage channels.', 403);
        }

        // Validate channel ID format
        if (!preg_match('/^\d{17,19}$/', $this->targetChannelId)) {
            $discord->channel($this->channelId)->send('❌ Invalid channel ID. Please use `#channel-name` to select a valid channel.');
            throw new Exception('Invalid channel ID provided.', 400);
        }

        try {
            // Use SDK to archive/unarchive channel
            // Discord instance already created above
            $channel = $discord->channel($this->targetChannelId);

            if ($this->archiveStatus) {
                $channel->archive();
                $message = "✅ Channel <#{$this->targetChannelId}> has been **archived**.";
            } else {
                $channel->update(['archived' => false]);
                $message = "✅ Channel <#{$this->targetChannelId}> has been **unarchived**.";
            }

            $discord->channel($this->channelId)->sendEmbed(
                '📁 Channel Archive Status Updated',
                $message,
                3066993// Green color
            );

        } catch (Exception $e) {
            $discord->channel($this->channelId)->send('❌ Failed to update channel archive status.');
            throw new Exception('Failed to update channel archive status: ' . $e->getMessage(), 500);
        }
    }

    private function parseMessage(string $message): array
    {
        preg_match('/^!archive-channel\s*(<#(\d+)>|(\d+))?\s*(true|false)?$/i', $message, $matches);

        $channelId = null;
        $archiveStatus = null;

        if (isset($matches[2]) && $matches[2]) {
            $channelId = $matches[2];
        } elseif (isset($matches[3]) && $matches[3]) {
            $channelId = $matches[3];
        }

        if (isset($matches[4])) {
            $archiveStatus = strtolower($matches[4]) === 'true';
        }

        return [$channelId, $archiveStatus];
    }
}
