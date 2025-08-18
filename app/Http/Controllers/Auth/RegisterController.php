<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('onboarding.signup');
    }

    public function register(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'username' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'phone_number' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Check if username already exists
        if (User::where('username', $validated['username'])->exists()) {
            return back()->withErrors(['username' => 'The username is already taken.'])->withInput();
        }

        // Check if user already exists by email
        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser) {
            if ($existingUser->email_verified_at) {
                // User already verified → redirect to sign in
                return redirect()->route('login')
                    ->with('info', 'You already have an account. Please sign in.');
            } else {
                // User exists but email not verified → Resend OTP
                $otp = rand(100000, 999999);

                EmailOtp::updateOrCreate(
                    ['user_id' => $existingUser->id],
                    [
                        'otp' => $otp,
                        'expires_at' => Carbon::now()->addMinutes(5),
                    ]
                );

                Mail::to($existingUser->email)->send(new OtpMail($otp));


                return redirect()->route('onboarding.emailOtp', ['user' => $existingUser->id])
                    ->with('warning', 'You already have an existing account with us. Please verify your email. A new OTP has been sent.');
            }
        }

        // Create new user
        $user = User::create([
            'username' => $validated['username'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);


        // Generate OTP for new user
        $otp = rand(100000, 999999);

        EmailOtp::create([
            'user_id' => $user->id,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return redirect()->route('onboarding.emailOtp', ['user' => $user->id])
                        ->with('success', 'Account created! Check your email for OTP.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|numeric|digits:6',
        ]);

        // Get the most recent OTP record with that code
        $otpRecord = EmailOtp::where('otp', $request->otp)
                        ->orderBy('created_at', 'desc')
                        ->first();

        // If no OTP or expired
        if (!$otpRecord || $otpRecord->expires_at < now()) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP']);
        }

        // Fetch the user automatically
        $user = $otpRecord->user;

        if (!$user) {
            return back()->withErrors(['otp' => 'User not found for this OTP']);
        }

        // ✅ Mark email as verified (explicitly)
        $user->email_verified_at = \Carbon\Carbon::now();
        $user->save();

        // Remove all OTPs for this user
        EmailOtp::where('user_id', $user->id)->delete();

        return redirect()->route('login')->with('success', 'Email verified! Please sign in.');
    }

    public function showOtpForm($userId)
    {
        return view('onboarding.emailOtp', ['userId' => $userId]);
    }

    public function updateProfile(Request $request)
    {
        // Validate input
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Update fields
        $user->update([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone_number' => $request->phone_number,
        ]);

        // Redirect back with success message
        return back()->with('success', 'Profile updated successfully.');
    }
}
