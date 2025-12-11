<?php

namespace App\Providers;

use App\Notifications\Channels\DiscordChannel;
use App\Notifications\Channels\SmsChannel;
use App\Observers\SubscriptionObserver;
use App\Rules\NuclearCode;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Subscription;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Discord\Provider as DiscordProvider;
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

        // Register custom notification channels
        $this->app->make(ChannelManager::class)->extend('discord', function () {
            return new DiscordChannel();
        });

        $this->app->make(ChannelManager::class)->extend('sms', function () {
            return new SmsChannel();
        });

        // Configure social providers
        Event::listen(SocialiteWasCalled::class, function (
            SocialiteWasCalled $event,
        ) {
            $event->extendSocialite('discord', DiscordProvider::class);
            $event->extendSocialite('apple', AppleProvider::class);
        });
    }
}
