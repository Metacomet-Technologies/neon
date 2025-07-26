<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $icon
 * @property bool $is_bot_member
 * @property \Illuminate\Support\Carbon|null $bot_joined_at
 * @property \Illuminate\Support\Carbon|null $bot_left_at
 * @property \Illuminate\Support\Carbon|null $last_bot_check_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\License> $activeLicenses
 * @property-read int|null $active_licenses_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\License> $licenses
 * @property-read int|null $licenses_count
 *
 * @method static \Database\Factories\GuildFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereIsBotMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereBotJoinedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereBotLeftAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild whereLastBotCheckAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild withBotMember()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Guild withoutBotMember()
 *
 * @mixin \Eloquent
 */
final class Guild extends Model
{
    /** @use HasFactory<\Database\Factories\GuildFactory> */
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_bot_member' => 'boolean',
        'bot_joined_at' => 'datetime',
        'bot_left_at' => 'datetime',
        'last_bot_check_at' => 'datetime',
    ];

    /**
     * Get the licenses assigned to this guild.
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'assigned_guild_id', 'id');
    }

    /**
     * Get active licenses for this guild.
     */
    public function activeLicenses(): HasMany
    {
        return $this->licenses()->where('status', License::STATUS_ACTIVE);
    }

    /**
     * Check if the guild has any active licenses.
     */
    public function hasActiveLicense(): bool
    {
        return $this->activeLicenses()->exists();
    }

    /**
     * Get the Neon commands for this guild.
     */
    public function neonCommands(): HasMany
    {
        return $this->hasMany(NeonCommand::class, 'guild_id', 'id');
    }

    /**
     * Get the welcome settings for this guild.
     */
    public function welcomeSettings(): HasMany
    {
        return $this->hasMany(WelcomeSetting::class, 'guild_id', 'id');
    }

    /**
     * Scope a query to only include guilds where the bot is a member.
     */
    public function scopeWithBotMember($query)
    {
        return $query->where('is_bot_member', true);
    }

    /**
     * Scope a query to only include guilds where the bot is not a member.
     */
    public function scopeWithoutBotMember($query)
    {
        return $query->where('is_bot_member', false);
    }

    /**
     * Check if the bot needs membership check (hasn't been checked in 24 hours).
     */
    public function needsBotMembershipCheck(): bool
    {
        if ($this->last_bot_check_at === null) {
            return true;
        }

        return $this->last_bot_check_at->lt(now()->subHours(24));
    }

    /**
     * Get the Discord CDN URL for the guild icon.
     */
    public function getIconUrl(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        return "https://cdn.discordapp.com/icons/{$this->id}/{$this->icon}.png";
    }
}
