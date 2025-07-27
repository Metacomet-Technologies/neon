<?php

use App\Jobs\CheckAllGuildsBotMembership;
use App\Jobs\RefreshNeonGuildsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RefreshNeonGuildsJob)->everyTwoMinutes();
Schedule::command('discord:check-tokens')->everyFifteenMinutes();
Schedule::job(new CheckAllGuildsBotMembership)->hourly()->name('check-bot-membership')->withoutOverlapping();
