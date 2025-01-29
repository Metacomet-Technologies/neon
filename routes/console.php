<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('ping', function () {
    $this->info('Pong');
})->everyFiveMinutes()
    ->thenPing('http://beats.envoyer.io/heartbeat/fsRbxXpU82GSTaF');
