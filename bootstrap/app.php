<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        api: __DIR__.'/../routes/api.php',  // Add this line for API routes
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add this line to register TrustProxies middleware
        $middleware->prependToGlobalMiddleware(\App\Http\Middleware\TrustProxies::class);
        
        // Register route middleware
        $middleware->alias([
            'role.company' => \App\Http\Middleware\CheckRoleAndCompanyType::class,
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
