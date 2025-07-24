<?php

namespace App\Providers;

use App\Observers\SubscriptionObserver;
use App\Rules\NuclearCode;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Subscription;

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
    }
}
