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
use Tests\TestCase;

class ReferenceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_global_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $references = $this->createReferences($user, $otherUser);

        $this->assertContains($references['globalCurrency']->id, Currency::query()->visibleFor($user)->pluck('id'));
        $this->assertContains($references['globalCategory']->id, Category::query()->visibleFor($user)->pluck('id'));
        $this->assertContains($references['globalCashbox']->id, Cashbox::query()->visibleFor($user)->pluck('id'));

        $this->assertTrue($user->can('view', $references['globalCurrency']));
        $this->assertTrue($user->can('view', $references['globalCategory']));
        $this->assertTrue($user->can('view', $references['globalCashbox']));
    }

    public function test_user_can_see_own_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $references = $this->createReferences($user, $otherUser);

        $this->assertContains($references['ownCurrency']->id, Currency::query()->visibleFor($user)->pluck('id'));
        $this->assertContains($references['ownCategory']->id, Category::query()->visibleFor($user)->pluck('id'));
        $this->assertContains($references['ownCashbox']->id, Cashbox::query()->visibleFor($user)->pluck('id'));

        $this->assertTrue($user->can('view', $references['ownCurrency']));
        $this->assertTrue($user->can('view', $references['ownCategory']));
        $this->assertTrue($user->can('view', $references['ownCashbox']));
    }

    public function test_user_cannot_see_another_users_private_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $references = $this->createReferences($user, $otherUser);

        $this->assertNotContains($references['otherCurrency']->id, Currency::query()->visibleFor($user)->pluck('id'));
        $this->assertNotContains($references['otherCategory']->id, Category::query()->visibleFor($user)->pluck('id'));
        $this->assertNotContains($references['otherCashbox']->id, Cashbox::query()->visibleFor($user)->pluck('id'));

        $this->assertFalse($user->can('view', $references['otherCurrency']));
        $this->assertFalse($user->can('view', $references['otherCategory']));
        $this->assertFalse($user->can('view', $references['otherCashbox']));
    }

    public function test_user_cannot_update_global_references(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $references = $this->createReferences($user, $otherUser);

        $this->assertFalse($user->can('update', $references['globalCurrency']));
        $this->assertFalse($user->can('update', $references['globalCategory']));
        $this->assertFalse($user->can('update', $references['globalCashbox']));
    }

    public function test_super_admin_can_update_global_references(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();
        $references = $this->createReferences($superAdmin, $otherUser);

        $this->assertTrue($superAdmin->can('update', $references['globalCurrency']));
        $this->assertTrue($superAdmin->can('update', $references['globalCategory']));
        $this->assertTrue($superAdmin->can('update', $references['globalCashbox']));
    }

    public function test_super_admin_visible_scope_stays_global_plus_own(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();
        $references = $this->createReferences($superAdmin, $otherUser);

        $this->assertContains($references['globalCurrency']->id, Currency::query()->visibleFor($superAdmin)->pluck('id'));
        $this->assertContains($references['ownCurrency']->id, Currency::query()->visibleFor($superAdmin)->pluck('id'));
        $this->assertNotContains($references['otherCurrency']->id, Currency::query()->visibleFor($superAdmin)->pluck('id'));
    }

    public function test_default_reference_seeders_create_global_records(): void
    {
        $this->seed();

        $this->assertTrue(Currency::query()->global()->where('code', 'USD')->exists());
        $this->assertTrue(Currency::query()->global()->where('code', 'UZS')->exists());
        $this->assertTrue(Category::query()->global()->where('type', TransactionTypeEnum::INCOME)->exists());
        $this->assertTrue(Category::query()->global()->where('type', TransactionTypeEnum::EXPENSE)->exists());
        $this->assertTrue(Cashbox::query()->global()->where('name', 'Cash')->exists());
    }

    /**
     * @return array<string, Currency|Category|Cashbox>
     */
    private function createReferences(User $user, User $otherUser): array
    {
        $globalCurrency = Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_default' => true,
        ]);

        $ownCurrency = Currency::query()->create([
            'user_id' => $user->id,
            'code' => 'EUR',
            'name' => 'Euro',
        ]);

        $otherCurrency = Currency::query()->create([
            'user_id' => $otherUser->id,
            'code' => 'GBP',
            'name' => 'British Pound',
        ]);

        $globalCategory = Category::query()->create([
            'type' => TransactionTypeEnum::INCOME,
            'name' => 'Salary',
            'is_default' => true,
        ]);

        $ownCategory = Category::query()->create([
            'user_id' => $user->id,
            'type' => TransactionTypeEnum::EXPENSE,
            'name' => 'Groceries',
        ]);

        $otherCategory = Category::query()->create([
            'user_id' => $otherUser->id,
            'type' => TransactionTypeEnum::EXPENSE,
            'name' => 'Private Expense',
        ]);

        $globalCashbox = Cashbox::query()->create([
            'currency_id' => $globalCurrency->id,
            'name' => 'Cash',
            'is_default' => true,
        ]);

        $ownCashbox = Cashbox::query()->create([
            'user_id' => $user->id,
            'currency_id' => $ownCurrency->id,
            'name' => 'Personal Cash',
        ]);

        $otherCashbox = Cashbox::query()->create([
            'user_id' => $otherUser->id,
            'currency_id' => $otherCurrency->id,
            'name' => 'Other Cash',
        ]);

        return [
            'globalCurrency' => $globalCurrency,
            'ownCurrency' => $ownCurrency,
            'otherCurrency' => $otherCurrency,
            'globalCategory' => $globalCategory,
            'ownCategory' => $ownCategory,
            'otherCategory' => $otherCategory,
            'globalCashbox' => $globalCashbox,
            'ownCashbox' => $ownCashbox,
            'otherCashbox' => $otherCashbox,
        ];
    }
}
