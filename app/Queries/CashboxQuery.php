<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Cashbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CashboxQuery
{
    /**
     * @return Builder<Cashbox>
     */
    public function forRequest(Request $request, User $user): Builder
    {
        $query = Cashbox::query()->with(['currency', 'user']);

        if (! ($request->string('scope')->toString() === 'all' && $user->isSuperAdmin())) {
            $query->visibleFor($user);
        }

        if ($request->filled('currency_id')) {
            $query->where('currency_id', $request->integer('currency_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        return $query->orderBy('name');
    }
}
