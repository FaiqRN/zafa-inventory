<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    /**
     * Waktu idle maksimum sebelum user otomatis logout (dalam menit).
     */
    private const MAX_IDLE_MINUTES = 10;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika user telah login
        if (Auth::check()) {
            // Jika session last_activity tidak ada, set waktu sekarang
            if (!$request->session()->has('last_activity')) {
                $request->session()->put('last_activity', now()->timestamp);
            }

            // Ambil waktu aktivitas terakhir
            $lastActivity = $request->session()->get('last_activity');

            // Logout otomatis jika tidak ada aktivitas selama MAX_IDLE_MINUTES menit
            if (now()->timestamp - $lastActivity > self::MAX_IDLE_MINUTES * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama ' . self::MAX_IDLE_MINUTES . ' menit. Silakan login kembali.');
            }

            // Update waktu aktivitas terakhir setiap ada request baru
            $request->session()->put('last_activity', now()->timestamp);
        }

        return $next($request);
    }
}
