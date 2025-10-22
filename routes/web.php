<?php

/**
 * Web Routes - CLOVER Application
 * 
 * Defines all HTTP routes for the CLOVER CV Cover Letter Generator.
 * 
 * Routes:
 * - GET / : Main application form (index)
 * - POST /generate : Cover letter generation endpoint
 * - GET /healthz : Health check endpoint for monitoring
 * 
 * Middleware:
 * - Rate limiting on /generate (10 requests per hour)
 * - Security headers applied globally
 * - Request ID tracking for debugging
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

use App\Http\Controllers\CoverLetterController;
use Illuminate\Support\Facades\Route;

// Main application routes
Route::get('/', [CoverLetterController::class, 'index'])->name('home');
Route::post('/generate', [CoverLetterController::class, 'generate'])
    ->middleware(['throttle:10,60']) // Rate limiting: 10 requests per hour
    ->name('generate');
Route::get('/healthz', [CoverLetterController::class, 'healthz'])->name('health');
