<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function dashboard(Request $request, User $user): array
    {
        $query = $this->baseQuery($request, $user);

        return [
            'totals' => $this->totals(clone $query),
            'recentTransactions' => $this->recentTransactions(clone $query),
            'cashboxBalances' => $this->cashboxBreakdown(clone $query),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reports(Request $request, User $user): array
    {
        $query = $this->baseQuery($request, $user);

        return [
            'totals' => $this->totals(clone $query),
            'monthly' => $this->monthlyIncomeExpense(clone $query),
            'byCategory' => $this->categoryBreakdown(clone $query),
            'byCurrency' => $this->currencyBreakdown(clone $query),
            'byCashbox' => $this->cashboxBreakdown(clone $query),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(Request $request, User $user): array
    {
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
     * @return array<string, string>
     */
    public function filters(Request $request, User $user): array
    {
        return [
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'currency_id' => (string) $request->input('currency_id', ''),
            'cashbox_id' => (string) $request->input('cashbox_id', ''),
            'type' => $request->string('type')->toString(),
            'scope' => $user->isSuperAdmin() ? $request->string('scope')->toString() : '',
        ];
    }

    /**
     * @return Builder<Transaction>
     */
    private function baseQuery(Request $request, User $user): Builder
    {
        $query = Transaction::query();

        if (! ($request->string('scope')->toString() === 'all' && $user->isSuperAdmin())) {
            $query->visibleFor($user);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transactions.transaction_date', '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transactions.transaction_date', '<=', $request->date('date_to')->toDateString());
        }

        if ($request->filled('currency_id')) {
            $query->where('transactions.currency_id', $request->integer('currency_id'));
        }

        if ($request->filled('cashbox_id')) {
            $query->where('transactions.cashbox_id', $request->integer('cashbox_id'));
        }

        if ($request->filled('type')) {
            $query->where('transactions.type', $request->string('type')->toString());
        }

        return $query;
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return array<string, float>
     */
    private function totals(Builder $query): array
    {
        $row = $query
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as income', [TransactionTypeEnum::INCOME->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as expense', [TransactionTypeEnum::EXPENSE->value])
            ->first();

        $income = (float) ($row?->income ?? 0);
        $expense = (float) ($row?->expense ?? 0);

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
        ];
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function recentTransactions(Builder $query): Collection
    {
        return $query
            ->with(['category', 'cashbox', 'currency'])
            ->orderByDesc('transactions.transaction_date')
            ->orderByDesc('transactions.id')
            ->limit(8)
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'id' => $transaction->id,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'type' => $transaction->type->value,
                'amount' => (float) $transaction->amount,
                'comment' => $transaction->comment,
                'category' => $transaction->category->name,
                'cashbox' => $transaction->cashbox->name,
                'currency' => $transaction->currency->code,
            ]);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function monthlyIncomeExpense(Builder $query): Collection
    {
        $monthExpression = $this->monthExpression();

        return $query
            ->selectRaw($monthExpression.' as month')
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as income', [TransactionTypeEnum::INCOME->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as expense', [TransactionTypeEnum::EXPENSE->value])
            ->groupBy(DB::raw($monthExpression))
            ->orderBy('month')
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->month,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
            ]);
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function categoryBreakdown(Builder $query): Collection
    {
        return $this->typedBreakdown(
            $query->join('categories', 'categories.id', '=', 'transactions.category_id'),
            'categories.name',
        );
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function currencyBreakdown(Builder $query): Collection
    {
        return $this->typedBreakdown(
            $query->join('currencies', 'currencies.id', '=', 'transactions.currency_id'),
            'currencies.code',
        );
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function cashboxBreakdown(Builder $query): Collection
    {
        return $this->typedBreakdown(
            $query->join('cashboxes', 'cashboxes.id', '=', 'transactions.cashbox_id'),
            'cashboxes.name',
        );
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return Collection<int, array<string, mixed>>
     */
    private function typedBreakdown(Builder $query, string $labelColumn): Collection
    {
        return $query
            ->selectRaw($labelColumn.' as label')
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as income', [TransactionTypeEnum::INCOME->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN transactions.type = ? THEN transactions.amount ELSE 0 END), 0) as expense', [TransactionTypeEnum::EXPENSE->value])
            ->groupBy(DB::raw($labelColumn))
            ->orderBy('label')
            ->get()
            ->map(function ($row): array {
                $income = (float) $row->income;
                $expense = (float) $row->expense;

                return [
                    'label' => (string) $row->label,
                    'income' => $income,
                    'expense' => $expense,
                    'net' => $income - $expense,
                ];
            });
    }

    private function monthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "to_char(date_trunc('month', transactions.transaction_date), 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', transactions.transaction_date)",
            default => "date_format(transactions.transaction_date, '%Y-%m')",
        };
    }
}
