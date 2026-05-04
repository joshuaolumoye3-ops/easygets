<?php

// app/Http/Controllers/AdminAuthController.php
namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $request->email;
        $admin = Admin::where('email', $login)
            ->orWhere('name', $login)
            ->first();

        if ($admin && $admin->status !== 'active') {
            return back()->withErrors([
                'email' => 'Your admin account is inactive. Please contact a super admin to reactivate it.',
            ])->withInput();
        }

        $credentials = [
            'password' => $request->password,
            'status' => 'active',
        ];

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $credentials['email'] = $login;
        } else {
            $credentials['name'] = $login;
        }

        if (!Auth::guard('admins')->attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors([
                'email' => 'Invalid credentials.',
            ])->withInput();
        }

        // regenerate session to prevent CSRF/session fixation issues
        try {
            $request->session()->regenerate();
        } catch (\Exception $e) {
            // Log but don't fail the login
            \Illuminate\Support\Facades\Log::warning('Session regenerate failed during admin login: ' . $e->getMessage());
        }

        // update last login timestamp
        $admin = Auth::guard('admins')->user();
        $admin->update(['last_login' => now()]);

        return redirect()->route('admin.dashboard')->with('success', 'Welcome back, '.$admin->name.'!');
    }

    // public function index()
    // {
    //     // Fetch all admins with pagination (optional)
    //     $admins = Admin::orderBy('created_at', 'desc')->paginate(10);

    //     return view('admin.adminlist', compact('admins'));
    // }
    public function showLogin()
    {
        // Regenerate CSRF token to ensure the login form receives a fresh token
        try {
            session()->regenerateToken();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to regenerate CSRF token for admin login: ' . $e->getMessage());
        }

        return view('admin.login');
    }

    public function index()
    {
        $admins = Admin::withCount('transactions')->paginate(10);
        return view('admin.adminlist', compact('admins'));
    }

    public function showadmin(Admin $admin)
    {
        $admin->load(['transactions' => function ($query) {
            $query->latest();
        }]);

        return response()->json($admin);
    }

    public function updateadmin(Request $request, Admin $admin)
    {
        $currentAdmin = Auth::guard('admins')->user();
        if (!$currentAdmin || $currentAdmin->role !== 'super_admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Only super admins can update administrators.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized. Only super admins can update administrators.');
        }

        $request->validate([
            'role' => 'required|in:super_admin,admin',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $admin->role = $request->role;
        $admin->status = $request->status;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Admin updated successfully!']);
        }

        return redirect()->back()->with('success', 'Admin updated successfully!');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
