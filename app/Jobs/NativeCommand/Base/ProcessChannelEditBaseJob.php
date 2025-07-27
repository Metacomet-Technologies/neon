<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand\Base;

use App\Services\Discord\DiscordService;
use Exception;

abstract class ProcessChannelEditBaseJob extends ProcessBaseJob
{
    protected ?string $targetChannelId = null;
    protected ?string $newValue = null;

    /**
     * Get the command name for parsing.
     */
    abstract protected function getCommandName(): string;

    /**
     * Get the Discord API field name to update.
     */
    abstract protected function getUpdateField(): string;

    /**
     * Validate and transform the input value.
     */
    abstract protected function validateValue(string $value): mixed;

    /**
     * Get the confirmation message details.
     */
    abstract protected function getConfirmationDetails(mixed $value): string;

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse channel edit command
        [$this->targetChannelId, $this->newValue] = DiscordService::parseChannelEditCommand(
            $this->messageContent,
            $this->getCommandName()
        );

        if (! $this->targetChannelId || ! $this->newValue) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($this->targetChannelId);

        // 3. Validate the value using the child class implementation
        $validatedValue = $this->validateValue($this->newValue);

        // 4. Perform update using service
        $success = $this->getDiscord()->updateChannel($this->targetChannelId, [
            $this->getUpdateField() => $validatedValue,
        ]);

        if (! $success) {
            $this->sendApiError('update channel');
            throw new Exception('Failed to update channel.', 500);
        }

        // 5. Send confirmation
        $this->sendChannelActionConfirmation(
            'updated',
            $this->targetChannelId,
            $this->getConfirmationDetails($validatedValue)
        );
    }
}
