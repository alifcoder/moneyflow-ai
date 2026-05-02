<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\TransactionTypeEnum;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_index_loads(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);
        $transaction = $this->transactionFor($user, $currency, $category, $cashbox);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transactions/Index')
                ->where('transactions.data.0.id', $transaction->id));
    }

    public function test_create_page_loads_with_visible_references(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$globalCurrency, $globalCategory, $globalCashbox] = $this->globalReferences(TransactionTypeEnum::INCOME);
        [$ownCurrency, $ownCategory, $ownCashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);
        [$otherCurrency, $otherCategory, $otherCashbox] = $this->referencesFor($otherUser, TransactionTypeEnum::EXPENSE);

        $response = $this->actingAs($user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Transactions/Form'));

        $currencyIds = collect($response->inertiaProps('options.currencies'))->pluck('id');
        $categoryIds = collect($response->inertiaProps('options.categories'))->pluck('id');
        $cashboxIds = collect($response->inertiaProps('options.cashboxes'))->pluck('id');

        $this->assertContains($globalCurrency->id, $currencyIds);
        $this->assertContains($ownCurrency->id, $currencyIds);
        $this->assertNotContains($otherCurrency->id, $currencyIds);
        $this->assertContains($globalCategory->id, $categoryIds);
        $this->assertContains($ownCategory->id, $categoryIds);
        $this->assertNotContains($otherCategory->id, $categoryIds);
        $this->assertContains($globalCashbox->id, $cashboxIds);
        $this->assertContains($ownCashbox->id, $cashboxIds);
        $this->assertNotContains($otherCashbox->id, $cashboxIds);
    }

    public function test_store_works(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-05-02',
                'type' => TransactionTypeEnum::EXPENSE->value,
                'category_id' => $category->id,
                'cashbox_id' => $cashbox->id,
                'currency_id' => $currency->id,
                'amount' => '120.5000',
                'comment' => 'Office supplies',
            ])
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '120.5000',
            'comment' => 'Office supplies',
        ]);
    }

    public function test_edit_page_loads(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::INCOME);
        $transaction = $this->transactionFor($user, $currency, $category, $cashbox);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Transactions/Form')
                ->where('mode', 'edit')
                ->where('transaction.id', $transaction->id));
    }

    public function test_update_works(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::INCOME);
        $transaction = $this->transactionFor($user, $currency, $category, $cashbox);

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-05-03',
                'type' => TransactionTypeEnum::INCOME->value,
                'category_id' => $category->id,
                'cashbox_id' => $cashbox->id,
                'currency_id' => $currency->id,
                'amount' => '999.9900',
                'comment' => 'Updated transaction',
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction->refresh();

        $this->assertSame('2026-05-03', $transaction->transaction_date->toDateString());
        $this->assertSame('999.9900', $transaction->amount);
        $this->assertSame('Updated transaction', $transaction->comment);
    }

    public function test_delete_works(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);
        $transaction = $this->transactionFor($user, $currency, $category, $cashbox);

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_access_another_users_transaction_pages(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($otherUser, TransactionTypeEnum::EXPENSE);
        $transaction = $this->transactionFor($otherUser, $currency, $category, $cashbox);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-05-03',
                'type' => TransactionTypeEnum::EXPENSE->value,
                'category_id' => $category->id,
                'cashbox_id' => $cashbox->id,
                'currency_id' => $currency->id,
                'amount' => '10',
                'comment' => 'Blocked',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $response = $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk();

        $this->assertSame([], $response->inertiaProps('transactions.data'));
    }

    /**
     * @return array{Currency, Category, Cashbox}
     */
    private function referencesFor(User $user, TransactionTypeEnum $type): array
    {
        $currency = Currency::query()->create([
            'user_id' => $user->id,
            'code' => fake()->unique()->lexify('???'),
            'name' => fake()->currencyCode(),
        ]);

        $category = Category::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'name' => fake()->words(2, true),
        ]);

        $cashbox = Cashbox::query()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'name' => fake()->words(2, true),
        ]);

        return [$currency, $category, $cashbox];
    }

    /**
     * @return array{Currency, Category, Cashbox}
     */
    private function globalReferences(TransactionTypeEnum $type): array
    {
        $currency = Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);

        $category = Category::query()->create([
            'type' => $type,
            'name' => 'Global Category',
        ]);

        $cashbox = Cashbox::query()->create([
            'currency_id' => $currency->id,
            'name' => 'Global Cashbox',
        ]);

        return [$currency, $category, $cashbox];
    }

    private function transactionFor(User $user, Currency $currency, Category $category, Cashbox $cashbox): Transaction
    {
        return Transaction::query()->create([
            'user_id' => $user->id,
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => $category->type,
            'amount' => '50',
            'transaction_date' => '2026-05-02',
            'comment' => 'Seeded transaction',
        ]);
    }
}
