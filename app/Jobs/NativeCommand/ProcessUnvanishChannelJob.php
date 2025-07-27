<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelVisibilityBaseJob;

final class ProcessUnvanishChannelJob extends ProcessChannelVisibilityBaseJob
{
    protected function getPermissions(): array
    {
        return [
            'deny' => '0', // Remove VIEW_CHANNEL restriction
            'allow' => '1024', // Allow VIEW_CHANNEL
        ];
    }

    protected function getActionName(): string
    {
        return 'unvanished';
    }
}
