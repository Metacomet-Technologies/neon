<?php

namespace App\Jobs;

use App\Models\NativeCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class CacheNativeCommandsJob implements ShouldQueue
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
