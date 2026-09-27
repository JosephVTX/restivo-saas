<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenant;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ResolveTenant runs in the web group before HandleInertiaRequests so
        // the tenant context and the permission team id are resolved before
        // Inertia captures the shared props (auth.roles/permissions, tenant).
        // It still runs after StartSession, so it can read the session/tenant
        // and lazily resolve the authenticated user.
        $middleware->web(append: [
            ResolveTenant::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'tenant' => EnsureTenant::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
