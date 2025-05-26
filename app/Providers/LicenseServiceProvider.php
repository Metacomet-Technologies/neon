<?php
namespace App\Providers;

use App\Listeners\CreateLicenseFromSubscription;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class LicenseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(
            WebhookReceived::class,
            CreateLicenseFromSubscription::class
        );
    }
}
