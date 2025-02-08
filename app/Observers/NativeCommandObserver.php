<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\NativeCommand;

final class NativeCommandObserver
{
    /**
     * Handle the NativeCommand "created" event.
     */
    public function creating(NativeCommand $nativeCommand): void
    {
        $now = now();
        $nativeCommand->created_at = $now;
        $nativeCommand->updated_at = $now;

    }

    /**
     * Handle the NativeCommand "updated" event.
     */
    public function updating(NativeCommand $nativeCommand): void
    {
        $nativeCommand->updated_at = now();
    }
}
