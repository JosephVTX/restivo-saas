<?php

namespace App\Providers;

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
