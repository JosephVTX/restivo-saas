<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cash.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->can('cash.view');
    }

    public function create(User $user): bool
    {
        return $user->can('payments.create');
    }
}
