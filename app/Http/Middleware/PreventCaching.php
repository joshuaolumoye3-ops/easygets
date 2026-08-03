<?php
// app/Http/Middleware/PreventCaching.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Ensures authenticated, personalized pages (wallet balance, flash messages,
 * transaction history) are NEVER stored by the browser's back/forward cache
 * or by any reverse proxy / CDN cache sitting in front of the app.
 *
 * Without this, a shared cache could serve one user's dashboard (including
 * their flash message and balance) to a different user — a real data leak,
 * not just a display bug.
 */
class PreventCaching
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}