<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\TimeStampObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserIntegration query()
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
