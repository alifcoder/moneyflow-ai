<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function impersonate(User $user, User $target): bool
    {
        return $user->isSuperAdmin()
            && $target->isUser()
            && ! $user->is($target);
    }
}
