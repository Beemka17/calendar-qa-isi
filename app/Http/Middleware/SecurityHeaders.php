<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->remove('Content-Security-Policy');

        $isLocal = app()->environment('local');

        // Script sources
        $scriptSrc = $isLocal
            ? "'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173 https://cdn.jsdelivr.net https://cdnjs.cloudflare.com"
            : "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com";

        // Style sources
        $styleSrc = $isLocal
            ? "'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173 https://fonts.bunny.net"
            : "'self' 'unsafe-inline' https://fonts.bunny.net";

        // Connect sources (WebSocket Reverb + Vite HMR)
        $connectSrc = $isLocal
            ? "'self' ws://localhost:8080 wss://localhost:8080 ws://127.0.0.1:8080 wss://127.0.0.1:8080 ws://localhost:5173 http://localhost:5173 http://127.0.0.1:5173"
            : "'self' ws://localhost:8080 wss://localhost:8080 ws://127.0.0.1:8080 wss://127.0.0.1:8080";

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "script-src-elem {$scriptSrc}",   // ← fix pesan 'script-src-elem not set'
            "style-src {$styleSrc}",
            "style-src-elem {$styleSrc}",      // ← fix pesan 'style-src-elem not set'
            "font-src 'self' https://fonts.bunny.net data:",
            "connect-src {$connectSrc}",
            "img-src 'self' data: blob:",
            "base-uri 'self'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}