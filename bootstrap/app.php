<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnforceHttps::class,
        ]);

        $middleware->alias([
        'auth.custom' => \App\Http\Middleware\RedirectIfNotAuthenticated::class,
        'nocache' => \App\Http\Middleware\NoCache::class,
        'verifysession' => \App\Http\Middleware\VerifySession::class,
        'session.timeout' => \App\Http\Middleware\SessionTimeout::class,
        'prevent.back' => \App\Http\Middleware\PreventBackAfterLogout::class,
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'check.user.role' => \App\Http\Middleware\CheckUserHasRole::class,
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                    'error' => $e->getMessage(),
                ], 403);
            }

            return response()->view('errors.403', ['exception' => $e], 403);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aksi ini tidak diizinkan.',
                    'error' => $e->getMessage(),
                ], 403);
            }

            return response()->view('errors.403', ['exception' => $e], 403);
        });
    })->create();
