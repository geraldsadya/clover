<?php

/**
 * Console Routes - CLOVER Application
 * 
 * Defines Artisan console commands for the CLOVER application.
 * Currently minimal but can be extended with custom commands.
 * 
 * Commands:
 * - inspire : Display an inspiring quote (Laravel default)
 * 
 * Future commands could include:
 * - CV validation testing
 * - Performance monitoring
 * - Database maintenance
 * - Cache management
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Default Laravel inspire command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
