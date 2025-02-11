<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CacheNativeCommandsJob;
use App\Models\NativeCommand;

final class NativeCommandObserver
{
    /**
     * Handle the NativeCommand "creating" event.
     */
    public function creating(NativeCommand $nativeCommand): void
    {
        $now = now();
        $nativeCommand->created_at = $now;
        $nativeCommand->updated_at = $now;

    }

    /**
     * Handle the NativeCommand "updating" event.
     */
    public function updating(NativeCommand $nativeCommand): void
    {
        $nativeCommand->updated_at = now();
    }

    /**
     * Handle the NativeCommand "created" event.
     */
    public function created(NativeCommand $nativeCommand): void
    {
        // Cache the guild commands
        CacheNativeCommandsJob::dispatch();
    }

    /**
     * Handle the NativeCommand "updated" event.
     */
    public function updated(NativeCommand $nativeCommand): void
    {
        // Cache the guild commands
        CacheNativeCommandsJob::dispatch();
    }

    /**
     * Handle the NativeCommand "deleted" event.
     */
    public function deleted(NativeCommand $nativeCommand): void
    {
        // Cache the guild commands
        CacheNativeCommandsJob::dispatch();
    }

    /**
     * Handle the NativeCommand "restored" event.
     */
    public function restored(NativeCommand $nativeCommand): void
    {
        // Cache the guild commands
        CacheNativeCommandsJob::dispatch();
    }

    /**
     * Handle the NativeCommand "force deleted" event.
     */
    public function forceDeleted(NativeCommand $nativeCommand): void
    {
        // Cache the guild commands
        CacheNativeCommandsJob::dispatch();
    }
}
