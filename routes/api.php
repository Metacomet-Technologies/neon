<?php

use App\Http\Controllers\Api\Twitch\EventSubWebhookController;
use App\Http\Controllers\Api\UpdateUserCurrentServerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\WebhookController;

Route::post('twitch/eventsub/webhook', EventSubWebhookController::class)->name('twitch.eventsub.webhook');

// Stripe webhook route
Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('stripe.webhook');

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('user', function (Request $request) {
        return json_encode($request->user());
    });
    Route::patch('user/{user}/current-server', UpdateUserCurrentServerController::class)->name('user.current-server');
});
