<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        /**
         * ✅ LOG ALL ERRORS
         */
        $this->reportable(function (Throwable $e) {
            Log::error('Unhandled exception captured.', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });

        /**
         * ✅ VALIDATION ERROR
         */
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => null,
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        /**
         * ✅ AUTH ERROR
         */
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                    'errors' => null,
                ], 401);
            }
        });

        /**
         * ✅ 404 ERROR (ONLY API)
         */
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'data' => null,
                    'errors' => null,
                ], 404);
            }
        });

        /**
         * ✅ GENERAL ERROR (SAFE + DEBUG MODE SUPPORT)
         */
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*')) {

                $message = 'Server Error';
                $details = null;

                if (config('app.debug')) {
                    $message = $e->getMessage();
                    $details = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ];
                }

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'data' => null,
                    'errors' => $details,
                ], 500);
            }
        });
    }
}