<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $stripe_id
 * @property string $status
 * @property string|null $assigned_guild_id
 * @property Carbon|null $last_assigned_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 *
 * @method static \Database\Factories\LicenseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License parked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License subscription()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License lifetime()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License assignedToGuild(string $guildId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License unassigned()
 */
final class License extends Model
{
    /** @use HasFactory<\Database\Factories\LicenseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'stripe_id',
        'status',
        'assigned_guild_id',
        'last_assigned_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The possible license types.
     */
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_LIFETIME = 'lifetime';

    /**
     * The possible license statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PARKED = 'parked';

    /**
     * Get the user that owns the license.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active licenses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include parked licenses.
     */
    public function scopeParked($query)
    {
        return $query->where('status', self::STATUS_PARKED);
    }

    /**
     * Scope a query to only include subscription licenses.
     */
    public function scopeSubscription($query)
    {
        return $query->where('type', self::TYPE_SUBSCRIPTION);
    }

    /**
     * Scope a query to only include lifetime licenses.
     */
    public function scopeLifetime($query)
    {
        return $query->where('type', self::TYPE_LIFETIME);
    }

    /**
     * Scope a query to only include licenses assigned to a specific guild.
     */
    public function scopeAssignedToGuild($query, string $guildId)
    {
        return $query->where('assigned_guild_id', $guildId);
    }

    /**
     * Scope a query to only include unassigned licenses.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_guild_id');
    }

    /**
     * Check if the license is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the license is parked.
     */
    public function isParked(): bool
    {
        return $this->status === self::STATUS_PARKED;
    }

    /**
     * Check if the license is a subscription.
     */
    public function isSubscription(): bool
    {
        return $this->type === self::TYPE_SUBSCRIPTION;
    }

    /**
     * Check if the license is lifetime.
     */
    public function isLifetime(): bool
    {
        return $this->type === self::TYPE_LIFETIME;
    }

    /**
     * Check if the license is assigned to a guild.
     */
    public function isAssigned(): bool
    {
        return $this->assigned_guild_id !== null;
    }

    /**
     * Assign the license to a guild.
     */
    public function assignToGuild(string $guildId): void
    {
        $this->update([
            'assigned_guild_id' => $guildId,
            'status' => self::STATUS_ACTIVE,
            'last_assigned_at' => now(),
        ]);
    }

    /**
     * Unassign the license from any guild.
     */
    public function unassign(): void
    {
        $this->update([
            'assigned_guild_id' => null,
            'status' => self::STATUS_PARKED,
        ]);
    }
}
