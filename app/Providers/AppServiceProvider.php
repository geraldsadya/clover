<?php

/**
 * AppServiceProvider
 * 
 * Main service provider for the CLOVER application.
 * Handles application-wide service registration and bootstrapping.
 * 
 * Key Features:
 * - CSP nonce generation for security headers
 * - Service container configuration
 * - Application-wide service registration
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

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
