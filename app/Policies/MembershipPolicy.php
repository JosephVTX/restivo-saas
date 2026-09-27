<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('members.view');
    }

    public function invite(User $user): bool
    {
        return $user->can('members.invite');
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $user->can('members.remove');
    }
}
