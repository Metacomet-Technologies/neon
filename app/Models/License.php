<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $guild_id
 * @property string|null $previous_guild_id
 * @property string|null $last_assigned_guild_id
 * @property string|null $last_assigned_at
 * @property string $stripe_id
 * @property string|null $plan_id
 * @property string $stripe_status
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property string|null $last_moved_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereEndsAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereLastAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereLastAssignedGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereLastMovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License wherePreviousGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereStripeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereUserId($value)
 *
 * @mixin \Eloquent
 */
class License extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'assigned_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_moved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->stripe_status === 'active' && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function isAssigned(): bool
    {
        return ! is_null($this->guild_id);
    }

    public function hasExpired(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function wasMovedRecently(): bool
    {
        return $this->last_moved_at && now()->diffInDays($this->last_moved_at) < 30;
    }

    public function canBeReassignedTo(string $targetGuildId): bool
    {
        if ($this->last_assigned_guild_id === $targetGuildId) {
            return true; // Reassignment to same guild is always allowed
        }

        return is_null($this->last_moved_at)
            || now()->diffInDays($this->last_moved_at) >= 30;
    }
}
