<?php

use App\Http\Controllers\CoverLetterController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CoverLetterController::class, 'index'])->name('home');
Route::post('/generate', [CoverLetterController::class, 'generate'])
    ->middleware(['throttle:10,60']) // 10 requests per hour
    ->name('generate');
Route::get('/healthz', [CoverLetterController::class, 'healthz'])->name('health');
