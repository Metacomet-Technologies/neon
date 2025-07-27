<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessChannelVisibilityBaseJob;

final class ProcessVanishChannelJob extends ProcessChannelVisibilityBaseJob
{
    protected function getPermissions(): array
    {
        return [
            'deny' => '1024', // VIEW_CHANNEL permission bit
            'allow' => '0',
        ];
    }

    protected function getActionName(): string
    {
        return 'vanished';
    }
}
