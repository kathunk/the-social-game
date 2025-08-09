<?php

namespace App\Providers;

use App\Observers\SubscriptionObserver;
use App\Rules\NuclearCode;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Subscription;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        NuclearCode::register();
        Subscription::observe(SubscriptionObserver::class);

        // Configure social providers
        \Event::listen(SocialiteWasCalled::class, function (
            SocialiteWasCalled $event,
        ) {
            $event->extendSocialite("discord", DiscordProvider::class);
            $event->extendSocialite("apple", AppleProvider::class);
        });
    }
}
