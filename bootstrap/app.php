<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenant;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.resolve' => ResolveTenant::class,
            'tenant' => EnsureTenant::class,
            'super.admin' => EnsureSuperAdmin::class,
        ]);

        $middleware->throttleApi();

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error('Token has expired.', 401, ['code' => 'token_expired']);
            }
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error('Token is invalid.', 401, ['code' => 'token_invalid']);
            }
        });

        $exceptions->render(function (JWTException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error('Token could not be parsed.', 401, ['code' => 'token_absent']);
            }
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error($e->getMessage(), 422, [
                    'code' => 'validation_error',
                    'errors' => $e->errors(),
                ]);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error('Unauthenticated.', 401, ['code' => 'unauthenticated']);
            }
        });

        $exceptions->render(function (InvalidQuery $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error($e->getMessage(), 400, ['code' => 'invalid_query']);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error('Resource not found.', 404, ['code' => 'not_found']);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return api_error($e->getMessage() ?: 'Request failed.', $e->getStatusCode());
            }
        });
    })->create();
