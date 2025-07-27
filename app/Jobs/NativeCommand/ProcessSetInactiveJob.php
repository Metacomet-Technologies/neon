<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
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

        // Extract parameters from message
        $paramString = trim(str_replace('!set-inactive', '', $this->messageContent));

        if (empty($paramString)) {
            $this->sendUsageAndExample();
            throw new Exception('Channel and timeout are required.', 400);
        }

        // Parse parameters - format: channelName/ID timeout
        $params = explode(' ', $paramString);
        if (count($params) < 2) {
            $this->sendUsageAndExample();
            throw new Exception('Channel and timeout are required.', 400);
        }

        $channelInput = $params[0];
        $timeoutInput = $params[1];

        // Validate timeout is numeric and in range
        if (! is_numeric($timeoutInput)) {
            $this->sendErrorMessage('Timeout must be a number.');
            throw new Exception('Invalid timeout value.', 400);
        }

        $timeout = (int) $timeoutInput;
        if ($timeout < 60 || $timeout > 3600) {
            $this->sendErrorMessage('Timeout must be between 60 and 3600 seconds.');
            throw new Exception('Timeout out of range.', 400);
        }

        // Validate timeout is in allowed values
        if (! in_array($timeout, $this->allowedTimeouts)) {
            $allowedList = implode(', ', $this->allowedTimeouts);
            $this->sendErrorMessage("Timeout must be one of: {$allowedList} seconds.");
            throw new Exception('Invalid timeout value.', 400);
        }

        $channelId = $this->resolveVoiceChannelId($channelInput);

        $success = $this->getDiscord()->setGuildAfkChannel($this->guildId, $channelId, $timeout);

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
        // If input is already a valid channel ID (snowflake format), return it
        if (preg_match('/^\d{17,19}$/', $input)) {
            return $input;
        }

        // Try to find channel by name
        $channels = $this->getDiscord()->getGuildChannels($this->guildId);

        foreach ($channels as $channel) {
            if ($channel['type'] === 2 && strcasecmp($channel['name'], $input) === 0) {
                return $channel['id'];
            }
        }

        $this->sendErrorMessage("Voice channel '{$input}' not found.");
        throw new Exception('Voice channel not found.', 404);
    }
}
