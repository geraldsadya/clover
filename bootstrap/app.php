<?php

/**
 * Application Bootstrap - CLOVER Application
 * 
 * Main application bootstrap file for the CLOVER CV Cover Letter Generator.
 * Configures routing, middleware, and exception handling.
 * 
 * Configuration:
 * - Web routes from routes/web.php
 * - Console commands from routes/console.php
 * - Health check endpoint at /up
 * - Global middleware: RequestId, SecurityHeaders
 * - API middleware: RateLimiting
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware - runs on every request
        $middleware->append(\App\Http\Middleware\RequestId::class);
        // $middleware->append(\App\Http\Middleware\SecurityHeaders::class); // Temporarily disabled for testing
        
        // API middleware group - for rate limiting on API routes
        $middleware->group('api', [
            \App\Http\Middleware\RateLimiting::class,
        ]);
        
        // Web middleware group - for rate limiting on web routes
        $middleware->group('web', [
            \App\Http\Middleware\RateLimiting::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
