<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanDuration;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Tenants', [
            'statuses' => enum_options(TenantStatus::class),
            'durations' => array_map(
                fn (PlanDuration $duration): array => [
                    'value' => $duration->value,
                    'label' => $duration->label(),
                    'is_trial' => $duration->isTrial(),
                ],
                PlanDuration::cases(),
            ),
        ]);
    }
}
