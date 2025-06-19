<?php

namespace App\Models;

use App\Observers\WelcomeSettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(WelcomeSettingObserver::class)]
class WelcomeSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
