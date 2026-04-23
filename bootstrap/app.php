<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Apply the haykal-core locale + permissions-team middlewares to
        // every request so authenticated users see content in their
        // preferred locale and Spatie permission queries are scoped to
        // the active tenant automatically.
        $middleware->appendToGroup('web', [
            'haykal.user.locale',
            'haykal.permissions.team',
        ]);

        $middleware->appendToGroup('api', [
            'haykal.user.locale',
            'haykal.permissions.team',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
