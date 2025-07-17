<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\TimeStampObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 * @property array<array-key, mixed> $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereProviderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy(TimeStampObserver::class)]
final class UserIntegration extends Model
{
    protected $guarded = [];

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
