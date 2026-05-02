<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionTypeEnum;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use App\Queries\CategoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryQuery $categories): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Category::class);

        return Inertia::render('Categories/Index', [
            'categories' => $categories->forRequest($request, $user)
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Category $category): array => [
                    'id' => $category->id,
                    'type' => $category->type->value,
                    'name' => $category->name,
                    'is_default' => $category->is_default,
                    'enabled' => $category->enabled,
                    'owner' => $this->ownerLabel($category->user_id, $category->user?->name, $user->id),
                    'can_update' => $user->can('update', $category),
                    'can_delete' => $user->can('delete', $category),
                ]),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString(),
                'enabled' => $request->input('enabled'),
                'scope' => $user->isSuperAdmin() ? $request->string('scope')->toString() : '',
            ],
            'types' => [
                TransactionTypeEnum::INCOME->value,
                TransactionTypeEnum::EXPENSE->value,
            ],
            'canUseAllScope' => $user->isSuperAdmin(),
        ]);
    }

    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $this->userIdForNewReference($request);

        unset($data['owner_scope']);

        Category::query()->create($data);

        return back();
    }

    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $category->delete();

        return back();
    }

    private function userIdForNewReference(Request $request): ?int
    {
        if ($request->user()->isSuperAdmin() && $request->string('owner_scope')->toString() === 'global') {
            return null;
        }

        return $request->user()->id;
    }

    private function ownerLabel(?int $userId, ?string $ownerName, int $currentUserId): string
    {
        if ($userId === null) {
            return 'global';
        }

        return $userId === $currentUserId ? 'own' : ($ownerName ?? 'user');
    }
}
