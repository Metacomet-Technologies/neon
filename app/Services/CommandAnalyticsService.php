<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommandUsageMetric;
use DateTimeInterface;
use Illuminate\Support\Collection;

/**
 * Service for tracking and analyzing command usage patterns.
 */
final class CommandAnalyticsService
{
    /**
     * Record usage of a native command.
     */
    public function recordNativeCommandUsage(
        string $commandSlug,
        string $guildId,
        string $discordUserId,
        array $parameters = [],
        ?string $channelType = null,
        ?int $executionTimeMs = null,
        string $status = 'success',
        ?string $errorCategory = null
    ): CommandUsageMetric {
        return CommandUsageMetric::recordUsage(
            commandType: 'native',
            commandSlug: $commandSlug,
            guildId: $guildId,
            discordUserId: $discordUserId,
            parameters: $parameters,
            channelType: $channelType,
            executionTimeMs: $executionTimeMs,
            status: $status,
            errorCategory: $errorCategory
        );
    }

    /**
     * Record usage of a custom neon command.
     */
    public function recordNeonCommandUsage(
        string $commandSlug,
        string $guildId,
        string $discordUserId,
        array $parameters = [],
        ?string $channelType = null,
        ?int $executionTimeMs = null,
        string $status = 'success',
        ?string $errorCategory = null
    ): CommandUsageMetric {
        return CommandUsageMetric::recordUsage(
            commandType: 'neon',
            commandSlug: $commandSlug,
            guildId: $guildId,
            discordUserId: $discordUserId,
            parameters: $parameters,
            channelType: $channelType,
            executionTimeMs: $executionTimeMs,
            status: $status,
            errorCategory: $errorCategory
        );
    }

    /**
     * Get command usage statistics for research and feature planning.
     */
    public function getUsageStatistics(
        ?string $commandType = null,
        ?string $guildId = null,
        ?DateTimeInterface $startDate = null,
        ?DateTimeInterface $endDate = null
    ): Collection {
        $query = CommandUsageMetric::query();

        if ($commandType) {
            $query->commandType($commandType);
        }

        if ($guildId) {
            $query->where('guild_id', $guildId);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        }

        return $query->selectRaw('
            command_slug,
            command_type,
            COUNT(*) as total_uses,
            COUNT(DISTINCT guild_id) as unique_guilds,
            COUNT(DISTINCT user_hash) as unique_users,
            AVG(parameter_count) as avg_parameters,
            AVG(CAST(execution_duration_ms AS UNSIGNED)) as avg_duration_ms,
            COUNT(CASE WHEN status = "success" THEN 1 END) as successful_uses,
            COUNT(CASE WHEN status != "success" THEN 1 END) as failed_uses,
            (COUNT(CASE WHEN status = "success" THEN 1 END) / COUNT(*) * 100) as success_rate
        ')
            ->groupBy('command_slug', 'command_type')
            ->orderByDesc('total_uses')
            ->get();
    }

    /**
     * Get usage patterns by time for feature deprecation research.
     */
    public function getTimeBasedUsagePatterns(string $commandSlug, int $days = 30): Collection
    {
        return CommandUsageMetric::where('command_slug', $commandSlug)
            ->where('date', '>=', now()->subDays($days))
            ->selectRaw('
                date,
                COUNT(*) as daily_uses,
                COUNT(DISTINCT guild_id) as unique_guilds,
                AVG(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_rate
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get error patterns for specific commands.
     */
    public function getErrorPatterns(string $commandSlug): Collection
    {
        return CommandUsageMetric::where('command_slug', $commandSlug)
            ->where('status', '!=', 'success')
            ->selectRaw('
                error_category,
                parameter_signature,
                COUNT(*) as error_count,
                COUNT(DISTINCT guild_id) as affected_guilds
            ')
            ->groupBy('error_category', 'parameter_signature')
            ->orderByDesc('error_count')
            ->get();
    }

    /**
     * Get parameter usage patterns for feature research.
     */
    public function getParameterPatterns(string $commandSlug): Collection
    {
        return CommandUsageMetric::where('command_slug', $commandSlug)
            ->selectRaw('
                parameter_signature,
                parameter_count,
                COUNT(*) as usage_count,
                AVG(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_rate
            ')
            ->groupBy('parameter_signature', 'parameter_count')
            ->orderByDesc('usage_count')
            ->get();
    }

    /**
     * Get commands that might be candidates for deprecation.
     */
    public function getDeprecationCandidates(int $days = 90, int $minUsageThreshold = 10): Collection
    {
        return CommandUsageMetric::where('date', '>=', now()->subDays($days))
            ->selectRaw('
                command_slug,
                command_type,
                COUNT(*) as total_uses,
                COUNT(DISTINCT guild_id) as unique_guilds,
                MAX(date) as last_used,
                AVG(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_rate
            ')
            ->groupBy('command_slug', 'command_type')
            ->having('total_uses', '<', $minUsageThreshold)
            ->orderBy('total_uses')
            ->get();
    }

    /**
     * Get popular commands for feature prioritization.
     */
    public function getPopularCommands(int $days = 30): Collection
    {
        return CommandUsageMetric::where('date', '>=', now()->subDays($days))
            ->selectRaw('
                command_slug,
                command_type,
                COUNT(*) as total_uses,
                COUNT(DISTINCT guild_id) as unique_guilds,
                COUNT(DISTINCT user_hash) as unique_users,
                AVG(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_rate
            ')
            ->groupBy('command_slug', 'command_type')
            ->orderByDesc('total_uses')
            ->limit(20)
            ->get();
    }
}
