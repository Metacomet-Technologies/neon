<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string|null $description
 * @property string $class
 * @property string|null $help
 * @property string|null $example
 * @property string|null $sample
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereExample($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereHelp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereSample($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommand whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
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
}
