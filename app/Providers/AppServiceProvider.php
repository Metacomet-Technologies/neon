<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Services\Discord\DiscordClient;
use App\Services\Discord\DiscordService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitch\Provider as TwitchProvider;

/**
 * Class AppServiceProvider
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Discord services as singletons
        $this->app->singleton(DiscordClient::class);
        $this->app->singleton(DiscordService::class, function ($app) {
            return new DiscordService($app->make(DiscordClient::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable mass assignment protection in all environments
        Model::unguard();

        // Development settings for better coding experience
        if ($this->app->environment('local')) {
            // Enable query logging for debugging
            DB::enableQueryLog();

            // Prevent lazy loading to catch N+1 queries
            Model::preventLazyLoading();
            Model::preventAccessingMissingAttributes();
            Model::preventSilentlyDiscardingAttributes();

            // Enable model events for all models
            Model::shouldBeStrict();
        }

        // Configure Cashier to use the User model
        Cashier::useCustomerModel(User::class);

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('discord', DiscordProvider::class);
            $event->extendSocialite('twitch', TwitchProvider::class);
        });

        Gate::before(function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        Gate::define('viewPulse', function (User $user) {
            return $user->isAdmin();
        });
    }
}
