<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // =========================
        // 1) Alias middleware custom
        // =========================
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // ==========================================
        // 2) Middleware group "web" (untuk semua web)
        // ==========================================
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\LogVisit::class,
        ]);

        // ======================================================
        // 3) CSRF EXCEPT (WAJIB) untuk Midtrans Webhook & Testing
        // ======================================================
        // Ini yang paling aman untuk menghindari 419 di webhook.
        $middleware->validateCsrfTokens(except: [
            'webhook/midtrans',
            'webhook/midtrans/*',
            'webhook/midtrans-test',
            'webhook/midtrans-test/*',
            'midtrans/notification',
            'midtrans/notification/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
