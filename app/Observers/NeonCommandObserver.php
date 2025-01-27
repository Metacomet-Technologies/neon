<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CacheGuildCommandsJob;
use App\Models\NeonCommand;

final class NeonCommandObserver
{
    /**
     * Handle the NeonCommand "created" event.
     */
    public function created(NeonCommand $neonCommand): void
    {
        // Cache the guild commands
        CacheGuildCommandsJob::dispatch($neonCommand->guild_id);
    }

    /**
     * Handle the NeonCommand "updated" event.
     */
    public function updated(NeonCommand $neonCommand): void
    {
        // Cache the guild commands
        CacheGuildCommandsJob::dispatch($neonCommand->guild_id);
    }

    /**
     * Handle the NeonCommand "deleted" event.
     */
    public function deleted(NeonCommand $neonCommand): void
    {
        // Cache the guild commands
        CacheGuildCommandsJob::dispatch($neonCommand->guild_id);
    }

    /**
     * Handle the NeonCommand "restored" event.
     */
    public function restored(NeonCommand $neonCommand): void
    {
        // Cache the guild commands
        CacheGuildCommandsJob::dispatch($neonCommand->guild_id);
    }

    /**
     * Handle the NeonCommand "force deleted" event.
     */
    public function forceDeleted(NeonCommand $neonCommand): void
    {
        // Cache the guild commands
        CacheGuildCommandsJob::dispatch($neonCommand->guild_id);
    }
}
