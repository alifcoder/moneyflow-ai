<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;

class CurrencyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Currency $currency): bool
    {
        return $user->isSuperAdmin()
            || $currency->user_id === null
            || $currency->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Currency $currency): bool
    {
        return $user->isSuperAdmin()
            || $currency->user_id === $user->id;
    }

    public function delete(User $user, Currency $currency): bool
    {
        return $this->update($user, $currency);
    }
}
