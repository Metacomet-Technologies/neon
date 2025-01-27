<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $command
 * @property string|null $description
 * @property string|null $response
 * @property string|null $guild_id
 * @property bool $is_enabled
 * @property bool $is_public
 * @property int $created_by
 * @property int $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $createdByUser
 * @property-read \App\Models\User $updatedByUser
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereCommand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereIsEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereIsPublic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NeonCommand whereUpdatedBy($value)
 *
 * @mixin \Eloquent
 */
final class NeonCommand extends Model
{
    protected $fillable = [
        'command',
        'description',
        'response',
        'guild_id',
        'is_enabled',
        'is_public',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function createdByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
