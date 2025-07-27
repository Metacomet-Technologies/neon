<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/**
 * Command usage metrics for analytics and feature research.
 *
 * @property int $id
 * @property string $command_type
 * @property string $command_slug
 * @property string $command_hash
 * @property string $guild_id
 * @property string $user_hash
 * @property string|null $channel_type
 * @property array<array-key, mixed>|null $parameter_signature
 * @property int $parameter_count
 * @property bool $had_errors
 * @property string|null $execution_duration_ms
 * @property \Illuminate\Support\Carbon $executed_at
 * @property \Illuminate\Support\Carbon $date
 * @property int $hour
 * @property int $day_of_week
 * @property string $status
 * @property string|null $error_category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric commandType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric dateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric failed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric successful()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereChannelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereCommandHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereCommandSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereCommandType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereDayOfWeek($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereErrorCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereExecutedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereExecutionDurationMs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereGuildId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereHadErrors($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereHour($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereParameterCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereParameterSignature($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommandUsageMetric whereUserHash($value)
 *
 * @mixin \Eloquent
 */
final class CommandUsageMetric extends Model
{
    protected $casts = [
        'parameter_signature' => 'array',
        'had_errors' => 'boolean',
        'executed_at' => 'datetime',
        'date' => 'date',
    ];

    /**
     * Record a command usage metric.
     */
    public static function recordUsage(
        string $commandType,
        string $commandSlug,
        string $guildId,
        string $discordUserId,
        array $parameters = [],
        ?string $channelType = null,
        ?int $executionTimeMs = null,
        string $status = 'success',
        ?string $errorCategory = null
    ): self {
        $executedAt = now();

        return self::create([
            'command_type' => $commandType,
            'command_slug' => $commandSlug,
            'command_hash' => self::generateCommandHash($commandSlug, $parameters),
            'guild_id' => $guildId,
            'user_hash' => self::hashUserId($discordUserId),
            'channel_type' => $channelType,
            'parameter_signature' => self::tokenizeParameters($parameters),
            'parameter_count' => count($parameters),
            'had_errors' => $status !== 'success',
            'execution_duration_ms' => $executionTimeMs ? (string) $executionTimeMs : null,
            'executed_at' => $executedAt,
            'date' => $executedAt->toDateString(),
            'hour' => (int) $executedAt->format('H'),
            'day_of_week' => (int) $executedAt->format('w'),
            'status' => $status,
            'error_category' => $errorCategory,
        ]);
    }

    /**
     * Generate a hash for command + parameter pattern for pattern analysis.
     */
    private static function generateCommandHash(string $commandSlug, array $parameters): string
    {
        $pattern = $commandSlug . ':' . implode(',', array_map('gettype', $parameters));

        return hash('sha256', $pattern);
    }

    /**
     * Hash Discord user ID for privacy while maintaining analytics.
     */
    private static function hashUserId(string $discordUserId): string
    {
        return hash('sha256', $discordUserId . config('app.key'));
    }

    /**
     * Tokenize parameters for pattern analysis without storing sensitive data.
     */
    private static function tokenizeParameters(array $parameters): array
    {
        return array_map(function ($param) {
            if (is_string($param)) {
                // Tokenize common patterns
                if (preg_match('/^\d{17,19}$/', $param)) {
                    return 'discord_id';
                }
                if (preg_match('/^<@&?\d{17,19}>$/', $param)) {
                    return 'mention';
                }
                if (preg_match('/^#[\w-]+$/', $param)) {
                    return 'channel_reference';
                }
                if (filter_var($param, FILTER_VALIDATE_URL)) {
                    return 'url';
                }
                if (is_numeric($param)) {
                    return 'number';
                }

                return 'text:' . strlen($param); // Text with length
            }

            return gettype($param);
        }, $parameters);
    }

    /**
     * Scope for date range queries.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for command type.
     */
    public function scopeCommandType($query, string $type)
    {
        return $query->where('command_type', $type);
    }

    /**
     * Scope for successful commands only.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed commands only.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '!=', 'success');
    }
}
