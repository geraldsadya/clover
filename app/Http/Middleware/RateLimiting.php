<?php

/**
 * RateLimiting Middleware
 * 
 * Custom rate limiting middleware for the CLOVER application.
 * Implements IP-based rate limiting with cache storage and retry-after logic.
 * 
 * Features:
 * - IP-based rate limiting (10 requests per hour)
 * - Cache-based request counting
 * - Retry-after headers for client guidance
 * - Comprehensive logging for monitoring
 * - Rate limit headers in responses
 * 
 * Rate Limiting Logic:
 * - Tracks requests per IP address
 * - Blocks IPs that exceed 10 requests/hour
 * - Sets 1-hour retry-after period
 * - Provides clear error messages
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimiting
{
    private const MAX_REQUESTS_PER_HOUR = 10;
    private const CACHE_PREFIX = 'rate_limit:';
    private const RETRY_AFTER_PREFIX = 'retry_after:';

    /**
     * Handle an incoming request and enforce rate limiting
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $cacheKey = self::CACHE_PREFIX . $ip;
        $retryAfterKey = self::RETRY_AFTER_PREFIX . $ip;
        
        // Check if IP is currently rate limited
        $retryAfter = Cache::get($retryAfterKey);
        if ($retryAfter && $retryAfter > time()) {
            $secondsRemaining = $retryAfter - time();
            
            Log::info('Rate limit exceeded', [
                'ip' => $ip,
                'retry_after_seconds' => $secondsRemaining,
                'request_id' => $request->header('X-Request-ID', 'unknown')
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'code' => 'RATE_LIMIT_EXCEEDED'
                ]
            ], 429)->header('Retry-After', (string)$secondsRemaining);
        }
        
        // Get current request count
        $currentCount = Cache::get($cacheKey, 0);
        
        // Increment counter
        $newCount = $currentCount + 1;
        
        if ($newCount > self::MAX_REQUESTS_PER_HOUR) {
            // Set retry after timestamp (1 hour from now)
            $retryAfter = time() + 3600;
            Cache::put($retryAfterKey, $retryAfter, now()->addHour());
            
            Log::warning('Rate limit exceeded - setting retry after', [
                'ip' => $ip,
                'request_count' => $newCount,
                'retry_after_timestamp' => $retryAfter,
                'request_id' => $request->header('X-Request-ID', 'unknown')
            ]);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Rate limit exceeded. Please try again in 1 hour.',
                    'code' => 'RATE_LIMIT_EXCEEDED'
                ]
            ], 429)->header('Retry-After', '3600');
        }
        
        // Store updated count (expires in 1 hour)
        Cache::put($cacheKey, $newCount, now()->addHour());
        
        // Log request (without PII)
        Log::info('Rate limit check passed', [
            'ip' => $ip,
            'request_count' => $newCount,
            'max_requests' => self::MAX_REQUESTS_PER_HOUR,
            'request_id' => $request->header('X-Request-ID', 'unknown')
        ]);
        
        $response = $next($request);
        
        // Add rate limit headers to response
        if ($response instanceof \Illuminate\Http\Response) {
            return $response
                ->header('X-RateLimit-Limit', (string)self::MAX_REQUESTS_PER_HOUR)
                ->header('X-RateLimit-Remaining', (string)max(0, self::MAX_REQUESTS_PER_HOUR - $newCount))
                ->header('X-RateLimit-Reset', (string)now()->addHour()->timestamp);
        }
        
        return $response;
    }
}
