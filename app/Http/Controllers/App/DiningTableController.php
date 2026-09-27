<?php

namespace App\Http\Controllers\App;

use App\Enums\TableStatus;
use App\Http\Controllers\Controller;
use App\Models\Zone;
use Inertia\Inertia;
use Inertia\Response;

class DiningTableController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Tables/Index', [
            'statuses' => enum_options(TableStatus::class),
            'zones' => Zone::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['uuid', 'name'])
                ->map(fn (Zone $zone): array => ['uuid' => $zone->uuid, 'name' => $zone->name])
                ->all(),
        ]);
    }
}
