<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelEditBaseJob;

final class ProcessEditChannelSlowmodeJob extends ProcessChannelEditBaseJob
{
    protected function getCommandName(): string
    {
        return 'edit-channel-slowmode';
    }

    protected function getUpdateField(): string
    {
        return 'rate_limit_per_user';
    }

    protected function validateValue(string $value): int
    {
        // Validate numeric range (0-21600 seconds / 6 hours)
        return $this->validateNumericRange($value, 0, 21600, 'Slowmode');
    }

    protected function getConfirmationDetails(mixed $value): string
    {
        return "Slowmode: {$value} seconds";
    }
}
