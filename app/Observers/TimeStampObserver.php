<?php

declare(strict_types=1);

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

final class TimeStampObserver
{
    /**
     * Handle the NativeCommand "creating" event.
     */
    public function creating(Model $model): void
    {
        $now = now();
        $model->created_at = $now;
        $model->updated_at = $now;

    }

    /**
     * Handle the NativeCommand "updating" event.
     */
    public function updating(Model $model): void
    {
        $model->updated_at = now();
    }
}
