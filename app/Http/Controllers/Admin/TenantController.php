<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanDuration;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
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

    public function enter(Tenant $tenant): RedirectResponse
    {
        if (! $tenant->isActive()) {
            return back()->with('error', 'Este espacio de trabajo no está activo.');
        }

        session([config('tenancy.session_key', 'tenant_id') => $tenant->getKey()]);

        return redirect()->route('app.dashboard');
    }

    public function leave(): RedirectResponse
    {
        session()->forget(config('tenancy.session_key', 'tenant_id'));

        return redirect()->route('admin.dashboard');
    }
}
