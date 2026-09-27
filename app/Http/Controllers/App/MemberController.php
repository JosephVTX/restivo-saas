<?php

namespace App\Http\Controllers\App;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MemberController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Members/Index', [
            'roles' => enum_options(Role::class),
        ]);
    }
}
