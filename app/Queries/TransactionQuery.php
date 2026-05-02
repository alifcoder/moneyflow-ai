<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TransactionQuery
{
    /**
     * @return Builder<Transaction>
     */
    public function forRequest(Request $request, User $user): Builder
    {
        $query = Transaction::query()->with(['user', 'cashbox', 'currency', 'category']);

        if (! ($request->string('scope')->toString() === 'all' && $user->isSuperAdmin())) {
            $query->visibleFor($user);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('cashbox_id')) {
            $query->where('cashbox_id', $request->integer('cashbox_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date('date_to')->toDateString());
        }

        return $query->orderByDesc('transaction_date')->orderByDesc('id');
    }
}
