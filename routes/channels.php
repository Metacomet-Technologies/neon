<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::private('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::private('neon.private.{botId}', function ($user, $botId) {
    return $botId === config('discord.token');
});
