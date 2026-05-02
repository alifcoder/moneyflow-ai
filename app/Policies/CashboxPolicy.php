<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cashbox;
use App\Models\User;

class CashboxPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Cashbox $cashbox): bool
    {
        return $user->isSuperAdmin()
            || $cashbox->user_id === null
            || $cashbox->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Cashbox $cashbox): bool
    {
        return $user->isSuperAdmin()
            || $cashbox->user_id === $user->id;
    }

    public function delete(User $user, Cashbox $cashbox): bool
    {
        return $this->update($user, $cashbox);
    }
}
