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

        // Try to authenticate with admins guard
        if (!Auth::guard('admins')->attempt([
            'email' => $request->email,
            'password' => $request->password,
        ], $request->filled('remember'))) {
            
            // Try also by username instead of email
            $admin = \App\Models\Admin::where('name', $request->email)->first();

            if (!$admin || !\Illuminate\Support\Facades\Hash::check($request->password, $admin->password)) {
                return back()->withErrors([
                    'email' => 'Invalid credentials.',
                ])->withInput();
            }

            Auth::guard('admins')->login($admin, $request->filled('remember'));
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
        $request->validate([
            'role' => 'required|in:super_admin,admin',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $admin->role = $request->role;

        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();

        return redirect()->back()->with('success', 'Admin updated successfully!');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('admin.login');
    }
}
