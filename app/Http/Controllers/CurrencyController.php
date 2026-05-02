<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyStoreRequest;
use App\Http\Requests\CurrencyUpdateRequest;
use App\Models\Currency;
use App\Queries\CurrencyQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function index(Request $request, CurrencyQuery $currencies): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Currency::class);

        return Inertia::render('Currencies/Index', [
            'currencies' => $currencies->forRequest($request, $user)
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Currency $currency): array => [
                    'id' => $currency->id,
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'symbol' => $currency->symbol,
                    'is_default' => $currency->is_default,
                    'enabled' => $currency->enabled,
                    'owner' => $this->ownerLabel($currency->user_id, $currency->user?->name, $user->id),
                    'can_update' => $user->can('update', $currency),
                    'can_delete' => $user->can('delete', $currency),
                ]),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'enabled' => $request->input('enabled'),
                'scope' => $user->isSuperAdmin() ? $request->string('scope')->toString() : '',
            ],
            'canUseAllScope' => $user->isSuperAdmin(),
        ]);
    }

    public function store(CurrencyStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = mb_strtoupper($data['code']);
        $data['user_id'] = $this->userIdForNewReference($request);

        unset($data['owner_scope']);

        Currency::query()->create($data);

        return back();
    }

    public function update(CurrencyUpdateRequest $request, Currency $currency): RedirectResponse
    {
        $data = $request->validated();
        $data['code'] = mb_strtoupper($data['code']);

        $currency->update($data);

        return back();
    }

    public function destroy(Request $request, Currency $currency): RedirectResponse
    {
        Gate::authorize('delete', $currency);

        $currency->delete();

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
