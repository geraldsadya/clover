<?php

/**
 * Create Cache Tables Migration
 * 
 * Creates cache tables for the CLOVER application.
 * Used for rate limiting and session storage.
 * 
 * Tables Created:
 * - cache: Application cache storage
 * - cache_locks: Cache locking mechanism
 * 
 * Usage:
 * - Rate limiting middleware uses cache for request counting
 * - Session storage for user state management
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
