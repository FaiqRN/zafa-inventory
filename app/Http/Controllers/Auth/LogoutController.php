<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\LoginHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * Handle user logout with secure session cleanup
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        // Get current authenticated user data before logout
        $user = Auth::user();
        $userId = $user ? $user->user_id : null;
        $name = $user ? $user->firstname . ' ' . $user->lastname : 'Unknown';
        $email = $user ? $user->email : 'unknown@example.com';

        // Log logout activity before clearing session
        if ($userId) {
            $message = "User {$name} ({$email}) logged out";
            $metadata = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];
            
            LoginHelper::logLogout($userId, $message, $metadata);
        }

        // Clear authentication
        Auth::logout();

        // Invalidate the session
        $request->session()->invalidate();

        // Regenerate CSRF token
        $request->session()->regenerateToken();

        // Redirect to login with cache control headers and flash message
        return redirect()->route('login')
            ->withHeaders([
                'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => 'Fri, 01 Jan 1990 00:00:00 GMT',
            ]);
    }
}
