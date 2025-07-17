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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
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
}
