<?php

use App\Http\Middleware\AccessMiddleware;
use App\Http\Middleware\RefreshRememberCookie;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TestTime;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sidebar state cookie is written raw via document.cookie (Alpine $watch),
        // so it must be excluded from encryption or request()->cookie() returns null.
        $middleware->encryptCookies(except: ['sidebar-collapsed']);
        $middleware->web(append: [
            TestTime::class,
            RefreshRememberCookie::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login-page'));
        $middleware->redirectUsersTo(fn () => route('home'));
        $middleware->alias([
            'access' => AccessMiddleware::class,
            'super-admin' => SuperAdminMiddleware::class,
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data' => null,
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token does not have the required library-read ability.',
                    'data' => null,
                ], 403);
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Validation failed.',
                    'data' => null,
                    'errors' => $exception->errors(),
                ], 422);
            }

            return null;
        });
    })->create();
