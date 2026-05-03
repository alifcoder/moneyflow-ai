<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $impersonatorId = $request->session()->get('impersonator_id');
        $impersonator = $impersonatorId ? User::query()->find($impersonatorId) : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'canManageUsers' => $user?->isSuperAdmin() ?? false,
                'impersonation' => [
                    'active' => $user !== null && $impersonator?->isSuperAdmin(),
                    'impersonator' => $impersonator ? [
                        'id' => $impersonator->id,
                        'name' => $impersonator->name,
                        'email' => $impersonator->email,
                    ] : null,
                ],
            ],
        ];
    }
}
