<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            // Spatie Permission Middlewares
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            // Basic Auth Middleware
            'basic.auth' => \App\Http\Middleware\BasicAuthMiddleware::class,
            // Coming Soon Middleware
            'coming-soon' => \App\Http\Middleware\ComingSoonMiddleware::class,
            // Company IP Restriction Middleware
            'company.ip' => \App\Http\Middleware\CompanyIpMiddleware::class,
            // Season Intro Middleware
            'season.intro' => \App\Http\Middleware\SeasonIntroMiddleware::class,
            // Secure ID Middleware

            // Secure Route Middleware - تحويل تلقائي للـ secure IDs
            'secure.route' => \App\Http\Middleware\SecureRouteMiddleware::class, // معطل مؤقتاً
        ]);

        // إضافة middleware القرارات الإدارية والـ secure routes
        $middleware->web([
            \App\Http\Middleware\CheckUnreadAdministrativeDecisions::class,
            \App\Http\Middleware\SeasonIntroMiddleware::class, // عرض مقدمة السيزون الجديد
            // تطبيق SecureRouteMiddleware على كل الـ web routes

            \App\Http\Middleware\SecureRouteMiddleware::class, // معطل مؤقتاً
        ]);    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
