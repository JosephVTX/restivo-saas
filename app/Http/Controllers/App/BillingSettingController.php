<?php

namespace App\Http\Controllers\App;

use App\Enums\BillingMode;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BillingSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('app/Billing/Settings', [
            'billingModeOptions' => enum_options(BillingMode::class),
        ]);
    }
}
