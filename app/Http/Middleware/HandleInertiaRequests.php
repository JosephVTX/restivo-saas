<?php

namespace App\Http\Middleware;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

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
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
            ],
        ];
    }
}
