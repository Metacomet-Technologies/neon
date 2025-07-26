<?php

declare(strict_types=1);

namespace App\Nova\Cards;

use App\Services\DiscordApiService;
use Laravel\Nova\Card;

final class DiscordServiceStatus extends Card
{
    /**
     * The width of the card (1/3, 1/2, or full).
     */
    public $width = '1/3';

    /**
     * Get the component name for the element.
     */
    public function component(): string
    {
        return 'card';
    }

    /**
     * Prepare the element for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $service = app(DiscordApiService::class);
        $stats = $service->getRateLimitStats();

        return array_merge(parent::jsonSerialize(), [
            'data' => [
                'title' => 'Discord API Status',
                'stats' => $stats,
                'status' => $this->getStatusColor($stats),
            ],
        ]);
    }

    /**
     * Get the status color based on usage.
     */
    private function getStatusColor(array $stats): string
    {
        if ($stats['circuit_breaker_state'] !== 'closed') {
            return 'red';
        }

        if ($stats['global_usage_percentage'] > 90 || $stats['invalid_requests_percentage'] > 90) {
            return 'red';
        }

        if ($stats['global_usage_percentage'] > 80 || $stats['invalid_requests_percentage'] > 80) {
            return 'yellow';
        }

        return 'green';
    }
}
