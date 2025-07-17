<?php

namespace App\Models;

use App\Observers\NativeCommandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $description
 * @property string $class
 * @property string|null $usage
 * @property string|null $example
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\NativeCommandParameter> $parameters
 * @property-read int|null $parameters_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereExample($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereUsage($value)
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
