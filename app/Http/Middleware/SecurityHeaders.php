<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline response security headers. The CSP is intentionally conservative and
 * matches the assets the app actually loads today:
 *   - scripts/styles: bundled by Vite and served from 'self'
 *   - Google Fonts stylesheet + font files
 *   - some inline style="" attributes in Blade (hence 'unsafe-inline' for styles only)
 *
 * script-src carries 'unsafe-eval' because the default Alpine.js build evaluates
 * x-data / @click expressions via new Function(). It does NOT get 'unsafe-inline'
 * (there are no inline <script> blocks). To drop 'unsafe-eval', switch to the
 * Alpine CSP build (@alpinejs/csp) and its stricter expression syntax.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), browsing-topics=()',
        ];

        // The CSP is skipped only with debug mode on, so `npm run dev` HMR
        // (websocket to localhost) is not blocked during local development.
        // Production (APP_DEBUG=false) always receives it.
        if (! config('app.debug')) {
            $headers['Content-Security-Policy'] = implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "img-src 'self' data:",
                "font-src 'self' https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'unsafe-eval'",
                "connect-src 'self'",
            ]);
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
