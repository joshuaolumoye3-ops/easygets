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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{
    // Disposable email domains to block
    private const DISPOSABLE_DOMAINS = [
        'tempmail.com', '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 
        'throwaway.email', 'temp-mail.org', 'sharklasers.com', 'grr.la', 'yopmail.com',
        'fakeinbox.com', 'trashmail.com', 'mytrashmail.com', 'spam4.me', 'temp-email.mobi'
    ];

    // Common bot patterns in usernames
    private const BOT_USERNAME_PATTERNS = [
        '/^(admin|test|bot|fake|dummy|user\d{3,}|bot\d+)/i',
        '/^[a-z]{1,3}\d{5,}$/', // abc12345
        '/^(xd|xx|qq|vk|zz|qq)\d+$/i' // common bot patterns
    ];

    public function showRegisterForm()
    {
        session()->regenerateToken();
        return view('onboarding.signup');
    }

    public function register(Request $request)
{
    // ============ SECURITY CHECKS ============
    
    // 1. Check honeypot field (bot trap)
    if ($request->filled('website_url')) {
        Log::warning('Honeypot triggered during registration', ['ip' => $request->ip()]);
        return back()->withErrors(['registration' => 'Invalid form submission.'])->withInput();
    }

    // 2. Rate limiting - Max 5 attempts per IP per hour
    $rateLimitKey = 'register_ip:' . $request->ip();
    $attempts = RateLimiter::attempts($rateLimitKey);
    
    if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
        Log::warning('Registration rate limit exceeded', ['ip' => $request->ip(), 'attempts' => $attempts]);
        return back()->withErrors(['registration' => 'Too many registration attempts. Please try again after 1 hour.']);
    }
    
    RateLimiter::hit($rateLimitKey, 3600); // 1 hour expiry

    // 3. Check for rapid sequential registrations from same IP (within 10 seconds)
    $recentRegKey = 'recent_reg:' . $request->ip();
    if (Cache::has($recentRegKey)) {
        Log::warning('Rapid registration attempt detected', ['ip' => $request->ip()]);
        return back()->withErrors(['registration' => 'Please wait a moment before registering again.'])->withInput();
    }

    // 4. Check User-Agent validity (no bot user agents)
    if ($this->isSuspiciousUserAgent($request->userAgent())) {
        Log::warning('Suspicious User-Agent during registration attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        return back()->withErrors(['registration' => 'Invalid request.'])->withInput();
    }

    // ============ STANDARD VALIDATION ============
    $validated = $request->validate([
        'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|email|max:255',
        'phone_number' => 'nullable|string|max:20',
        'password' => 'required|string|min:6|confirmed',
    ], [
        'username.regex' => 'Username can only contain letters, numbers, underscores, and hyphens.',
        'password.min' => 'Password must be at least 6 characters.',
    ]);

    // ============ SUSPICIOUS PATTERN DETECTION ============

    // Check if username matches bot patterns
    foreach (self::BOT_USERNAME_PATTERNS as $pattern) {
        if (preg_match($pattern, $validated['username'])) {
            Log::warning('Suspicious username pattern detected', [
                'ip' => $request->ip(),
                'username' => $validated['username']
            ]);
            return back()->withErrors(['username' => 'This username is not allowed.'])->withInput();
        }
    }

    // Check if username is too similar to first/last name (common bot behavior)
    if ($this->isSuspiciousNameCombination($validated)) {
        Log::warning('Suspicious name combination detected', [
            'ip' => $request->ip(),
            'username' => $validated['username']
        ]);
        return back()->withErrors(['username' => 'Please provide a valid username.'])->withInput();
    }

    // Check for disposable/temporary email
    if ($this->isDisposableEmail($validated['email'])) {
        Log::warning('Disposable email registration attempt', [
            'ip' => $request->ip(),
            'email' => $validated['email']
        ]);
        return back()->withErrors(['email' => 'Disposable emails are not allowed. Please use a real email address.'])->withInput();
    }

    // Check if email has suspicious patterns
    if ($this->hasSuspiciousEmailPattern($validated['email'])) {
        Log::warning('Suspicious email pattern detected', [
            'ip' => $request->ip(),
            'email' => $validated['email']
        ]);
        return back()->withErrors(['email' => 'Please use a valid email address.'])->withInput();
    }

    // ============ UNIQUENESS CHECKS ============

    // Check for unique username
    if (User::where('username', $validated['username'])->exists()) {
        Log::info('Username taken during registration', ['username' => $validated['username']]);
        return back()->withErrors(['username' => 'The username is already taken.'])->withInput();
    }

    // Check for unique email
    $existingUser = User::where('email', $validated['email'])->first();
    if ($existingUser) {
        if ($existingUser->email_verified_at) {
            // User already verified → redirect to login
            return redirect()->route('login')
                ->with('info', 'You already have an account. Please sign in.');
        } else {
            // User exists but not verified → resend OTP
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
                ->with('warning', 'You already have an existing account. Please verify your email. A new OTP has been sent.');
        }
    }

    // Check for unique phone number (if provided)
    if (!empty($validated['phone_number']) && User::where('phone_number', $validated['phone_number'])->exists()) {
        Log::info('Phone number already exists', ['phone' => $validated['phone_number']]);
        return back()->withErrors(['phone_number' => 'The phone number is already associated with another account.'])->withInput();
    }

    // ============ USER CREATION ============

    // Set cache to prevent rapid registrations
    Cache::put($recentRegKey, true, 10);

    // Create new user (blocked initially if suspicious patterns detected during onboarding)
    $user = User::create([
        'username' => $validated['username'],
        'first_name' => $validated['first_name'],
        'last_name' => $validated['last_name'],
        'email' => $validated['email'],
        'phone_number' => $validated['phone_number'] ?? null,
        'password' => Hash::make($validated['password']),
        'is_blocked' => false, // Will only be blocked if admin intervenes
    ]);

    Log::info('New user registration attempt', [
        'username' => $validated['username'],
        'email' => $validated['email'],
        'ip' => $request->ip(),
        'user_id' => $user->id
    ]);

    // Generate and send OTP
    $otp = rand(100000, 999999);

    EmailOtp::create([
        'user_id' => $user->id,
        'otp' => $otp,
        'expires_at' => Carbon::now()->addMinutes(5),
    ]);

    Mail::to($user->email)->send(new OtpMail($otp));

    return redirect()->route('onboarding.emailOtp', ['user' => $user->id])
                    ->with('success', 'Account created successfully! Check your email for the OTP.');
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

    // ============ SECURITY HELPER METHODS ============

    /**
     * Check if email is from a disposable email service
     */
    private function isDisposableEmail(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, self::DISPOSABLE_DOMAINS);
    }

    /**
     * Check for suspicious email patterns
     */
    private function hasSuspiciousEmailPattern(string $email): bool
    {
        // Check for excessive numbers or special patterns
        if (preg_match('/^[0-9]{5,}/', $email)) {
            return true; // Starts with 5+ numbers
        }
        if (preg_match('/(.)\1{3,}/', $email)) {
            return true; // Repeated characters 4+ times
        }
        if (substr_count($email, '.') > 2) {
            return true; // Too many dots
        }
        return false;
    }

    /**
     * Check if username/name combination is suspicious
     */
    private function isSuspiciousNameCombination(array $validated): bool
    {
        $username = strtolower($validated['username']);
        $firstName = strtolower($validated['first_name']);
        $lastName = strtolower($validated['last_name']);

        // Username shouldn't be very similar to name
        if (strlen($username) >= 3) {
            if (strpos($firstName, substr($username, 0, 3)) === 0 || 
                strpos($lastName, substr($username, 0, 3)) === 0) {
                return false; // This is actually normal, don't block
            }
        }

        // But block if all fields are too short (likely bot)
        if (strlen($firstName) < 2 || strlen($lastName) < 2) {
            return true;
        }

        return false;
    }

    /**
     * Check for suspicious User-Agent strings
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python', 
            'java(?!script)', 'perl', 'ruby', 'golang', 'c\+\+', 'c#', 
            'postman', 'insomnia', 'thunderclient'
        ];

        $userAgentLower = strtolower($userAgent);
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $userAgentLower)) {
                return true;
            }
        }

        // User-Agent is empty or suspiciously short
        if (empty($userAgent) || strlen($userAgent) < 10) {
            return true;
        }

        return false;
    }
}
