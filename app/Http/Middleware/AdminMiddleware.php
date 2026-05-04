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

        // If not logged in → return JSON for AJAX or redirect for regular requests
        if (!$admin) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login to access this page.'
                ], 401);
            }
            return redirect()->route('admin.login')
                ->with('error', 'Please login to access this page.');
        }

        // If role is not allowed → return JSON for AJAX or redirect for regular requests
        if (!in_array($admin->role, ['super_admin', 'admin'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        // Role-based restriction
        if (!empty($roles) && !in_array($admin->role, $roles)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have the required role.'
                ], 403);
            }
            return redirect()->back()->with('error', 'You do not have the required role.');
        }

        if ($admin->status !== 'active') {
            Auth::guard('admins')->logout();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your admin account is inactive. Please contact a super admin to reactivate it.'
                ], 403);
            }

            return redirect()->route('admin.login')->with('error', 'Your admin account is inactive. Please contact a super admin to reactivate it.');
        }

        return $next($request);
    }
}
