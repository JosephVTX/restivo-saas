<?php

namespace App\Http\Controllers\App;

use App\Enums\CashMovementType;
use App\Enums\CashSessionStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CashController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Cash/Index', [
            'paymentMethodOptions' => enum_options(PaymentMethod::class),
            'cashMovementTypeOptions' => enum_options(CashMovementType::class),
            'cashSessionStatusOptions' => enum_options(CashSessionStatus::class),
        ]);
    }
}
