<?php

namespace App\Policies;

use App\Models\OrderItem;
use App\Models\User;

/**
 * Order line authorization. Waiters write through `orders.update`; the kitchen
 * advances lines through `kitchen.update`.
 */
class OrderItemPolicy
{
    public function update(User $user, OrderItem $item): bool
    {
        return $user->can('orders.update');
    }

    public function advance(User $user, OrderItem $item): bool
    {
        return $user->can('orders.update') || $user->can('kitchen.update');
    }
}
