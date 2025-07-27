<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\NeonCommandObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $command
 * @property string|null $description
 * @property string|null $response
 * @property string $guild_id
 * @property bool $is_enabled
 * @property bool $is_public
 * @property bool $is_embed
 * @property string|null $embed_title
 * @property string|null $embed_description
 * @property int|null $embed_color
 * @property int $created_by
 * @property int $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $createdByUser
 * @property-read \App\Models\Guild $guild
 * @property-read \App\Models\User $updatedByUser
 *
 * @method static Builder<static>|NeonCommand aciveGuildCommands(string $guildId)
 * @method static Builder<static>|NeonCommand newModelQuery()
 * @method static Builder<static>|NeonCommand newQuery()
 * @method static Builder<static>|NeonCommand query()
 * @method static Builder<static>|NeonCommand whereCommand($value)
 * @method static Builder<static>|NeonCommand whereCreatedAt($value)
 * @method static Builder<static>|NeonCommand whereCreatedBy($value)
 * @method static Builder<static>|NeonCommand whereDescription($value)
 * @method static Builder<static>|NeonCommand whereEmbedColor($value)
 * @method static Builder<static>|NeonCommand whereEmbedDescription($value)
 * @method static Builder<static>|NeonCommand whereEmbedTitle($value)
 * @method static Builder<static>|NeonCommand whereGuildId($value)
 * @method static Builder<static>|NeonCommand whereId($value)
 * @method static Builder<static>|NeonCommand whereIsEmbed($value)
 * @method static Builder<static>|NeonCommand whereIsEnabled($value)
 * @method static Builder<static>|NeonCommand whereIsPublic($value)
 * @method static Builder<static>|NeonCommand whereResponse($value)
 * @method static Builder<static>|NeonCommand whereUpdatedAt($value)
 * @method static Builder<static>|NeonCommand whereUpdatedBy($value)
 *
 * @mixin \Eloquent
 */
#[ObservedBy(NeonCommandObserver::class)]
final class NeonCommand extends Model
{
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
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the guild that owns this command.
     */
    public function guild(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Guild::class, 'guild_id', 'id');
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

    /**
     * Record usage analytics for this Neon command.
     */
    public function recordUsage(
        string $discordUserId,
        string $messageContent = '',
        ?string $channelType = null,
        ?int $executionTimeMs = null,
        string $status = 'success',
        ?string $errorCategory = null
    ): CommandUsageMetric {
        // Tokenize the message content to extract parameters safely
        $parameters = $this->extractParameters($messageContent);

        return CommandUsageMetric::recordUsage(
            commandType: 'neon',
            commandSlug: $this->command,
            guildId: $this->guild_id,
            discordUserId: $discordUserId,
            parameters: $parameters,
            channelType: $channelType,
            executionTimeMs: $executionTimeMs,
            status: $status,
            errorCategory: $errorCategory
        );
    }

    /**
     * Extract parameters from message content for analytics.
     * This tokenizes the content without storing sensitive information.
     */
    private function extractParameters(string $messageContent): array
    {
        // Remove the command name from the message
        $commandPrefix = '!' . $this->command;
        $parametersString = trim(str_replace($commandPrefix, '', $messageContent));

        if (empty($parametersString)) {
            return [];
        }

        // Split by spaces, but respect quoted strings
        $parameters = [];
        $inQuotes = false;
        $currentParam = '';
        $chars = str_split($parametersString);

        foreach ($chars as $char) {
            if ($char === '"' && ! $inQuotes) {
                $inQuotes = true;
            } elseif ($char === '"' && $inQuotes) {
                $inQuotes = false;
                if (! empty($currentParam)) {
                    $parameters[] = $currentParam;
                    $currentParam = '';
                }
            } elseif ($char === ' ' && ! $inQuotes) {
                if (! empty($currentParam)) {
                    $parameters[] = $currentParam;
                    $currentParam = '';
                }
            } else {
                $currentParam .= $char;
            }
        }

        if (! empty($currentParam)) {
            $parameters[] = $currentParam;
        }

        return $parameters;
    }
}
