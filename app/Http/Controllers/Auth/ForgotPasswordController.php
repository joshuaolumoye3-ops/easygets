<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\EmailOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Step 1: Show email input form
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    // Step 2: Handle email submission, send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'No account found with that email.'])->withInput();
        }
        $otp = rand(100000, 999999);
        EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP for password reset', ['user_id' => $user->id, 'email' => $user->email, 'msg' => $e->getMessage()]);
            return back()->withErrors(['email' => 'Failed to send OTP. Please try again.']);
        }
        Session::put('password_reset_user', $user->id);
        return redirect()->route('password.otp')->with('info', 'OTP sent to your email.');
    }

    // Step 3: Show OTP input form
    public function showOtpForm()
    {
        $userId = Session::get('password_reset_user');
        if (!$userId) return redirect()->route('password.request');
        $user = User::find($userId);
        return view('auth.forgot-password-otp', compact('user'));
    }

    // Step 4: Verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);
        $userId = Session::get('password_reset_user');
        if (!$userId) return redirect()->route('password.request');
        $otpRow = EmailOtp::where('user_id', $userId)->first();
        if (!$otpRow || $otpRow->otp !== $request->otp || Carbon::now()->gt($otpRow->expires_at)) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }
        Session::put('password_reset_verified', true);
        return redirect()->route('password.reset');
    }

    // Step 5: Show reset password form
    public function showResetForm()
    {
        $userId = Session::get('password_reset_user');
        $verified = Session::get('password_reset_verified');
        if (!$userId || !$verified) return redirect()->route('password.request');
        $user = User::find($userId);
        return view('auth.forgot-password-reset', compact('user'));
    }

    // Step 6: Handle password reset
    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);
        $userId = Session::get('password_reset_user');
        $verified = Session::get('password_reset_verified');
        if (!$userId || !$verified) return redirect()->route('password.request');
        $user = User::find($userId);
        $user->password = Hash::make($request->password);
        $user->save();
        // Clean up OTP and session
        EmailOtp::where('user_id', $userId)->delete();
        Session::forget(['password_reset_user', 'password_reset_verified']);
        return redirect()->route('login')->with('success', 'Password reset successful. You can now log in.');
    }
}
