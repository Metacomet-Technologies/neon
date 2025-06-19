<?php

use App\Jobs\RefreshNeonGuildsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new RefreshNeonGuildsJob)->everyTwoMinutes();
