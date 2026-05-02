<?php

declare(strict_types=1);

namespace Tests\Feature\References;

use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReferenceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_works_for_own_references(): void
    {
        $user = User::factory()->create();
        $currency = Currency::query()->create([
            'user_id' => $user->id,
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);

        $this->actingAs($user)
            ->post(route('currencies.store'), [
                'code' => 'eur',
                'name' => 'Euro',
                'symbol' => 'EUR',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $createdCurrency = Currency::query()->where('code', 'EUR')->firstOrFail();
        $this->assertSame($user->id, $createdCurrency->user_id);

        $this->actingAs($user)
            ->put(route('currencies.update', $createdCurrency), [
                'code' => 'eur',
                'name' => 'Euro Updated',
                'symbol' => 'E',
                'enabled' => false,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', [
            'id' => $createdCurrency->id,
            'code' => 'EUR',
            'name' => 'Euro Updated',
            'enabled' => false,
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->post(route('categories.store'), [
                'type' => TransactionTypeEnum::EXPENSE->value,
                'name' => 'Groceries',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $category = Category::query()->where('name', 'Groceries')->firstOrFail();
        $this->assertSame($user->id, $category->user_id);

        $this->actingAs($user)
            ->put(route('categories.update', $category), [
                'type' => TransactionTypeEnum::INCOME->value,
                'name' => 'Side Income',
                'enabled' => true,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'type' => TransactionTypeEnum::INCOME->value,
            'name' => 'Side Income',
        ]);

        $this->actingAs($user)
            ->post(route('cashboxes.store'), [
                'currency_id' => $currency->id,
                'name' => 'Wallet',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $cashbox = Cashbox::query()->where('name', 'Wallet')->firstOrFail();
        $this->assertSame($user->id, $cashbox->user_id);

        $this->actingAs($user)
            ->put(route('cashboxes.update', $cashbox), [
                'currency_id' => $currency->id,
                'name' => 'Main Wallet',
                'enabled' => false,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cashboxes', [
            'id' => $cashbox->id,
            'name' => 'Main Wallet',
            'enabled' => false,
            'is_default' => true,
        ]);

        $this->actingAs($user)->delete(route('cashboxes.destroy', $cashbox))->assertRedirect();
        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertRedirect();
        $this->actingAs($user)->delete(route('currencies.destroy', $createdCurrency))->assertRedirect();

        $this->assertDatabaseMissing('cashboxes', ['id' => $cashbox->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('currencies', ['id' => $createdCurrency->id]);
    }

    public function test_normal_user_cannot_update_or_delete_global_references(): void
    {
        $user = User::factory()->create();
        [$currency, $category, $cashbox] = $this->globalReferences();

        $this->actingAs($user)
            ->put(route('currencies.update', $currency), [
                'code' => 'USD',
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('currencies.destroy', $currency))->assertForbidden();

        $this->actingAs($user)
            ->put(route('categories.update', $category), [
                'type' => TransactionTypeEnum::EXPENSE->value,
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertForbidden();

        $this->actingAs($user)
            ->put(route('cashboxes.update', $cashbox), [
                'currency_id' => $currency->id,
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('cashboxes.destroy', $cashbox))->assertForbidden();
    }

    public function test_normal_user_cannot_update_or_delete_another_users_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        [$currency, $category, $cashbox] = $this->ownedReferences($otherUser);

        $this->actingAs($user)
            ->put(route('currencies.update', $currency), [
                'code' => 'GBP',
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('currencies.destroy', $currency))->assertForbidden();

        $this->actingAs($user)
            ->put(route('categories.update', $category), [
                'type' => TransactionTypeEnum::EXPENSE->value,
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('categories.destroy', $category))->assertForbidden();

        $this->actingAs($user)
            ->put(route('cashboxes.update', $cashbox), [
                'currency_id' => $currency->id,
                'name' => 'Blocked',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertForbidden();

        $this->actingAs($user)->delete(route('cashboxes.destroy', $cashbox))->assertForbidden();
    }

    public function test_super_admin_can_update_and_delete_global_references(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        [$currency, $category, $cashbox] = $this->globalReferences();

        $this->actingAs($superAdmin)
            ->put(route('currencies.update', $currency), [
                'code' => 'USD',
                'name' => 'US Dollar Updated',
                'enabled' => true,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->put(route('categories.update', $category), [
                'type' => TransactionTypeEnum::INCOME->value,
                'name' => 'Salary Updated',
                'enabled' => true,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->put(route('cashboxes.update', $cashbox), [
                'currency_id' => $currency->id,
                'name' => 'Cash Updated',
                'enabled' => true,
                'is_default' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'name' => 'US Dollar Updated']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Salary Updated']);
        $this->assertDatabaseHas('cashboxes', ['id' => $cashbox->id, 'name' => 'Cash Updated']);

        $this->actingAs($superAdmin)->delete(route('cashboxes.destroy', $cashbox))->assertRedirect();
        $this->actingAs($superAdmin)->delete(route('categories.destroy', $category))->assertRedirect();
        $this->actingAs($superAdmin)->delete(route('currencies.destroy', $currency))->assertRedirect();

        $this->assertDatabaseMissing('cashboxes', ['id' => $cashbox->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_super_admin_can_create_global_references(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('currencies.store'), [
                'owner_scope' => 'global',
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $currency = Currency::query()->where('code', 'JPY')->firstOrFail();
        $this->assertNull($currency->user_id);

        $this->actingAs($superAdmin)
            ->post(route('categories.store'), [
                'owner_scope' => 'global',
                'type' => TransactionTypeEnum::INCOME->value,
                'name' => 'Investments',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->assertNull(Category::query()->where('name', 'Investments')->firstOrFail()->user_id);

        $this->actingAs($superAdmin)
            ->post(route('cashboxes.store'), [
                'owner_scope' => 'global',
                'currency_id' => $currency->id,
                'name' => 'Global Vault',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->assertNull(Cashbox::query()->where('name', 'Global Vault')->firstOrFail()->user_id);
    }

    public function test_super_admin_can_update_and_delete_another_users_references(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();
        [$currency, $category, $cashbox] = $this->ownedReferences($otherUser);

        $this->actingAs($superAdmin)
            ->put(route('currencies.update', $currency), [
                'code' => 'GBP',
                'name' => 'British Pound Updated',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->put(route('categories.update', $category), [
                'type' => TransactionTypeEnum::EXPENSE->value,
                'name' => 'Private Expense Updated',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->put(route('cashboxes.update', $cashbox), [
                'currency_id' => $currency->id,
                'name' => 'Private Cash Updated',
                'enabled' => true,
                'is_default' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'name' => 'British Pound Updated']);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Private Expense Updated']);
        $this->assertDatabaseHas('cashboxes', ['id' => $cashbox->id, 'name' => 'Private Cash Updated']);

        $this->actingAs($superAdmin)->delete(route('cashboxes.destroy', $cashbox))->assertRedirect();
        $this->actingAs($superAdmin)->delete(route('categories.destroy', $category))->assertRedirect();
        $this->actingAs($superAdmin)->delete(route('currencies.destroy', $currency))->assertRedirect();

        $this->assertDatabaseMissing('cashboxes', ['id' => $cashbox->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_super_admin_scope_all_works(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();
        [$otherCurrency, $otherCategory, $otherCashbox] = $this->ownedReferences($otherUser);

        $this->withoutVite();

        $this->actingAs($superAdmin)
            ->get(route('currencies.index', ['scope' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Currencies/Index')
                ->where('currencies.data.0.id', $otherCurrency->id));

        $this->actingAs($superAdmin)
            ->get(route('categories.index', ['scope' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->where('categories.data.0.id', $otherCategory->id));

        $this->actingAs($superAdmin)
            ->get(route('cashboxes.index', ['scope' => 'all']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cashboxes/Index')
                ->where('cashboxes.data.0.id', $otherCashbox->id));
    }

    /**
     * @return array{Currency, Category, Cashbox}
     */
    private function globalReferences(): array
    {
        $currency = Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
        ]);

        $category = Category::query()->create([
            'type' => TransactionTypeEnum::INCOME,
            'name' => 'Salary',
        ]);

        $cashbox = Cashbox::query()->create([
            'currency_id' => $currency->id,
            'name' => 'Cash',
        ]);

        return [$currency, $category, $cashbox];
    }

    /**
     * @return array{Currency, Category, Cashbox}
     */
    private function ownedReferences(User $user): array
    {
        $currency = Currency::query()->create([
            'user_id' => $user->id,
            'code' => 'GBP',
            'name' => 'British Pound',
        ]);

        $category = Category::query()->create([
            'user_id' => $user->id,
            'type' => TransactionTypeEnum::EXPENSE,
            'name' => 'Private Expense',
        ]);

        $cashbox = Cashbox::query()->create([
            'user_id' => $user->id,
            'currency_id' => $currency->id,
            'name' => 'Private Cash',
        ]);

        return [$currency, $category, $cashbox];
    }
}
