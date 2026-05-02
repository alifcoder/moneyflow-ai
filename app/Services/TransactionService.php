<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TransactionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Transaction
    {
        Gate::forUser($user)->authorize('create', Transaction::class);

        $data['user_id'] = $user->id;

        return Transaction::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, Transaction $transaction, array $data): Transaction
    {
        Gate::forUser($user)->authorize('update', $transaction);

        $transaction->update($data);

        return $transaction->refresh();
    }

    public function delete(User $user, Transaction $transaction): void
    {
        Gate::forUser($user)->authorize('delete', $transaction);

        $transaction->delete();
    }
}
