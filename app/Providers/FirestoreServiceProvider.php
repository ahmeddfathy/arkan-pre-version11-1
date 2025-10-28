<?php

namespace App\Providers;

use App\Services\FirestoreService;
use Illuminate\Support\ServiceProvider;

class FirestoreServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FirestoreService::class, function ($app) {
            return new FirestoreService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
