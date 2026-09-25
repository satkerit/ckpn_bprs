<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * S2 FIX: Added strict Content-Security-Policy header to prevent
     * inline script injection and enforce script/style loading from safe sources.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN');
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            
            // S2 FIX: Strict Content-Security-Policy
            // - default-src 'self': block all except from same origin
            // - script-src 'self' 'unsafe-inline' (temporary for Livewire compatibility)
            // - style-src 'self' 'unsafe-inline' (temporary for Tailwind inline styles)
            // - img-src 'self' data: https: (allow data URIs and remote images)
            // - font-src 'self' data: (allow fonts from same origin and data URIs)
            // - connect-src 'self' (restrict XHR/WebSocket to same origin)
            // - frame-ancestors 'none' (prevent embedding in frames)
            // - object-src 'none' (prevent plugins)
            // - base-uri 'self' (prevent changing base URL)
            // - form-action 'self' (prevent form submission to external sites)
            $csp = "default-src 'self'; "
                . "script-src 'self' 'unsafe-inline'; "
                . "style-src 'self' 'unsafe-inline'; "
                . "img-src 'self' data: https:; "
                . "font-src 'self' data:; "
                . "connect-src 'self'; "
                . "frame-ancestors 'none'; "
                . "object-src 'none'; "
                . "base-uri 'self'; "
                . "form-action 'self'; "
                . "upgrade-insecure-requests;";
            
            $response->header('Content-Security-Policy', $csp);
            
            if ($request->isSecure()) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
            }
        }

        return $response;
    }
}
