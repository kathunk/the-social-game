<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add a global error handler for model not found exceptions (route model binding)
        $this->app->bind(ModelNotFoundException::class, function () {
            Log::debug('ModelNotFoundException caught by global handler');
            return redirect()->route('dashboard');
        });
    }
}
