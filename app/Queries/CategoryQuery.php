<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CategoryQuery
{
    /**
     * @return Builder<Category>
     */
    public function forRequest(Request $request, User $user): Builder
    {
        $query = Category::query()->with('user');

        if (! ($request->string('scope')->toString() === 'all' && $user->isSuperAdmin())) {
            $query->visibleFor($user);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('enabled')) {
            $query->where('enabled', $request->boolean('enabled'));
        }

        return $query->orderBy('type')->orderBy('name');
    }
}
