<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Octane can't run on Windows (it requires the PCNTL signal constants),
        // so `php artisan dev` uses the built-in PHP server there instead. The
        // userland registration overrides the one Octane registers (vendor).
        if (PHP_OS_FAMILY === 'Windows' && class_exists(DevCommands::class)) {
            DevCommands::artisan('serve', 'server');
        }

        // Behind the Dokploy/Traefik TLS terminator the forwarded scheme is not
        // reliable, so force https for every generated URL in production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response): ?ExceptionResponse {
            if ($response->request->is('api/*')) {
                return null;
            }

            $status = $response->statusCode();
            $alwaysRender = in_array($status, [403, 404, 419, 429], true);
            $productionOnly = in_array($status, [500, 503], true) && app()->environment('production');

            if ($alwaysRender || $productionOnly) {
                return $response
                    ->render('ErrorPage', ['status' => $status])
                    ->withSharedData();
            }

            return null;
        });
    }
}
