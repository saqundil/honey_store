<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\AuthenticateAdminPanel;
use App\Http\Middleware\EnsurePanelRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->validateCsrfTokens(except: [
            'school/*',
        ]);

        $middleware->alias([
            'locale' => SetLocale::class,
            'panel.auth' => AuthenticateAdminPanel::class,
            'panel.role' => EnsurePanelRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
