<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;

final class ProcessSetInactiveJob extends ProcessBaseJob
{
    /**
     * Allowed AFK timeout values (in seconds).
     */
    private array $allowedTimeouts = [60, 300, 900, 1800, 3600];

    protected function executeCommand(): void
    {
        $this->requireChannelPermission();

        $params = Discord::extractParameters($this->messageContent, 'set-inactive');
        $this->validateRequiredParameters($params, 2, 'Channel and timeout are required.');

        $channelInput = $params[0];
        $timeout = $this->validateNumericRange($params[1], 60, 3600, 'Timeout');

        // Validate timeout is in allowed values
        if (! in_array($timeout, $this->allowedTimeouts)) {
            $allowedList = implode(', ', $this->allowedTimeouts);
            $this->sendErrorMessage("Timeout must be one of: {$allowedList} seconds.");
            throw new Exception('Invalid timeout value.', 400);
        }

        $channelId = $this->resolveVoiceChannelId($channelInput);
        $this->validateChannelId($channelId);

        $success = $this->discord->setGuildAfkChannel($this->guildId, $channelId, $timeout);

        if (! $success) {
            $this->sendApiError('set inactive voice channel');
            throw new Exception('Failed to set inactive channel.', 500);
        }

        $this->sendSuccessMessage(
            '🎧 Inactive Channel Set',
            "**AFK Channel:** <#{$channelId}>\n**Timeout:** ⏳ `{$timeout} sec`"
        );
    }

    /**
     * Resolves a voice channel name or ID to a valid channel ID.
     */
    private function resolveVoiceChannelId(string $input): string
    {
        // If input is already a valid channel ID, return it
        if (Discord::isValidDiscordId($input)) {
            return $input;
        }

        // Try to find channel by name
        $channels = $this->discord->getGuildChannels($this->guildId);

        foreach ($channels as $channel) {
            if ($channel['type'] === 2 && strcasecmp($channel['name'], $input) === 0) {
                return $channel['id'];
            }
        }

        $this->sendErrorMessage("Voice channel '{$input}' not found.");
        throw new Exception('Voice channel not found.', 404);
    }
}
