<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoginHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\LoginAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form
     *
     * @return View|RedirectResponse
     */
    public function showLoginForm()
    {
        // Check if user already authenticated
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Return login view with cache control headers
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    /**
     * Handle login request with rate limiting and account lockout
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $username = $request->input('username');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Check if account is locked
        if (LoginAttempt::isLocked($username)) {
            $remainingSeconds = LoginAttempt::getLockTimeRemaining($username);
            $remainingMinutes = ceil($remainingSeconds / 60);
            
            return back()
                ->withErrors([
                    'username' => "Akun Anda terkunci karena terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$remainingMinutes} menit."
                ])
                ->onlyInput('username');
        }

        // Prepare credentials array
        $credentials = [
            'username' => $username,
            'password' => $password,
        ];

        // Implement rate limiting (per IP + username)
        $rateLimitKey = 'login.' . $request->ip() . '.' . $username;

        // Check if rate limit exceeded (5 attempts per minute)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return back()
                ->withErrors(['username' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik."])
                ->onlyInput('username');
        }

        // Attempt authentication
        if (Auth::attempt($credentials, $remember)) {
            // Get authenticated user
            $user = Auth::user();

            // Clear rate limiter on success
            RateLimiter::clear($rateLimitKey);

            // Record successful login attempt
            LoginAttempt::recordAttempt(
                $username,
                $request->ip(),
                $request->userAgent(),
                true
            );

            // Clear any previous failed attempts
            LoginAttempt::clearAttempts($username);

            // Regenerate session
            $request->session()->regenerate();

            // Store user_id in session for VerifySession middleware
            $request->session()->put('user_id', $user->user_id);

            // Log login activity
            LoginHelper::logLogin(
                $user->user_id,
                'User logged in successfully',
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'remember' => $remember,
                ]
            );

            // Flash success message
            Session::flash('message', 'Selamat datang kembali!');
            Session::flash('class', 'success');

            // Redirect to dashboard with cache control headers
            return redirect()
                ->intended(route('dashboard'))
                ->withHeaders([
                    'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
                ]);
        }

        // Failed authentication
        RateLimiter::hit($rateLimitKey, 60);

        // Record failed login attempt
        LoginAttempt::recordAttempt(
            $username,
            $request->ip(),
            $request->userAgent(),
            false
        );

        // Check if we should lock the account (5 failed attempts in 15 minutes)
        $failedAttempts = LoginAttempt::countRecentFailedAttempts($username, 15);
        
        if ($failedAttempts >= 5) {
            // Lock account for 30 minutes
            LoginAttempt::lockAccount($username, 30);
            
            // Log the lockout
            LoginHelper::logLogin(
                0,
                "Account locked due to too many failed login attempts: {$username}",
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'failed_attempts' => $failedAttempts,
                ]
            );
            
            return back()
                ->withErrors([
                    'username' => 'Akun Anda telah dikunci karena terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam 30 menit.'
                ])
                ->onlyInput('username');
        }

        // Show remaining attempts
        $remainingAttempts = 5 - $failedAttempts;
        $errorMessage = "Username atau password tidak valid.";
        
        if ($remainingAttempts <= 3) {
            $errorMessage .= " Anda memiliki {$remainingAttempts} percobaan lagi sebelum akun dikunci.";
        }

        return back()
            ->withErrors(['username' => $errorMessage])
            ->onlyInput('username');
    }

    /**
     * Generate encrypted login token
     *
     * @param User $user
     * @param int $expiresInMinutes
     * @param bool $remember
     * @return string
     */
    public static function generateLoginToken(User $user, int $expiresInMinutes = 60, bool $remember = false): string
    {
        // Create data array
        $data = [
            'user_id' => $user->user_id,
            'expires_at' => now()->addMinutes($expiresInMinutes)->toDateTimeString(),
            'remember' => $remember,
        ];

        // Encrypt data
        return Crypt::encryptString(json_encode($data));
    }

    /**
     * Handle token-based login
     *
     * @param string $token
     * @return RedirectResponse
     */
    public function loginViaToken(string $token): RedirectResponse
    {
        $request = request();
        
        // Implement rate limiting
        $tokenPrefix = substr($token, 0, 20);
        $rateLimitKey = 'token-login.' . $request->ip() . '.' . $tokenPrefix;

        // Check if rate limit exceeded
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            
            Session::flash('message', "Terlalu banyak percobaan. Silakan coba lagi dalam {$seconds} detik.");
            Session::flash('class', 'error');
            
            return redirect()->route('login');
        }

        // Create token hash and check if used
        $usedTokenKey = 'used_token_' . hash('sha256', $token);
        
        if (Cache::has($usedTokenKey)) {
            RateLimiter::hit($rateLimitKey, 60);
            
            Session::flash('message', 'Token telah digunakan dan tidak dapat digunakan lagi.');
            Session::flash('class', 'error');
            
            return redirect()->route('login');
        }

        try {
            // Decrypt token
            $decrypted = Crypt::decryptString($token);
            $data = json_decode($decrypted, true);

            // Validate token structure
            if (!isset($data['user_id']) || !isset($data['expires_at'])) {
                RateLimiter::hit($rateLimitKey, 60);
                
                Session::flash('message', 'Token tidak valid.');
                Session::flash('class', 'error');
                
                return redirect()->route('login');
            }

            // Check if token expired
            $expiresAt = \Carbon\Carbon::parse($data['expires_at']);
            if (now()->greaterThan($expiresAt)) {
                RateLimiter::hit($rateLimitKey, 60);
                
                Session::flash('message', 'Token telah kedaluwarsa.');
                Session::flash('class', 'error');
                
                return redirect()->route('login');
            }

            // Find user
            $user = User::find($data['user_id']);
            
            if (!$user) {
                RateLimiter::hit($rateLimitKey, 60);
                
                Session::flash('message', 'User tidak ditemukan.');
                Session::flash('class', 'error');
                
                return redirect()->route('login');
            }

            // Mark token as used in cache (7 days)
            Cache::put($usedTokenKey, true, now()->addDays(7));

            // Login user
            $remember = $data['remember'] ?? false;
            Auth::login($user, $remember);

            // Regenerate session
            session()->regenerate();

            // Store user_id in session for VerifySession middleware
            session()->put('user_id', $user->user_id);

            // Clear rate limiter
            RateLimiter::clear($rateLimitKey);

            // Log token login activity
            LoginHelper::logLogin(
                $user->user_id,
                'User logged in via token',
                [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'remember' => $remember,
                    'token_login' => true,
                ]
            );

            // Flash success message
            Session::flash('message', 'Selamat datang kembali!');
            Session::flash('class', 'success');

            // Redirect to dashboard with cache control headers
            return redirect()
                ->route('dashboard')
                ->withHeaders([
                    'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
                ]);

        } catch (\Exception $e) {
            // Hit rate limiter on any exception
            RateLimiter::hit($rateLimitKey, 60);
            
            Session::flash('message', 'Token tidak valid atau telah rusak.');
            Session::flash('class', 'error');
            
            return redirect()->route('login');
        }
    }
}
