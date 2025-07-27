<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessGuildCommandJob extends ProcessBaseJob
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
        // Check if command contains embed data
        if (isset($this->command['is_embed']) && $this->command['is_embed']) {
            $success = $this->getDiscord()->channel($this->channelId)->sendEmbed(
                $this->command['embed_title'] ?? '',
                $this->command['embed_description'] ?? '',
                $this->command['embed_color'] ?? 0
            );
        } else {
            $success = $this->getDiscord()->channel($this->channelId)->send(
                $this->command['response'] ?? $this->messageContent
            );
        }

        if (! $success) {
            throw new Exception('Failed to send message to Discord', 500);
        }
    }
}
