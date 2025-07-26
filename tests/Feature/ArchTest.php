<?php

arch()->preset()->php();
arch()->preset()->security();
arch()->preset()->laravel()
    ->ignoring([
        'App\Http\Controllers\Api\StripeWebhookController',
    ]);
// arch()->preset()->strict();
