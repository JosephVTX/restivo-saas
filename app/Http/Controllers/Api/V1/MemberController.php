<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\App\StoreMemberRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Membership;
use App\Models\User;
use App\Services\Tenancy\AccessGranter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class MemberController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $members = $this->indexQuery(
            Membership::class,
            $request,
            with: ['user.roles'],
        );

        return MembershipResource::collection($members)->response();
    }

    public function store(StoreMemberRequest $request): JsonResponse
    {
        $tenant = tenant_context()->require();
        $email = $request->string('email')->toString();

        if (User::query()->where('email', $email)->first()?->belongsToTenant($tenant->getKey())) {
            abort(422, 'Este usuario ya es miembro del espacio de trabajo.');
        }

        $access = app(AccessGranter::class)->grant(
            $tenant,
            '',
            $email,
            $request->string('role')->toString(),
            jobTitle: $request->input('job_title'),
        );

        return (new MembershipResource($access['membership']->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(string $member): JsonResponse
    {
        $tenant = tenant_context()->require();
        $membership = $this->findByUuid(Membership::class, $member);

        if ((int) $membership->user_id === (int) request()->user()->getKey()) {
            abort(422, 'No puedes eliminarte a ti mismo del espacio de trabajo.');
        }

        $user = $membership->user;
        $membership->delete();

        if ($user !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
            $user->syncRoles([]);
        }

        return response()->json(null, 204);
    }
}
