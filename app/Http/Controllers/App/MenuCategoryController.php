<?php

namespace App\Http\Controllers\App;

use App\Enums\Station;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MenuCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Menu/Categories', [
            'stations' => enum_options(Station::class),
        ]);
    }
}
