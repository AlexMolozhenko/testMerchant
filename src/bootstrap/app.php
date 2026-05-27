<?php

declare(strict_types=1);

use App\Shared\Exceptions\AppException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'merchant.auth' => \App\Application\Http\Middleware\Auth\MerchantAuthMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request): ?\Illuminate\Http\JsonResponse {
            if (! $request->expectsJson() && ! $request->is('api/*') && ! $request->is('merchant/*') && ! $request->is('internal/*') && ! $request->is('public/*')) {
                return null;
            }

            if ($e instanceof AppException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            if ($e instanceof HttpException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage() ?: 'HTTP error.',
                ], $e->getStatusCode());
            }

            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            return response()->json([
                'status'  => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Server error.',
            ], $status);
        });
    })->create();
