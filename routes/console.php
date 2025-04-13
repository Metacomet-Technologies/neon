<?php

use App\Jobs\RefreshNeonGuildsJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('ping', function () {
    $this->info('Pong');
})->everyFiveMinutes()
    ->thenPing('http://beats.envoyer.io/heartbeat/fsRbxXpU82GSTaF');

Schedule::job(new RefreshNeonGuildsJob())->everyTwoMinutes();
