<?php

declare(strict_types=1);

namespace App\Nova\Dashboards;

use App\Nova\Metrics\ActiveGuilds;
use App\Nova\Metrics\CommandExecutions;
use App\Nova\Metrics\CommandSuccessRate;
use App\Nova\Metrics\ErrorsByCategory;
use App\Nova\Metrics\LicenseDistribution;
use App\Nova\Metrics\NewUsers;
use App\Nova\Metrics\PopularCommands;
use App\Nova\Metrics\RequestsPerDay;
use App\Nova\Metrics\TotalUsers;
use App\Nova\Metrics\UsersPerDay;
use Laravel\Nova\Dashboard;

final class Main extends Dashboard
{
    /**
     * Get the displayable name of the dashboard.
     */
    public function label(): string
    {
        return 'Dashboard';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            // User Analytics
            new TotalUsers,
            new NewUsers,
            new UsersPerDay,
            new ActiveGuilds,
            new LicenseDistribution,

            // Request Logs & Analytics
            new CommandExecutions,
            new CommandSuccessRate,
            new RequestsPerDay,
            new PopularCommands,
            new ErrorsByCategory,
        ];
    }

    /**
     * Get the URI key for the dashboard.
     */
    public function uriKey(): string
    {
        return 'main';
    }
}
