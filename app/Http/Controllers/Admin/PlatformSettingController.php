<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlatformSettingResource;
use App\Models\PlatformSetting;
use Inertia\Inertia;
use Inertia\Response;

class PlatformSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/Settings/Integrations', [
            'settings' => (new PlatformSettingResource(PlatformSetting::current()))->resolve(),
        ]);
    }
}
