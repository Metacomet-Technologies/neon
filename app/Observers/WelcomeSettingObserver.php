<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CacheWelcomeSettingsJob;
use App\Models\WelcomeSetting;

final class WelcomeSettingObserver
{
    /**
     * Handle the WelcomeSetting "created" event.
     */
    public function creating(WelcomeSetting $welcomeSetting): void
    {
        $now = now();
        $welcomeSetting->created_at = $now;
        $welcomeSetting->updated_at = $now;
    }

    /**
     * Handle the WelcomeSetting "created" event.
     */
    public function created(WelcomeSetting $welcomeSetting): void
    {
        CacheWelcomeSettingsJob::dispatch();
    }

    /**
     * Handle the WelcomeSetting "updated" event.
     */
    public function updating(WelcomeSetting $welcomeSetting): void
    {
        $welcomeSetting->updated_at = now();
    }

    /**
     * Handle the WelcomeSetting "updated" event.
     */
    public function updated(WelcomeSetting $welcomeSetting): void
    {
        CacheWelcomeSettingsJob::dispatch();
    }

    /**
     * Handle the WelcomeSetting "deleted" event.
     */
    public function deleted(WelcomeSetting $welcomeSetting): void
    {
        CacheWelcomeSettingsJob::dispatch();
    }

    /**
     * Handle the WelcomeSetting "restored" event.
     */
    public function restored(WelcomeSetting $welcomeSetting): void
    {
        CacheWelcomeSettingsJob::dispatch();
    }

    /**
     * Handle the WelcomeSetting "force deleted" event.
     */
    public function forceDeleted(WelcomeSetting $welcomeSetting): void
    {
        CacheWelcomeSettingsJob::dispatch();
    }
}
