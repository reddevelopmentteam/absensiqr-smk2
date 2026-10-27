<?php

use App\Http\Middleware\AccessMiddleware;
use App\Http\Middleware\RefreshRememberCookie;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\TestTime;
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
