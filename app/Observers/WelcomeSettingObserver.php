<?php

namespace App\Observers;

use App\Jobs\CacheWelcomeSettingsJob;
use App\Models\WelcomeSetting;

class WelcomeSettingObserver
{
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
