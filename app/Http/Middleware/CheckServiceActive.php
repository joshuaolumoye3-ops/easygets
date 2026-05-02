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

        // CRITICAL: Always query fresh data from database, no caching
        // Clear any query cache to ensure we get latest status from database
        $service = Service::where('slug', $serviceSlug)
            ->first();

        // If service doesn't exist or is inactive, redirect with error
        if (!$service) {
            \Log::warning('Service not found', ['service_slug' => $serviceSlug]);
            return redirect('/app/dashboard')->with('error', '🔧 Service Not Found - The service you\'re looking for doesn\'t exist.');
        }

        if (!$service->isActive()) {
            \Log::info('Service inactive access attempt', [
                'service_slug' => $serviceSlug,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'is_active' => $service->is_active,
                'user_id' => auth()->id()
            ]);
            return redirect('/app/dashboard')->with('error', '🔧 Service Temporarily Unavailable - We\'re currently maintaining this service. Please try again shortly.');
        }

        return $next($request);
    }
}
