<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\NeonCommandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\User|null $createdByUser
 * @property-read \App\Models\User|null $updatedByUser
 *
 * @method static Builder<static>|NeonCommand aciveGuildCommands(string $guildId)
 * @method static Builder<static>|NeonCommand newModelQuery()
 * @method static Builder<static>|NeonCommand newQuery()
 * @method static Builder<static>|NeonCommand query()
 *
 * @mixin \Eloquent
 */
#[ObservedBy(NeonCommandObserver::class)]
final class NeonCommand extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_public' => 'boolean',
        'is_embed' => 'boolean',
    ];

    /**
     * Get the user that created the command.
     */
    public function createdByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that updated the command.
     */
    public function updatedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include active guild commands.
     */
    public function scopeAciveGuildCommands(Builder $query, string $guildId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereGuildId($guildId)
            ->whereIsEnabled(true)
            ->select([
                'id',
                'command',
                'response',
                'is_embed',
                'embed_title',
                'embed_description',
                'embed_color',
            ]);
    }
}
