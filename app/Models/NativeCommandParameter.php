<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\NativeCommand|null $command
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter query()
 *
 * @mixin \Eloquent
 */
class NativeCommandParameter extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $guarded = [];

    /**
     * Get the command that owns the NativeCommandParameter
     */
    public function command(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(NativeCommand::class);
    }
}
