<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discord Bot Token
    |--------------------------------------------------------------------------
    |
    | This value is the token for your Discord bot. You can find this token
    | in the Discord Developer Portal. Make sure to keep this token secret.
    |
    */

    'token' => env('DISCORD_BOT_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Discord Bot Prefix
    |--------------------------------------------------------------------------
    |
    | This value is the prefix for your Discord bot commands. You can change
    | this to whatever you want, but make sure it doesn't conflict with other
    | bots or commands in your server.
    |
    */

    'prefix' => env('DISCORD_BOT_PREFIX', '!'),
];
