<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::private('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
