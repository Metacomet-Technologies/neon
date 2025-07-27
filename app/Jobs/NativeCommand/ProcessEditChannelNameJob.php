<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelEditBaseJob;
use App\Services\Discord\DiscordService;
use Exception;

final class ProcessEditChannelNameJob extends ProcessChannelEditBaseJob
{
    protected function getCommandName(): string
    {
        return 'edit-channel-name';
    }

    protected function getUpdateField(): string
    {
        return 'name';
    }

    protected function validateValue(string $value): string
    {
        // Validate channel name using Discord service
        $validationResult = DiscordService::validateChannelName($value);

        if (! $validationResult['valid']) {
            $this->sendErrorMessage($validationResult['message']);
            throw new Exception('Invalid channel name provided.', 400);
        }

        return $value;
    }

    protected function getConfirmationDetails(mixed $value): string
    {
        return "New name: #{$value}";
    }
}
