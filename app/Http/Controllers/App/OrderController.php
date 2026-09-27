<?php

namespace App\Http\Controllers\App;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/Orders/Index', [
            'orderStatusOptions' => enum_options(OrderStatus::class),
            'orderTypeOptions' => enum_options(OrderType::class),
            'paymentMethodOptions' => enum_options(PaymentMethod::class),
        ]);
    }

    public function pos(): Response
    {
        return Inertia::render('app/Pos/Index', [
            'orderTypeOptions' => enum_options(OrderType::class),
            'paymentMethodOptions' => enum_options(PaymentMethod::class),
        ]);
    }
}
