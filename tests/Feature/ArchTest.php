<?php

arch()->preset()->php();
arch()->preset()->security();

// Custom Laravel controller rules with webhook exception
arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toHavePublicMethodsBesides([
        '__construct',
        '__invoke',
        'index',
        'show',
        'create',
        'store',
        'edit',
        'update',
        'destroy',
        'middleware',
    ])
    ->ignoring([
        'App\Http\Controllers\Api\StripeWebhookController', // Extends CashierWebhookController with custom handlers
    ]);

// arch()->preset()->strict();
