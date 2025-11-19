<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnforceHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya enforce HTTPS di production
        if (app()->environment('production')) {
            // Validasi HTTPS aktif
            if (!$request->secure()) {
                Log::warning('HTTP request detected in production environment', [
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                
                // Redirect ke HTTPS
                return redirect()->secure($request->getRequestUri(), 301);
            }
            
            // Validasi SESSION_SECURE_COOKIE configuration
            if (!config('session.secure')) {
                Log::critical('SESSION_SECURE_COOKIE is not enabled in production!', [
                    'session.secure' => config('session.secure'),
                    'app.env' => app()->environment(),
                ]);
                
                // Dalam production, ini adalah critical error
                // Tapi kita tetap lanjutkan dengan warning
            }
        }
        
        return $next($request);
    }
}
