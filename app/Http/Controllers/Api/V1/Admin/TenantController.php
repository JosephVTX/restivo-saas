<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Admin\GrantAccessRequest;
use App\Http\Requests\Admin\StoreTenantRequest;
use App\Http\Requests\Admin\UpdateTenantRequest;
use App\Http\Resources\MembershipResource;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\Tenancy\AccessGranter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;

class TenantController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return TenantResource::collection($this->indexQuery(
            Tenant::class,
            $request,
            filters: [
                AllowedFilter::exact('status'),
                $this->searchFilter(['name']),
                AllowedFilter::partial('plan'),
            ],
            sorts: ['name', 'status', 'created_at'],
            withCount: ['memberships'],
        ));
    }

    /**
     * Create a tenant and grant its owner access in one step. Registration is
     * closed; this is the only way a client gets a workspace.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->safe()->only(['name', 'slug', 'plan', 'locale', 'status']);
        $data['status'] ??= TenantStatus::Active;

        $tenant = Tenant::create($data);

        $access = app(AccessGranter::class)->grant(
            $tenant,
            $request->string('owner_name')->toString(),
            $request->string('owner_email')->toString(),
            'owner',
            $request->input('owner_password'),
        );

        return (new TenantResource($tenant))
            ->additional([
                'owner' => [
                    'name' => $access['user']->name,
                    'email' => $access['user']->email,
                ],
                'temporary_password' => $access['temporary_password'],
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant->update($request->validated());

        return new TenantResource($tenant);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json(null, 204);
    }

    /**
     * Grant an existing user (or a brand new one) access to a tenant.
     */
    public function grantAccess(GrantAccessRequest $request, Tenant $tenant): JsonResponse
    {
        $email = $request->string('email')->toString();

        $access = app(AccessGranter::class)->grant(
            $tenant,
            $request->input('name') ?? Str::before($email, '@'),
            $email,
            $request->string('role')->toString(),
            $request->input('password'),
        );

        return response()->json([
            'data' => (new MembershipResource($access['membership']->load('user')))->resolve(),
            'temporary_password' => $access['temporary_password'],
        ], 201);
    }
}
