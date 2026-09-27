<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;

class UserController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->indexQuery(
            User::class,
            $request,
            filters: [
                AllowedFilter::exact('is_super_admin'),
                $this->searchFilter(['name', 'email']),
            ],
            sorts: ['name', 'email', 'created_at', 'last_login_at'],
            withCount: ['memberships'],
        ));
    }
}
