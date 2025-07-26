<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\WelcomeSettingObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
final class WelcomeSetting extends Model
{
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the guild that owns this welcome setting.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'guild_id', 'id');
    }
}
