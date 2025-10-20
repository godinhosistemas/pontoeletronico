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
        $middleware->alias([
            'check.subscription' => \App\Http\Middleware\CheckTenantSubscription::class,
            'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'log.auth' => \App\Http\Middleware\LogAuthUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tratamento personalizado para erro de CSRF token expirado
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sua sessão expirou. Por favor, faça login novamente.'], 419);
            }

            return redirect()->route('login')
                ->withErrors(['error' => 'Sua sessão expirou. Por favor, faça login novamente.']);
        });
    })->create();
