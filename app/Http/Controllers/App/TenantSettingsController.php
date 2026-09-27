<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\UpdateTenantSettingsRequest;
use App\Http\Resources\TenantResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TenantSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('app/Settings/Edit', [
            'tenant' => (new TenantResource(tenant_context()->require()))->resolve(),
        ]);
    }

    public function update(UpdateTenantSettingsRequest $request): RedirectResponse
    {
        $tenant = tenant_context()->require();

        $data = $request->safe()->only(['name', 'locale', 'settings']);

        if (isset($data['settings'])) {
            $tenant->settings = array_merge($tenant->settings ?? [], $data['settings']);
            unset($data['settings']);
        }

        $tenant->fill($data);
        $tenant->save();

        return back()->with('success', 'Configuración actualizada.');
    }
}
