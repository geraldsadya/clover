<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestId
{
    /**
     * Handle an incoming request and add request ID
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate UUID for request correlation
        $requestId = Str::uuid()->toString();
        
        // Add to request headers for logging
        $request->headers->set('X-Request-ID', $requestId);
        
        $response = $next($request);
        
        // Add request ID to response headers
        if ($response instanceof \Illuminate\Http\Response) {
            return $response->header('X-Request-ID', $requestId);
        }
        
        return $response;
    }
}
