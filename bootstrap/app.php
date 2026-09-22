<?php

/**
 * Punto de configuración de la aplicación Laravel.
 * Define rutas, middleware web y alias de autorización.
 */

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\CheckSiteOffline::class,
            \App\Http\Middleware\ShareCreatorPlusFeatures::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\MarkUserOnline::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'admin.panel' => \App\Http\Middleware\EnsureAdminPanel::class,
            'staff' => \App\Http\Middleware\EnsureStaff::class,
            'not_banned' => \App\Http\Middleware\EnsureNotBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
