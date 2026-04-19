<?php
namespace App\Http\Middleware;

use App\Models\User;
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
        $authIdentifier = Auth::id();

        if ($authIdentifier !== null) {
            $authenticatedUserId = User::query()
                ->where(User::FIELD_USERNAME, (string) $authIdentifier)
                ->value(User::FIELD_USER_ID);

            if (
                !$request->session()->has('user_id') ||
                (string) $request->session()->get('user_id') !== (string) $authenticatedUserId
            ) {
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
