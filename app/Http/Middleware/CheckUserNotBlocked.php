<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckUserNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();

            // If user is blocked, log them out and redirect to login
            if ($user->is_blocked) {
                Log::warning('Blocked user attempted to access protected route', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'route' => $request->path(),
                    'ip' => $request->ip(),
                    'blocked_reason' => $user->blocked_reason
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'blocked' => 'Your account has been blocked and you have been logged out. Please contact support.'
                ]);
            }
        }

        return $next($request);
    }
}
