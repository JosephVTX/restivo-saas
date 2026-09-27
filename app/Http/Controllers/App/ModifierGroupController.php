<?php

namespace App\Http\Controllers\App;

use App\Enums\ModifierSelectionType;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ModifierGroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Modifiers/Index', [
            'selection_types' => enum_options(ModifierSelectionType::class),
        ]);
    }
}
