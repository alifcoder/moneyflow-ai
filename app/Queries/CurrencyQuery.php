<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CurrencyQuery
{
    /**
     * @return Builder<Currency>
     */
    public function forRequest(Request $request, User $user): Builder
    {
        $query = Currency::query()->with('user');

        if (! ($request->string('scope')->toString() === 'all' && $user->isSuperAdmin())) {
            $query->visibleFor($user);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function (Builder $query) use ($search): void {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($request->filled('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        return $query->orderBy('code')->orderBy('name');
    }
}
