<?php

namespace App\Models;

use App\Observers\NativeCommandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NativeCommandParameter> $parameters
 * @property-read int|null $parameters_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(NativeCommandObserver::class)]
class NativeCommand extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parameters for the NativeCommand
     */
    public function parameters(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NativeCommandParameter::class);
    }
}
