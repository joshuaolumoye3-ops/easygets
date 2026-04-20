<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if the login field is email or username
        $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Check if user is blocked before attempting login
        $user = User::where($fieldType, $credentials['login'])->first();
        
        if ($user && $user->isBlocked()) {
            \Log::warning('Login attempt by blocked user', [
                'username' => $user->username,
                'email' => $user->email,
                'ip' => $request->ip(),
                'blocked_at' => $user->blocked_at,
                'reason' => $user->blocked_reason
            ]);
            return back()->withErrors([
                'login' => 'Your account has been blocked. Please contact support for assistance.',
            ])->onlyInput('login');
        }

        // Attempt authentication
        if (Auth::attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']])) {
            // Update last login timestamp
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);
            
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                            ->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'login' => 'Invalid email/username or password.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
