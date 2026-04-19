<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * If user has no role, they can only access dashboard, logout, and profile
     */
    public function handle(Request $request, Closure $next)
    {
        $authIdentifier = Auth::id();
        $user = $authIdentifier !== null
            ? User::query()->where(User::FIELD_USERNAME, (string) $authIdentifier)->first()
            : null;

        // If user has no roles
        if ($user && $user->roles->isEmpty()) {
            // Allow these essential routes
            $allowedRoutes = [
                'dashboard',
                'dashboard.inventory-optimization',
                'dashboard.partner-performance',
                'logout',
                'profile',
                'profile.edit',
                'profile.update',
                'profile.change-password',
                'profile.update-password',
            ];

            // Allow by route name
            $routeName = $request->route()?->getName();

            if ($routeName !== null && in_array($routeName, $allowedRoutes, true)) {
                return $next($request);
            }

            // Allow by path pattern
            if ($request->is('dashboard') ||
                $request->is('dashboard/inventory-optimization') ||
                $request->is('dashboard/partner-performance') ||
                $request->is('/') ||
                $request->is('profile*') ||
                $request->is('pengaturan*')) {
                return $next($request);
            }

            // Redirect to dashboard with message
            return redirect()->route('dashboard')
                ->with('warning', 'Anda tidak memiliki role. Silakan hubungi administrator untuk mendapatkan akses.');
        }

        return $next($request);
    }
}
