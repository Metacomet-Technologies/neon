<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_id
 * @property \Illuminate\Support\Carbon $event_timestamp
 * @property string $event_type
 * @property array<array-key, mixed> $event_data
 * @property bool $is_processed
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $errored_at
 * @property array<array-key, mixed>|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereErroredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereEventData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereEventTimestamp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereIsProcessed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TwitchEvent whereUpdatedAt($value)
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
