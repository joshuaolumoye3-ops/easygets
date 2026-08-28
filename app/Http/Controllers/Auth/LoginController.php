<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Regenerate session and CSRF token for fresh login form
        session()->regenerate();
        return view('auth.login');
    }

    protected function sendVerificationOtp(User $user)
    {
        $otp = rand(100000, 999999);

        \App\Models\EmailOtp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5),
            ]
        );

        try {
            Mail::to($user->email)->send(new \App\Mail\OtpMail($otp));
        } catch (\Exception $exception) {
            \Log::error('Failed to send verification OTP email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determine if the login field is email or username
        $fieldType = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Check if user exists before attempting login
        $user = User::where($fieldType, $credentials['login'])->first();
        if (!$user) {
            return back()->withErrors([
                'login' => 'Invalid email/username or password.',
            ])->onlyInput('login');
        }

        if ($user->isBlocked()) {
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

        if (!Auth::attempt([$fieldType => $credentials['login'], 'password' => $credentials['password']])) {
            $request->session()->regenerateToken();
            return back()->withErrors([
                'login' => 'Invalid email/username or password.',
            ])->onlyInput('login');
        }

        $request->session()->regenerate();
        $user = Auth::user();

        if ($user->email_verified_at === null) {
            Auth::logout();
            $this->sendVerificationOtp($user);

            return redirect()->route('verify.email.form', ['user' => $user->id])
                ->with('warning', 'Please verify your email first to continue. We\'ve sent a verification code to your email.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'))
                        ->with('success', 'Welcome back!');
    }

    public function showVerifyEmailForm($userId)
    {
        $user = User::findOrFail($userId);

        // If already verified, redirect to login
        if ($user->email_verified_at !== null) {
            return redirect()->route('login')->with('info', 'Your email is already verified. Please sign in.');
        }

        $existsOtp = \App\Models\EmailOtp::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if (!$existsOtp) {
            $this->sendVerificationOtp($user);
        }

        return view('auth.verify-email', ['user' => $user]);
    }

    public function verifyEmailFromLogin(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required|numeric|digits:6',
        ]);

        $user = User::findOrFail($request->user_id);

        // If already verified, redirect to login
        if ($user->email_verified_at !== null) {
            return redirect()->route('login')->with('info', 'Your email is already verified. Please sign in.');
        }

        // Get the most recent OTP record
        $otpRecord = \App\Models\EmailOtp::where('user_id', $user->id)
                                ->where('otp', $request->otp)
                                ->orderBy('created_at', 'desc')
                                ->first();

        // If no OTP or expired
        if (!$otpRecord || $otpRecord->expires_at < now()) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP. Please request a new one.'])->onlyInput('user_id');
        }

        // Mark email as verified
        $user->email_verified_at = \Carbon\Carbon::now();
        $user->save();

        // Remove all OTPs for this user
        \App\Models\EmailOtp::where('user_id', $user->id)->delete();

        return redirect()->route('login')->with('success', 'Email verified successfully! You can now sign in.');
    }

    public function resendVerificationOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->user_id);

        // If already verified, no need to resend
        if ($user->email_verified_at !== null) {
            return back()->with('info', 'Your email is already verified.');
        }

        $this->sendVerificationOtp($user);

        return back()->with('success', 'A new verification code has been sent to your email. Check your inbox.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
