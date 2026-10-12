<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Domain\RoutingProvider::class, \App\Domain\DevelopmentRouting::class); $this->app->bind(\App\Domain\PaymentProvider::class, \App\Domain\DevelopmentPayment::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
