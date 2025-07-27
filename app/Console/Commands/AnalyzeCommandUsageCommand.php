<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CommandAnalyticsService;
use Illuminate\Console\Command;

final class AnalyzeCommandUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neon:analyze-commands
                            {--days=30 : Number of days to analyze}
                            {--type= : Command type (native, neon, or all)}
                            {--guild= : Specific guild ID to analyze}
                            {--command= : Specific command to analyze}
                            {--deprecation : Show deprecation candidates}
                            {--popular : Show popular commands}
                            {--errors : Show error analysis}
                            {--patterns : Show parameter patterns}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze command usage patterns for feature research and deprecation planning (includes both Native and Neon commands)';

    public function __construct(private CommandAnalyticsService $analytics)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $type = $this->option('type');
        $guildId = $this->option('guild');

        if ($this->option('deprecation')) {
            $this->showDeprecationCandidates($days);

            return 0;
        }

        if ($this->option('popular')) {
            $this->showPopularCommands($days);

            return 0;
        }

        $this->showUsageStatistics($type, $guildId, $days);

        return 0;
    }

    private function showUsageStatistics(?string $type, ?string $guildId, int $days): void
    {
        $startDate = now()->subDays($days);
        $endDate = now();

        $stats = $this->analytics->getUsageStatistics($type, $guildId, $startDate, $endDate);

        $this->info("Command Usage Statistics ({$days} days)");
        $this->line('');

        if ($stats->isEmpty()) {
            $this->warn('No usage data found for the specified criteria.');

            return;
        }

        $headers = [
            'Command',
            'Type',
            'Total Uses',
            'Unique Guilds',
            'Unique Users',
            'Avg Parameters',
            'Avg Duration (ms)',
            'Success Rate (%)',
        ];

        $rows = $stats->map(function ($stat) {
            return [
                $stat->command_slug,
                $stat->command_type,
                number_format($stat->total_uses),
                number_format($stat->unique_guilds),
                number_format($stat->unique_users),
                number_format($stat->avg_parameters, 1),
                $stat->avg_duration_ms ? number_format($stat->avg_duration_ms, 0) : 'N/A',
                number_format($stat->success_rate, 1) . '%',
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    private function showDeprecationCandidates(int $days): void
    {
        $candidates = $this->analytics->getDeprecationCandidates($days);

        $this->info("Commands with Low Usage (Last {$days} days)");
        $this->line('These commands might be candidates for deprecation:');
        $this->line('');

        if ($candidates->isEmpty()) {
            $this->info('No low-usage commands found. All commands are actively used!');

            return;
        }

        $headers = [
            'Command',
            'Type',
            'Total Uses',
            'Unique Guilds',
            'Last Used',
            'Success Rate (%)',
        ];

        $rows = $candidates->map(function ($candidate) {
            return [
                $candidate->command_slug,
                $candidate->command_type,
                number_format($candidate->total_uses),
                number_format($candidate->unique_guilds),
                $candidate->last_used,
                number_format($candidate->success_rate * 100, 1) . '%',
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    private function showPopularCommands(int $days): void
    {
        $popular = $this->analytics->getPopularCommands($days);

        $this->info("Most Popular Commands (Last {$days} days)");
        $this->line('These commands should be prioritized for improvements:');
        $this->line('');

        if ($popular->isEmpty()) {
            $this->warn('No usage data found.');

            return;
        }

        $headers = [
            'Rank',
            'Command',
            'Type',
            'Total Uses',
            'Unique Guilds',
            'Unique Users',
            'Success Rate (%)',
        ];

        $rows = $popular->map(function ($command, $index) {
            return [
                $index + 1,
                $command->command_slug,
                $command->command_type,
                number_format($command->total_uses),
                number_format($command->unique_guilds),
                number_format($command->unique_users),
                number_format($command->success_rate * 100, 1) . '%',
            ];
        })->toArray();

        $this->table($headers, $rows);
    }
}
