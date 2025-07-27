<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessNewChannelJob extends ProcessBaseJob
{
    private array $channelTypes = ['text', 'voice'];

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse new channel command with complex parameters
        [$channelName, $channelType, $categoryId, $channelTopic] = $this->parseNewChannelCommand($this->messageContent);

        if (! $channelName || ! $channelType) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        // 3. Validate channel name
        if (in_array($channelName, $this->channelTypes)) {
            $this->sendErrorMessage('Invalid channel name. Please use a different name.');
            throw new Exception('Invalid channel name provided.', 400);
        }

        $validationResult = DiscordService::validateChannelName($channelName);
        if (! $validationResult['valid']) {
            $this->sendErrorMessage($validationResult['message']);
            throw new Exception('Invalid channel name provided.', 400);
        }

        // 4. Validate channel type
        if (! in_array($channelType, $this->channelTypes)) {
            $this->sendErrorMessage('Invalid channel type. Please use "text" or "voice".');
            throw new Exception('Invalid channel type provided.', 400);
        }

        // 5. Construct payload and create channel
        $payload = [
            'name' => $channelName,
            'type' => $channelType === 'text' ? 0 : 2, // 0 = text channel, 2 = voice channel
        ];

        if ($categoryId) {
            $payload['parent_id'] = $categoryId;
        }
        if ($channelTopic) {
            $payload['topic'] = $channelTopic;
        }

        $createdChannel = $this->getDiscord()->createChannel($this->guildId, $payload);

        if (! $createdChannel) {
            $this->sendApiError('create channel');
            throw new Exception('Failed to create channel.', 500);
        }

        // 6. Send confirmation
        $details = [];
        $details[] = '**Type:** ' . ($channelType === 'text' ? '💬 Text' : '🔊 Voice');
        if ($categoryId) {
            $details[] = "**Category:** 📂 <#{$categoryId}>";
        }
        if ($channelTopic) {
            $details[] = "**Topic:** 📝 {$channelTopic}";
        }

        $this->sendSuccessMessage(
            'Channel Created!',
            "**Channel:** #{$channelName}\n" . implode("\n", $details)
        );
    }

    /**
     * Parse new channel command with complex parameters.
     */
    private function parseNewChannelCommand(string $messageContent): array
    {
        // Pattern: !new-channel <name> <type> [categoryId] ["topic"]
        preg_match('/^!new-channel\s+(\S+)\s+(\S+)(?:\s+(\d+))?(?:\s+(.+))?$/', $messageContent, $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null, null, null];
        }

        $channelName = $matches[1];
        $channelType = $matches[2];
        $categoryId = isset($matches[3]) ? $matches[3] : null;
        $channelTopic = isset($matches[4]) ? trim($matches[4], '"') : null;

        return [$channelName, $channelType, $categoryId, $channelTopic];
    }
}
