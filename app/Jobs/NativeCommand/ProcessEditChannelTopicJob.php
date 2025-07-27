<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelEditBaseJob;
use Exception;

final class ProcessEditChannelTopicJob extends ProcessChannelEditBaseJob
{
    protected function getCommandName(): string
    {
        return 'edit-channel-topic';
    }

    protected function getUpdateField(): string
    {
        return 'topic';
    }

    protected function validateValue(string $value): string
    {
        // Topic can be up to 1024 characters
        if (strlen($value) > 1024) {
            $this->sendErrorMessage('Topic too long (max 1024 characters)');
            throw new Exception('Topic too long.', 400);
        }

        return $value;
    }

    protected function getConfirmationDetails(mixed $value): string
    {
        return "New topic: {$value}";
    }
}
