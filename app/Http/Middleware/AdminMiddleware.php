<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $admin = Auth::guard('admins')->user();

        // If not logged in → redirect to login page
        if (!$admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to access this page.');
        }

        // If role is not allowed → redirect back with error message
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // Role-based restriction
        if (!empty($roles) && !in_array($admin->role, $roles)) {
            return redirect()->back()->with('error', 'You do not have the required role.');
        }

        return $next($request);
    }
}
