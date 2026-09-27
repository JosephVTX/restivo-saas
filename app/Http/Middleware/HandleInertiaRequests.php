<?php

namespace App\Http\Middleware;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Encrypt the page data that Inertia stores in the browser history for
     * signed-in users, so privileged pages can't be replayed with the back
     * button after logging out. Only enabled in secure contexts because the
     * Web Crypto API is unavailable over plain HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::encryptHistory($this->shouldEncryptHistory($request));

        return parent::handle($request, $next);
    }

    private function shouldEncryptHistory(Request $request): bool
    {
        if ($request->user() === null) {
            return false;
        }

        return $request->secure() || in_array($request->getHost(), ['localhost', '127.0.0.1'], true);
    }

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $context = tenant_context();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? (new UserResource($user))->resolve() : null,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $context->has() && $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
            'tenant' => $context->has() ? (new TenantResource($context->tenant()))->resolve() : null,
            'tenants' => $user && ! $user->isSuperAdmin()
                ? $user->tenants()->get(['tenants.uuid', 'tenants.name', 'tenants.slug'])->map(fn ($tenant) => [
                    'uuid' => $tenant->uuid,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                ])->all()
                : [],
            'flash' => [
                'success' => fn () => $request->hasSession() ? $request->session()->get('success') : null,
                'error' => fn () => $request->hasSession() ? $request->session()->get('error') : null,
            ],
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
            ],
        ];
    }
}
