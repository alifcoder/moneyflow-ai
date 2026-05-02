<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CashboxStoreRequest;
use App\Http\Requests\CashboxUpdateRequest;
use App\Models\Cashbox;
use App\Models\Currency;
use App\Queries\CashboxQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CashboxController extends Controller
{
    public function index(Request $request, CashboxQuery $cashboxes): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Cashbox::class);

        return Inertia::render('Cashboxes/Index', [
            'cashboxes' => $cashboxes->forRequest($request, $user)
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Cashbox $cashbox): array => [
                    'id' => $cashbox->id,
                    'name' => $cashbox->name,
                    'currency_id' => $cashbox->currency_id,
                    'currency' => [
                        'code' => $cashbox->currency->code,
                        'name' => $cashbox->currency->name,
                        'symbol' => $cashbox->currency->symbol,
                    ],
                    'is_default' => $cashbox->is_default,
                    'enabled' => $cashbox->enabled,
                    'owner' => $this->ownerLabel($cashbox->user_id, $cashbox->user?->name, $user->id),
                    'can_update' => $user->can('update', $cashbox),
                    'can_delete' => $user->can('delete', $cashbox),
                ]),
            'currencies' => $this->currencyOptions($request)->map(fn (Currency $currency): array => [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
            ]),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'currency_id' => $request->input('currency_id'),
                'enabled' => $request->input('enabled'),
                'scope' => $user->isSuperAdmin() ? $request->string('scope')->toString() : '',
            ],
            'canUseAllScope' => $user->isSuperAdmin(),
        ]);
    }

    public function store(CashboxStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $this->userIdForNewReference($request);

        unset($data['owner_scope']);

        Cashbox::query()->create($data);

        return back();
    }

    public function update(CashboxUpdateRequest $request, Cashbox $cashbox): RedirectResponse
    {
        $cashbox->update($request->validated());

        return back();
    }

    public function destroy(Request $request, Cashbox $cashbox): RedirectResponse
    {
        Gate::authorize('delete', $cashbox);

        $cashbox->delete();

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

    /**
     * @return Collection<int, Currency>
     */
    private function currencyOptions(Request $request): Collection
    {
        $query = Currency::query()->enabled();

        if (! ($request->string('scope')->toString() === 'all' && $request->user()->isSuperAdmin())) {
            $query->visibleFor($request->user());
        }

        return $query->orderBy('code')->get();
    }
}
