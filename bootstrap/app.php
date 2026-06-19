<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API routes must always answer with JSON. Without this, an
        // unauthenticated browser hit on /api/* (e.g. a file download that
        // doesn't carry the bearer token) makes Laravel try to redirect to a
        // non-existent `login` route → RouteNotFoundException → HTTP 500.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Explicit, definitive guard: an unauthenticated API request always
        // gets a clean 401 JSON response (never a redirect to a login route).
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
        });
    })->create();
