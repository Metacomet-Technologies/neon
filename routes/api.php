<?php

use App\Http\Controllers\Api\Billing\BillingPortalController;
use App\Http\Controllers\Api\Billing\CancelSubscriptionController;
use App\Http\Controllers\Api\Billing\CheckoutLifetimeController;
use App\Http\Controllers\Api\Billing\CheckoutSubscriptionController;
use App\Http\Controllers\Api\Billing\GetBillingInfoController;
use App\Http\Controllers\Api\Billing\ResumeSubscriptionController;
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

    // Billing routes
    Route::prefix('checkout')->group(function () {
        Route::post('subscription', CheckoutSubscriptionController::class)->name('checkout.subscription');
        Route::post('lifetime', CheckoutLifetimeController::class)->name('checkout.lifetime');
    });

    Route::prefix('billing')->group(function () {
        Route::get('portal', BillingPortalController::class)->name('billing.portal');
        Route::get('info', GetBillingInfoController::class)->name('billing.info');
        Route::post('subscription/cancel', CancelSubscriptionController::class)->name('billing.subscription.cancel');
        Route::post('subscription/resume', ResumeSubscriptionController::class)->name('billing.subscription.resume');
    });
});
