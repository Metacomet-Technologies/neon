<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NativeCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class CacheNativeCommandsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'native-commands';

        $commands = NativeCommand::all()->toArray();

        Cache::forever($key, $commands);
    }
}
