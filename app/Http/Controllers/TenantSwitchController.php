<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tenant' => ['nullable', 'string'],
        ]);

        $sessionKey = (string) config('tenancy.session_key', 'tenant_id');
        $user = $request->user();

        if (empty($data['tenant'])) {
            if (! $user->isSuperAdmin()) {
                abort(403);
            }

            $request->session()->forget($sessionKey);

            return back();
        }

        $tenant = Tenant::query()->where('uuid', $data['tenant'])->firstOrFail();

        if (! $user->isSuperAdmin() && ! $user->belongsToTenant($tenant->getKey())) {
            abort(403);
        }

        $request->session()->put($sessionKey, $tenant->getKey());

        return back();
    }
}
