<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\AuthenticatePartnerApi;
use App\Http\Middleware\AuditApiUsage;
use App\Http\Middleware\CheckSessionTimeout;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.partner' => AuthenticatePartnerApi::class,
            'audit.api' => AuditApiUsage::class,
            'session.timeout' => CheckSessionTimeout::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => 'VALIDATION_ERROR',
                'message' => 'The given data was invalid.',
                'details' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'error_code' => strtoupper(str_replace(' ', '_', $exception->getMessage() ?: 'HTTP_ERROR')),
                'message' => $exception->getMessage() ?: 'Request failed.',
                'details' => [],
            ], $exception->getStatusCode());
        });
    })->create();
