<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelEditBaseJob;

final class ProcessEditChannelNSFWJob extends ProcessChannelEditBaseJob
{
    protected function getCommandName(): string
    {
        return 'edit-channel-nsfw';
    }

    protected function getUpdateField(): string
    {
        return 'nsfw';
    }

    protected function validateValue(string $value): bool
    {
        // Validate boolean input
        return $this->validateBoolean($value, 'NSFW setting');
    }

    protected function getConfirmationDetails(mixed $value): string
    {
        $statusText = $value ? 'Enabled' : 'Disabled';

        return "NSFW: {$statusText}";
    }
}
