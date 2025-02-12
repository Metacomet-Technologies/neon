<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 *
 * @property int $id
 * @property int $native_command_id
 * @property string $name
 * @property string|null $description
 * @property int $is_required
 * @property int $order
 * @property string $data_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\NativeCommand|null $command
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereDataType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereNativeCommandId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandParameter whereUpdatedAt($value)
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
