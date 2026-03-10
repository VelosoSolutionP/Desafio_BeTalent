<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found'], 404);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        });
    })->create();