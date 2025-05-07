<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifySession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifikasi session yang valid
        if (Auth::check()) {
            // Validasi tambahan session
            if (!$request->session()->has('user_id') || 
                $request->session()->get('user_id') != Auth::user()->user_id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                return redirect()->route('login')
                    ->with('error', 'Sesi anda telah berakhir, silahkan login kembali.');
            }
        }
        
        return $next($request);
    }
}