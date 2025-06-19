<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent query()
 *
 * @mixin \Eloquent
 */
class TwitchEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_timestamp' => 'datetime',
        'event_data' => 'array',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'errored_at' => 'datetime',
        'error_message' => 'array',
    ];
}
