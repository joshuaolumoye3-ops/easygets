<?php
// app/Http/Controllers/Auth/RegisterController.php
// ── FULL REPLACEMENT — includes referral code support ──

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Models\EmailOtp;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{
    private const DISPOSABLE_DOMAINS = [
        'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
        'throwaway.email', 'temp-mail.org', 'sharklasers.com', 'grr.la', 'yopmail.com',
        'fakeinbox.com', 'trashmail.com', 'mytrashmail.com', 'spam4.me', 'temp-email.mobi',
    ];

    private const BOT_USERNAME_PATTERNS = [
        '/^(admin|test|bot|fake|dummy|user\d{3,}|bot\d+)/i',
        '/^[a-z]{1,3}\d{5,}$/',
        '/^(xd|xx|qq|vk|zz|qq)\d+$/i',
    ];

    public function showRegisterForm()
    {
        session()->regenerateToken();
        return view('onboarding.signup');
    }

    public function register(Request $request)
    {
        // ── SECURITY CHECKS ──────────────────────────────────────

        if ($request->filled('website_url')) {
            Log::warning('Honeypot triggered during registration', ['ip' => $request->ip()]);
            return back()->withErrors(['registration' => 'Invalid form submission.'])->withInput();
        }

        $rateLimitKey = 'register_ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()->withErrors(['registration' => 'Too many registration attempts. Please try again after 1 hour.']);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $recentRegKey = 'recent_reg:' . $request->ip();
        if (Cache::has($recentRegKey)) {
            return back()->withErrors(['registration' => 'Please wait a moment before registering again.'])->withInput();
        }

        if ($this->isSuspiciousUserAgent($request->userAgent())) {
            return back()->withErrors(['registration' => 'Invalid request.'])->withInput();
        }

        // ── STANDARD VALIDATION ───────────────────────────────────

        $validated = $request->validate([
            'username'      => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'email'         => 'required|email|max:255',
            'phone_number'  => 'nullable|string|max:20',
            'password'      => 'required|string|min:6|confirmed',
            'referral_code' => 'nullable|string|max:50',
        ], [
            'username.regex' => 'Username can only contain letters, numbers, underscores, and hyphens.',
            'password.min'   => 'Password must be at least 6 characters.',
        ]);

        // ── REFERRAL CODE VALIDATION ──────────────────────────────

        $referrer = null;
        if (!empty($validated['referral_code'])) {
            $referrer = User::where('username', $validated['referral_code'])->first();
            if (!$referrer) {
                return back()
                    ->withErrors(['referral_code' => 'Invalid referral code. No user found with that username.'])
                    ->withInput();
            }
            // Prevent self-referral
            if (strtolower($referrer->username) === strtolower($validated['username'])) {
                return back()
                    ->withErrors(['referral_code' => 'You cannot refer yourself.'])
                    ->withInput();
            }
        }

        // ── SUSPICIOUS PATTERN DETECTION ─────────────────────────

        foreach (self::BOT_USERNAME_PATTERNS as $pattern) {
            if (preg_match($pattern, $validated['username'])) {
                return back()->withErrors(['username' => 'This username is not allowed.'])->withInput();
            }
        }

        if ($this->isSuspiciousNameCombination($validated)) {
            return back()->withErrors(['username' => 'Please provide a valid username.'])->withInput();
        }

        if ($this->isDisposableEmail($validated['email'])) {
            return back()->withErrors(['email' => 'Disposable emails are not allowed.'])->withInput();
        }

        if ($this->hasSuspiciousEmailPattern($validated['email'])) {
            return back()->withErrors(['email' => 'Please use a valid email address.'])->withInput();
        }

        // ── UNIQUENESS CHECKS ─────────────────────────────────────

        if (User::where('username', $validated['username'])->exists()) {
            return back()->withErrors(['username' => 'The username is already taken.'])->withInput();
        }

        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            if ($existingUser->email_verified_at) {
                return redirect()->route('login')
                    ->with('info', 'You already have an account. Please sign in.');
            }

            $otp = rand(100000, 999999);
            EmailOtp::updateOrCreate(
                ['user_id' => $existingUser->id],
                ['otp' => $otp, 'expires_at' => Carbon::now()->addMinutes(5)]
            );
            Mail::to($existingUser->email)->send(new OtpMail($otp));

            return redirect()->route('onboarding.emailOtp', ['user' => $existingUser->id])
                ->with('warning', 'You already have an existing account. Please verify your email. A new OTP has been sent.');
        }

        if (!empty($validated['phone_number']) && User::where('phone_number', $validated['phone_number'])->exists()) {
            return back()->withErrors(['phone_number' => 'The phone number is already associated with another account.'])->withInput();
        }

        // ── USER CREATION ─────────────────────────────────────────

        Cache::put($recentRegKey, true, 10);

        $user = User::create([
            'username'     => $validated['username'],
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password'     => Hash::make($validated['password']),
            'is_blocked'   => false,
            'referred_by'  => $referrer?->id,
        ]);

        // Register the referral relationship
        if ($referrer) {
            app(ReferralService::class)->registerReferral($user, $referrer);
        }

        Log::info('New user registration', [
            'username'    => $validated['username'],
            'email'       => $validated['email'],
            'ip'          => $request->ip(),
            'user_id'     => $user->id,
            'referred_by' => $referrer?->id,
        ]);

        // Generate and send OTP
        $otp = rand(100000, 999999);
        EmailOtp::create([
            'user_id'    => $user->id,
            'otp'        => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);
        Mail::to($user->email)->send(new OtpMail($otp));

        return redirect()->route('onboarding.emailOtp', ['user' => $user->id])
            ->with('success', 'Account created successfully! Check your email for the OTP.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|numeric|digits:6']);

        $otpRecord = EmailOtp::where('otp', $request->otp)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$otpRecord || $otpRecord->expires_at < now()) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP']);
        }

        $user = $otpRecord->user;
        if (!$user) {
            return back()->withErrors(['otp' => 'User not found for this OTP']);
        }

        $user->email_verified_at = Carbon::now();
        $user->save();

        EmailOtp::where('user_id', $user->id)->delete();

        return redirect()->route('login')->with('success', 'Email verified! Please sign in.');
    }

    public function showOtpForm($userId)
    {
        return view('onboarding.emailOtp', ['userId' => $userId]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
        ]);

        Auth::user()->update([
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'phone_number' => $request->phone_number,
        ]);

        return back()->with('success', 'Profile updated successfully.');
    }

    // ── SECURITY HELPERS ──────────────────────────────────────

    private function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return in_array($domain, self::DISPOSABLE_DOMAINS);
    }

    private function hasSuspiciousEmailPattern(string $email): bool
    {
        if (preg_match('/^[0-9]{5,}/', $email)) return true;
        if (preg_match('/(.)\1{3,}/', $email)) return true;
        if (substr_count($email, '.') > 2) return true;
        return false;
    }

    private function isSuspiciousNameCombination(array $validated): bool
    {
        if (strlen($validated['first_name']) < 2 || strlen($validated['last_name']) < 2) return true;
        return false;
    }

    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $patterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python',
            'java(?!script)', 'perl', 'ruby', 'golang', 'c\+\+', 'c#',
            'postman', 'insomnia', 'thunderclient',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', strtolower($userAgent))) return true;
        }
        return empty($userAgent) || strlen($userAgent) < 10;
    }

    // ── AJAX: Check referral code ─────────────────────────────

    public function checkReferralCode(Request $request)
    {
        $code = trim($request->get('code', ''));
        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'No code provided.']);
        }

        $user = User::where('username', $code)->first();
        if ($user) {
            return response()->json([
                'valid'   => true,
                'message' => 'Valid referral code — referred by ' . $user->first_name . '!',
            ]);
        }

        return response()->json(['valid' => false, 'message' => 'No user found with that username.']);
    }
}