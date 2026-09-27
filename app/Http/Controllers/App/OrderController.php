<?php

namespace App\Http\Controllers\App;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Waiters get a focused, mobile-first view of their own orders; any
        // other role (owner, admin, cashier) keeps the full orders table.
        $isWaiter = $user !== null
            && $user->hasRole(Role::Waiter->value)
            && ! $user->hasAnyRole([Role::Owner->value, Role::Admin->value, Role::Cashier->value]);

        return Inertia::render($isWaiter ? 'app/Orders/Waiter' : 'app/Orders/Index', [
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
