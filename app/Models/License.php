<?php

declare (strict_types = 1);

namespace App\Models;

use App\Events\LicenseAssigned;
use App\Events\LicenseTransferred;
use App\Exceptions\License\GuildAlreadyHasLicenseException;
use App\Exceptions\License\LicenseNotAssignedException;
use App\Exceptions\License\LicenseOnCooldownException;
use App\Policies\LicensePolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
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
 * @property \Illuminate\Support\Carbon|null $last_assigned_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Guild|null $guild
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License assignedToGuild(string $guildId)
 * @method static \Database\Factories\LicenseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License lifetime()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License parked()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License subscription()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License unassigned()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereAssignedGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereLastAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereStripeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|License whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[UsePolicy(LicensePolicy::class)]
final class License extends Model
{
    /** @use HasFactory<\Database\Factories\LicenseFactory> */
    use HasFactory;

    /**
     * The possible license types.
     */
    public const TYPE_SUBSCRIPTION = 'subscription';
    public const TYPE_LIFETIME     = 'lifetime';

    /**
     * The possible license statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PARKED = 'parked';

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
     * Get the user that owns the license.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guild that this license is assigned to.
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class, 'assigned_guild_id', 'id');
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
     * Check if the license is on cooldown (within 30 days of last assignment).
     */
    public function isOnCooldown(): bool
    {
        if ($this->last_assigned_at === null) {
            return false;
        }

        return $this->last_assigned_at->addDays(30)->isFuture();
    }

    /**
     * Get the number of days remaining in the cooldown period.
     */
    public function getCooldownDaysRemaining(): int
    {
        if (!$this->isOnCooldown()) {
            return 0;
        }

        return (int) now()->diffInDays($this->last_assigned_at->addDays(30), false);
    }

    /**
     * Assign the license to a guild.
     *
     * @throws LicenseOnCooldownException
     * @throws GuildAlreadyHasLicenseException
     */
    public function assignToGuild(Guild $guild): void
    {
        // Check if license is on cooldown
        if ($this->isOnCooldown()) {
            throw new LicenseOnCooldownException($this->getCooldownDaysRemaining());
        }

        // Check if guild already has an active license
        if ($guild->hasActiveLicense()) {
            throw new GuildAlreadyHasLicenseException($guild->id);
        }

        $this->update([
            'assigned_guild_id' => $guild->id,
            'status' => self::STATUS_ACTIVE,
            'last_assigned_at' => now(),
        ]);

        // Dispatch the LicenseAssigned event
        LicenseAssigned::dispatch($this, $guild);
    }

    /**
     * Park the license (remove from guild and set to parked status).
     */
    public function park(): void
    {
        $this->update([
            'assigned_guild_id' => null,
            'status' => self::STATUS_PARKED,
        ]);
    }

    /**
     * Transfer the license to a different guild.
     *
     * @throws LicenseOnCooldownException
     * @throws GuildAlreadyHasLicenseException
     * @throws LicenseNotAssignedException
     */
    public function transferToGuild(Guild $guild): void
    {
        // Check if license is currently assigned
        if (!$this->isAssigned()) {
            throw new LicenseNotAssignedException;
        }

        // Check if license is on cooldown
        if ($this->isOnCooldown()) {
            throw new LicenseOnCooldownException($this->getCooldownDaysRemaining());
        }

        // Check if target guild already has an active license
        if ($guild->hasActiveLicense()) {
            throw new GuildAlreadyHasLicenseException($guild->id);
        }

        // Store the original guild for the event
        $fromGuild = $this->guild;

        // Park the license first, then assign to new guild
        $this->park();
        $this->assignToGuild($guild);

        // Dispatch the LicenseTransferred event
        LicenseTransferred::dispatch($this, $fromGuild, $guild);
    }

    /**
     * Unassign the license from any guild (alias for park method for backward compatibility).
     */
    public function unassign(): void
    {
        $this->park();
    }
}
