<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelEditBaseJob;
use Exception;

final class ProcessEditChannelAutohideJob extends ProcessChannelEditBaseJob
{
    private array $allowedDurations = [60, 1440, 4320, 10080];

    protected function getCommandName(): string
    {
        return 'edit-channel-autohide';
    }

    protected function getUpdateField(): string
    {
        return 'default_auto_archive_duration';
    }

    protected function validateValue(string $value): int
    {
        $duration = (int) $value;

        if (! in_array($duration, $this->allowedDurations)) {
            $this->sendUsageAndExample('Allowed values: `60, 1440, 4320, 10080` minutes.');
            throw new Exception('Invalid auto-hide duration.', 400);
        }

        return $duration;
    }

    protected function getConfirmationDetails(mixed $value): string
    {
        return "⏲️ Auto-hide Duration: `{$value} minutes`";
    }
}
