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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register CSP nonce for inline scripts
        $this->app->singleton('csp_nonce', function () {
            return base64_encode(random_bytes(16));
        });
    }
}
