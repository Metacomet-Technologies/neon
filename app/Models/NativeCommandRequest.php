<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $guild_id
 * @property string $channel_id
 * @property string $discord_user_id
 * @property string $message_content
 * @property array<array-key, mixed> $command
 * @property array<array-key, mixed>|null $additional_parameters
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $executed_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property array<array-key, mixed>|null $error_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereAdditionalParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereChannelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereDiscordUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereExecutedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereMessageContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NativeCommandRequest whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
final class NativeCommandRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'command' => 'array',
        'additional_parameters' => 'array',
        'error_message' => 'array',
        'executed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
