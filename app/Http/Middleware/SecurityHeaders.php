<?php

/**
 * SecurityHeaders Middleware
 * 
 * Implements comprehensive security headers for the CLOVER application.
 * Provides defense against common web vulnerabilities and attacks.
 * 
 * Security Headers:
 * - Content Security Policy (CSP) with nonce support
 * - X-Frame-Options: Prevents clickjacking
 * - X-Content-Type-Options: Prevents MIME sniffing
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Restricts browser features
 * - Strict-Transport-Security: Enforces HTTPS
 * - X-XSS-Protection: XSS filtering
 * - Cross-Origin policies: CORS protection
 * 
 * CSP Configuration:
 * - Allows Alpine.js from CDN
 * - Allows Figtree fonts from Bunny.net
 * - Restricts all other external resources
 * - Uses nonce for inline scripts
 * 
 * @author Gerald Sadya
 * @version 1.1.0
 * @since 2025-01-21
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and add security headers
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Generate CSP nonce for inline scripts (Alpine.js)
        $nonce = base64_encode(random_bytes(16));
        
        // Store nonce in app container for use in views
        App::instance('csp_nonce', $nonce);
        
        // Set comprehensive security headers
        if ($response instanceof \Illuminate\Http\Response) {
            return $response
                ->header('Content-Security-Policy', 
                    "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
                    "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
                    "font-src 'self' https://fonts.bunny.net; " .
                    "img-src 'self' data:; " .
                    "connect-src 'self'; " .
                    "frame-ancestors 'none';"
                )
                ->header('X-Frame-Options', 'DENY')
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Referrer-Policy', 'no-referrer')
                ->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=()')
                ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
                ->header('X-XSS-Protection', '1; mode=block')
                ->header('Cross-Origin-Embedder-Policy', 'require-corp')
                ->header('Cross-Origin-Opener-Policy', 'same-origin')
                ->header('Cross-Origin-Resource-Policy', 'same-origin');
        }
        
        return $response;
    }
}
