<?php

/**
 * Application Entry Point - CLOVER Application
 * 
 * Main entry point for the CLOVER CV Cover Letter Generator.
 * Handles request routing and application bootstrapping.
 * 
 * Features:
 * - Maintenance mode detection
 * - Composer autoloader registration
 * - Laravel application bootstrapping
 * - Request handling and routing
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
