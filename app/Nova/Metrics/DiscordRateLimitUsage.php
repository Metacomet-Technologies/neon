<?php

declare(strict_types=1);

namespace App\Nova\Metrics;

use App\Services\DiscordApiService;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;

final class DiscordRateLimitUsage extends Value
{
    /**
     * Calculate the value of the metric.
     */
    public function calculate(NovaRequest $request): mixed
    {
        $service = app(DiscordApiService::class);
        $stats = $service->getRateLimitStats();

        return $this->result($stats['global_usage_percentage'])
            ->suffix('%')
            ->format('0.0');
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array<string, string>
     */
    public function ranges(): array
    {
        return [
            'TODAY' => 'Current',
        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     */
    public function cacheFor(): ?DateTimeInterface
    {
        return now()->addSeconds(5);
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'discord-rate-limit-usage';
    }

    /**
     * Get the displayable name of the metric.
     */
    public function name(): string
    {
        return 'Discord Global Rate Limit Usage';
    }

    /**
     * Get the help text for the metric.
     */
    public function help(): string
    {
        return 'Percentage of Discord global rate limit (50 req/sec) currently being used. Contact Discord support if consistently above 80%.';
    }
}
