<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Service;

class CheckServiceActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $serviceSlug = null)
    {
        // If no service slug provided, continue
        if (!$serviceSlug) {
            return $next($request);
        }

        // Get the service by slug
        $service = Service::where('slug', $serviceSlug)->first();

        // If service doesn't exist or is inactive, redirect with error
        if (!$service || !$service->isActive()) {
            return redirect('/app/dashboard')->with('error', '🔧 Service Temporarily Unavailable - We\'re currently maintaining this service. Please try again shortly.');
        }

        return $next($request);
    }
}
