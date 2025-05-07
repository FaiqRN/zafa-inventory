<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class SessionTimeout
{
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
            
            // Cek apakah pengguna memilih "remember me"
            // Jika tidak, cek apakah session sudah melewati batas waktu maksimum
            // (default 120 menit/2 jam)
            $sessionLifetime = config('session.lifetime');
            $maxIdleMinutes = $sessionLifetime > 0 ? $sessionLifetime : 120;
            
            if (!Auth::viaRemember() && now()->timestamp - $lastActivity > $maxIdleMinutes * 60) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.');
            }
            
            // Update waktu aktivitas terakhir jika ada request baru
            $request->session()->put('last_activity', now()->timestamp);
        }
        
        return $next($request);
    }
}