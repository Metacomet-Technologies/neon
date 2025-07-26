<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Discord\Discord;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class RefreshNeonGuildsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'neon:guilds';
        $ttl = 300;

        $discord = new Discord;
        $guilds = $discord->bot()->guilds();
        $guildIds = $guilds->pluck('id')->toArray();

        Cache::put($key, $guildIds, $ttl);
    }
}
