<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionTypeEnum;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\TransactionQuery;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request, TransactionQuery $transactions): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Transaction::class);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions->forRequest($request, $user)
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Transaction $transaction): array => $this->transactionPayload($transaction, $user)),
            'filters' => [
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'type' => $request->string('type')->toString(),
                'category_id' => $request->input('category_id'),
                'currency_id' => $request->input('currency_id'),
                'cashbox_id' => $request->input('cashbox_id'),
                'search' => $request->string('search')->toString(),
                'scope' => $user->isSuperAdmin() ? $request->string('scope')->toString() : '',
            ],
            'options' => $this->options($request),
            'types' => $this->types(),
            'canUseAllScope' => $user->isSuperAdmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Transaction::class);

        return Inertia::render('Transactions/Form', [
            'mode' => 'create',
            'transaction' => null,
            'options' => $this->options($request),
            'types' => $this->types(),
        ]);
    }

    public function store(TransactionStoreRequest $request, TransactionService $transactions): RedirectResponse
    {
        $transactions->create($request->user(), $request->validated());

        return redirect()->route('transactions.index');
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('view', $transaction);

        $transaction->load(['cashbox', 'currency', 'category']);

        return Inertia::render('Transactions/Form', [
            'mode' => 'edit',
            'transaction' => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'type' => $transaction->type->value,
                'category_id' => $transaction->category_id,
                'cashbox_id' => $transaction->cashbox_id,
                'currency_id' => $transaction->currency_id,
                'amount' => $transaction->amount,
                'comment' => $transaction->comment,
            ],
            'options' => $this->options($request),
            'types' => $this->types(),
        ]);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $transactions->update($request->user(), $transaction, $request->validated());

        return redirect()->route('transactions.index');
    }

    public function destroy(Request $request, Transaction $transaction, TransactionService $transactions): RedirectResponse
    {
        $transactions->delete($request->user(), $transaction);

        return redirect()->route('transactions.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(Request $request): array
    {
        $user = $request->user();
        $allScope = $request->string('scope')->toString() === 'all' && $user->isSuperAdmin();

        $currencies = Currency::query()->enabled();
        $categories = Category::query()->enabled();
        $cashboxes = Cashbox::query()->enabled()->with('currency');

        if (! $allScope) {
            $currencies->visibleFor($user);
            $categories->visibleFor($user);
            $cashboxes->visibleFor($user);
        }

        return [
            'currencies' => $currencies->orderBy('code')->get()->map(fn (Currency $currency): array => [
                'id' => $currency->id,
                'code' => $currency->code,
                'name' => $currency->name,
            ]),
            'categories' => $categories->orderBy('type')->orderBy('name')->get()->map(fn (Category $category): array => [
                'id' => $category->id,
                'type' => $category->type->value,
                'name' => $category->name,
            ]),
            'cashboxes' => $cashboxes->orderBy('name')->get()->map(fn (Cashbox $cashbox): array => [
                'id' => $cashbox->id,
                'name' => $cashbox->name,
                'currency_id' => $cashbox->currency_id,
                'currency_code' => $cashbox->currency->code,
            ]),
        ];
    }

    /**
     * @return list<string>
     */
    private function types(): array
    {
        return [
            TransactionTypeEnum::INCOME->value,
            TransactionTypeEnum::EXPENSE->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionPayload(Transaction $transaction, User $user): array
    {
        return [
            'id' => $transaction->id,
            'transaction_date' => $transaction->transaction_date->toDateString(),
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'comment' => $transaction->comment,
            'category' => [
                'id' => $transaction->category_id,
                'name' => $transaction->category->name,
            ],
            'cashbox' => [
                'id' => $transaction->cashbox_id,
                'name' => $transaction->cashbox->name,
            ],
            'currency' => [
                'id' => $transaction->currency_id,
                'code' => $transaction->currency->code,
            ],
            'owner' => $transaction->user_id === $user->id ? 'own' : ($transaction->user->name ?? 'user'),
            'can_update' => $user->can('update', $transaction),
            'can_delete' => $user->can('delete', $transaction),
        ];
    }
}
