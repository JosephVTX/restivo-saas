<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestTerminated;
use Spatie\Permission\PermissionRegistrar;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);

        $this->app->singleton(TenantResolver::class, function ($app): TenantResolver {
            return new TenantResolver(
                $app['cache']->store(config('tenancy.cache.store'))
            );
        });
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureAuthorization();
        $this->configureRateLimiting();
        $this->configureOctane();
    }

    private function configureModels(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
    }

    private function configureAuthorization(): void
    {
        Gate::before(function ($user) {
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin() ? true : null;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            // Runs before the tenant is resolved (Laravel middleware priority),
            // so key by the authenticated user (tenant-tier budget) or IP.
            $user = $request->user();

            if ($user !== null) {
                return Limit::perMinute((int) config('tenancy.rate_limit.tenant', 300))
                    ->by('user:'.$user->getAuthIdentifier());
            }

            return Limit::perMinute((int) config('tenancy.rate_limit.guest', 60))
                ->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) $request->input('email').'|'.$request->ip());
        });
    }

    private function configureOctane(): void
    {
        $this->app->make('events')->listen(RequestTerminated::class, function (): void {
            app(TenantContext::class)->clear();
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        });

        Log::withContext([]);
    }
}
