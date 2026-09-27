<?php

namespace App\Http\Controllers\App;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Projects/Index', [
            'statuses' => enum_options(ProjectStatus::class),
        ]);
    }
}
