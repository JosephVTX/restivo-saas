<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

/**
 * Permission names are checked against the active tenant (spatie teams).
 * Super admins bypass every check through the Gate::before hook.
 */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    public function annul(User $user, Document $document): bool
    {
        return $user->can('documents.create');
    }

    public function delete(User $user, Document $document): bool
    {
        return false;
    }
}
