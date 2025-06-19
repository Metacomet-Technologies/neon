<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest query()
 *
 * @mixin \Eloquent
 */
final class NativeCommandRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'command' => 'array',
        'additional_parameters' => 'array',
        'error_message' => 'array',
        'executed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
