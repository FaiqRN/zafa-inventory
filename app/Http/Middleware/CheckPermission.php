<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $authIdentifier = Auth::id();

        if ($authIdentifier === null) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu');
        }

        /** @var User $user */
        $user = User::query()
            ->where(User::FIELD_USERNAME, (string) $authIdentifier)
            ->first();
        
        if (!$user || !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki akses untuk halaman ini');
        }

        return $next($request);
    }
}
