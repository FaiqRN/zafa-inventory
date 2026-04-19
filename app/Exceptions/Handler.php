<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Custom 403 error page with layout
        $this->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
                    'error' => $e->getMessage()
                ], 403);
            }

            return response()->view('errors.403', ['exception' => $e], 403);
        });

        // Custom 403 for authorization exceptions
        $this->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Aksi ini tidak diizinkan.',
                    'error' => $e->getMessage()
                ], 403);
            }

            return response()->view('errors.403', ['exception' => $e], 403);
        });
    }
}
