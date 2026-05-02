<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('dashboard.totals')
                ->has('dashboard.recentTransactions')
                ->has('dashboard.cashboxBalances'));
    }

    public function test_reports_load(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->has('reports.monthly')
                ->has('reports.byCategory')
                ->has('reports.byCurrency')
                ->has('reports.byCashbox'));
    }

    public function test_totals_are_calculated_correctly(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $this->createTransaction($user, TransactionTypeEnum::INCOME, '100.0000', 'Salary');
        $this->createTransaction($user, TransactionTypeEnum::EXPENSE, '40.0000', 'Food');

        $response = $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();

        $totals = $response->inertiaProps('reports.totals');

        $this->assertEquals(100.0, $totals['income']);
        $this->assertEquals(40.0, $totals['expense']);
        $this->assertEquals(60.0, $totals['net']);
    }

    public function test_normal_user_cannot_see_another_users_report_data(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->createTransaction($user, TransactionTypeEnum::INCOME, '100.0000', 'Own income');
        $this->createTransaction($otherUser, TransactionTypeEnum::INCOME, '900.0000', 'Other income');

        $response = $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();

        $totals = $response->inertiaProps('dashboard.totals');

        $this->assertEquals(100.0, $totals['income']);
        $this->assertEquals(0.0, $totals['expense']);
        $this->assertEquals(100.0, $totals['net']);
    }

    public function test_super_admin_scope_all_includes_all_users_report_data(): void
    {
        $this->withoutVite();

        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $otherUser = User::factory()->create();

        $this->createTransaction($superAdmin, TransactionTypeEnum::INCOME, '100.0000', 'Admin income');
        $this->createTransaction($otherUser, TransactionTypeEnum::INCOME, '900.0000', 'Other income');

        $defaultResponse = $this->actingAs($superAdmin)
            ->get(route('reports.index'))
            ->assertOk();

        $allResponse = $this->actingAs($superAdmin)
            ->get(route('reports.index', ['scope' => 'all']))
            ->assertOk();

        $this->assertEquals(100.0, $defaultResponse->inertiaProps('reports.totals.income'));
        $this->assertEquals(1000.0, $allResponse->inertiaProps('reports.totals.income'));
    }

    private function createTransaction(User $user, TransactionTypeEnum $type, string $amount, string $comment): Transaction
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

        return Transaction::query()->create([
            'user_id' => $user->id,
            'cashbox_id' => $cashbox->id,
            'currency_id' => $currency->id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => $amount,
            'transaction_date' => '2026-05-02',
            'comment' => $comment,
        ]);
    }
}
