<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\UserRoleEnum;
use App\Http\Requests\UserStoreRequest;
use App\Models\User;
use App\Queries\UserQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request, UserQuery $users): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Users/Index', [
            'users' => $users->forRequest($request)
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $this->roleLabel($user->role),
                    'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                    'is_current_user' => $user->is($request->user()),
                    'can_impersonate' => $request->user()->can('impersonate', $user),
                ]),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'roles' => collect(UserRoleEnum::cases())->map(fn (UserRoleEnum $role): array => [
                'value' => $role->value,
                'label' => $this->roleLabel($role),
            ]),
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        User::query()->create([
            ...$request->validated(),
            'email_verified_at' => now(),
        ]);

        return back();
    }

    private function roleLabel(UserRoleEnum $role): string
    {
        return match ($role) {
            UserRoleEnum::USER => 'User',
            UserRoleEnum::SUPER_ADMIN => 'Super Admin',
        };
    }
}
