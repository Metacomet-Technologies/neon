<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WelcomeSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

final class CacheWelcomeSettingsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $key = 'welcome-settings';

        $guilds = WelcomeSetting::query()
            ->select(['guild_id'])
            ->where('is_active', true)
            ->get()
            ->pluck('guild_id')
            ->toArray();

        Cache::forever($key, $guilds);
    }
}
