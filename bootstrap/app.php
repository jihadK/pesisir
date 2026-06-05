<?php

use App\Support\ResponseCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
        // Endpoint anonim portal (lead tracker) tidak butuh CSRF — dipanggil
        // via fetch keepalive dari JS sebelum window.open(wa.me).
        $middleware->validateCsrfTokens(except: [
            'portal/lead',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render exception ke format {resCode, resMsg} untuk request yang expects JSON
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson()) return null; // biar Laravel handle pakai HTML view

            // Validation
            if ($e instanceof ValidationException) {
                return response()->json([
                    'resCode' => ResponseCode::VALIDATION_ERROR,
                    'resMsg'  => 'Validasi gagal',
                    'data'    => ['errors' => $e->errors()],
                ], 422);
            }

            // Auth
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'resCode' => ResponseCode::UNAUTHENTICATED,
                    'resMsg'  => 'Belum login atau session habis',
                ], 401);
            }
            if ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                return response()->json([
                    'resCode' => ResponseCode::FORBIDDEN,
                    'resMsg'  => $e->getMessage() ?: 'Anda tidak punya akses',
                ], 403);
            }

            // Not found
            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'resCode' => ResponseCode::NOT_FOUND,
                    'resMsg'  => 'Data tidak ditemukan',
                ], 404);
            }

            // HTTP exception generic
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'resCode' => ResponseCode::SERVER_ERROR,
                    'resMsg'  => $e->getMessage() ?: 'HTTP error',
                ], $e->getStatusCode());
            }

            // Fallback
            return response()->json([
                'resCode' => ResponseCode::SERVER_ERROR,
                'resMsg'  => config('app.debug') ? $e->getMessage() : 'Terjadi kesalahan internal',
            ], 500);
        });
    })->create();
