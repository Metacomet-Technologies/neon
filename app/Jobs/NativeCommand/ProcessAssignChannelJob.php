<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessAssignChannelJob extends ProcessBaseJob
{
    private readonly ?string $targetChannelInput;
    private readonly ?string $targetCategoryInput;

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

        // Parse assignment parameters in constructor
        [$this->targetChannelInput, $this->targetCategoryInput] = $this->parseAssignCommand($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Validate input
        if (! $this->targetChannelInput || ! $this->targetCategoryInput) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        // 3. Find the channel and category
        $channels = $this->getDiscord()->getGuildChannels($this->guildId);

        $channel = $this->findChannelByInput($channels, $this->targetChannelInput);
        if (! $channel) {
            $this->sendErrorMessage("Channel '{$this->targetChannelInput}' not found.");
            throw new Exception('Channel not found.', 404);
        }

        $category = $this->findCategoryByInput($channels, $this->targetCategoryInput);
        if (! $category) {
            $this->sendErrorMessage("Category '{$this->targetCategoryInput}' not found.");
            throw new Exception('Category not found.', 404);
        }

        // 4. Move the channel to the category
        $success = $this->getDiscord()->updateChannel($channel['id'], ['parent_id' => $category['id']]);

        if (! $success) {
            $this->sendApiError('move channel to category');
            throw new Exception('Failed to move channel.', 500);
        }

        // 5. Send confirmation
        $this->sendSuccessMessage(
            'Channel Moved!',
            "**Channel:** <#{$channel['id']}> (#{$channel['name']})\n" .
            "**New Category:** 📂 {$category['name']}",
            3066993 // Green
        );
    }

    private function parseAssignCommand(string $message): array
    {
        // Remove invisible characters and normalize spaces
        $cleanedMessage = preg_replace('/[\p{Cf}]/u', '', $message);
        $cleanedMessage = trim(preg_replace('/\s+/', ' ', $cleanedMessage));

        // Extract channel and category
        preg_match('/^!assign-channel\s+(<#?(\d{17,19})>|[\w-]+)\s+([\w-]+)$/iu', $cleanedMessage, $matches);

        if (! isset($matches[1], $matches[3])) {
            return [null, null];
        }

        $channelInput = $matches[2] ?? $matches[1]; // Extract ID from channel mention if present
        $categoryInput = $matches[3];

        return [$channelInput, $categoryInput];
    }

    private function findChannelByInput($channels, string $input): ?array
    {
        return $channels->first(fn ($c) => $c['id'] === $input || strcasecmp($c['name'], $input) === 0);
    }

    private function findCategoryByInput($channels, string $input): ?array
    {
        return $channels->first(fn ($c) => ($c['id'] === $input || strcasecmp($c['name'], $input) === 0) && $c['type'] === 4
        );
    }
}
