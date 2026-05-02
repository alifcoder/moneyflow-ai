<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Http\Requests\TransactionStoreRequest;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Queries\TransactionQuery;
use App\Services\TransactionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_transaction_with_own_references(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);

        $transaction = $this->service()->create($user, $this->validatedStoreData($user, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '125.5000',
            'transaction_date' => '2026-05-02',
            'comment' => 'Groceries',
        ]));

        $this->assertSame($user->id, $transaction->user_id);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '125.5000',
        ]);
    }

    public function test_user_can_create_transaction_with_global_references(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->globalReferences(TransactionTypeEnum::INCOME);

        $transaction = $this->service()->create($user, $this->validatedStoreData($user, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::INCOME->value,
            'amount' => '1000',
            'transaction_date' => '2026-05-02',
        ]));

        $this->assertSame($user->id, $transaction->user_id);
        $this->assertSame(TransactionTypeEnum::INCOME, $transaction->type);
    }

    public function test_user_cannot_use_another_users_private_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($otherUser, TransactionTypeEnum::EXPENSE);

        $this->expectException(ValidationException::class);

        $this->validatedStoreData($user, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '25',
            'transaction_date' => '2026-05-02',
        ]);
    }

    public function test_category_type_must_match_transaction_type(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::INCOME);

        $this->expectException(ValidationException::class);

        $this->validatedStoreData($user, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '25',
            'transaction_date' => '2026-05-02',
        ]);
    }

    public function test_user_can_update_and_delete_only_own_transaction(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);
        [$otherCurrency, $otherCategory, $otherCashbox] = $this->referencesFor($otherUser, TransactionTypeEnum::EXPENSE);

        $ownTransaction = $this->transactionFor($user, $currency, $category, $cashbox);
        $otherTransaction = $this->transactionFor($otherUser, $otherCurrency, $otherCategory, $otherCashbox);

        $updated = $this->service()->update($user, $ownTransaction, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '75',
            'transaction_date' => '2026-05-03',
            'comment' => 'Updated',
        ]);

        $this->assertSame('75.0000', $updated->amount);
        $this->assertSame('Updated', $updated->comment);

        try {
            $this->service()->update($user, $otherTransaction, [
                'cashbox_id' => $otherCashbox->id,
                'currency_id' => $otherCurrency->id,
                'category_id' => $otherCategory->id,
                'type' => TransactionTypeEnum::EXPENSE->value,
                'amount' => '80',
                'transaction_date' => '2026-05-03',
            ]);

            $this->fail('Expected authorization failure when updating another user transaction.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('transactions', [
                'id' => $otherTransaction->id,
                'amount' => '50.0000',
            ]);
        }

        $this->expectException(AuthorizationException::class);
        $this->service()->delete($user, $otherTransaction);
    }

    public function test_user_can_delete_own_transaction(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);
        $transaction = $this->transactionFor($user, $currency, $category, $cashbox);

        $this->service()->delete($user, $transaction);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_super_admin_scope_all_can_see_all_transactions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();
        [$adminCurrency, $adminCategory, $adminCashbox] = $this->referencesFor($superAdmin, TransactionTypeEnum::INCOME);
        [$otherCurrency, $otherCategory, $otherCashbox] = $this->referencesFor($otherUser, TransactionTypeEnum::EXPENSE);

        $adminTransaction = $this->transactionFor($superAdmin, $adminCurrency, $adminCategory, $adminCashbox);
        $otherTransaction = $this->transactionFor($otherUser, $otherCurrency, $otherCategory, $otherCashbox);

        $defaultIds = $this->query()->forRequest(Request::create('/transactions'), $superAdmin)->pluck('id');
        $allIds = $this->query()->forRequest(Request::create('/transactions', 'GET', ['scope' => 'all']), $superAdmin)->pluck('id');

        $this->assertContains($adminTransaction->id, $defaultIds);
        $this->assertNotContains($otherTransaction->id, $defaultIds);
        $this->assertContains($adminTransaction->id, $allIds);
        $this->assertContains($otherTransaction->id, $allIds);
    }

    public function test_amount_must_be_positive(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->referencesFor($user, TransactionTypeEnum::EXPENSE);

        $this->expectException(ValidationException::class);

        $this->validatedStoreData($user, [
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => TransactionTypeEnum::EXPENSE->value,
            'amount' => '0',
            'transaction_date' => '2026-05-02',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validatedStoreData(User $user, array $data): array
    {
        $request = TransactionStoreRequest::create('/transactions', 'POST', $data);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(fn (): User => $user);
        $request->validateResolved();

        return $request->validated();
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
        ]);
    }

    private function service(): TransactionService
    {
        return $this->app->make(TransactionService::class);
    }

    private function query(): TransactionQuery
    {
        return $this->app->make(TransactionQuery::class);
    }
}
