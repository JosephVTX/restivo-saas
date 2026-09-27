<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends ApiController
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->forCurrentTenant()
            ->with('permissions')
            ->get();

        return RoleResource::collection($roles)->response();
    }
}
