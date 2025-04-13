<?php

namespace App\Jobs;

use App\Helpers\Discord\GetBotGuilds;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RefreshNeonGuildsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'neon:guilds';
        $ttl = 300;

        $guilds = new GetBotGuilds;

        Cache::put($key, $guilds->getGuildIds(), $ttl);
    }
}
