<?php

namespace App\Models;

use App\Observers\WelcomeSettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $guild_id
 * @property string $channel_id
 * @property string|null $message
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WelcomeSetting whereUpdatedAt($value)
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
