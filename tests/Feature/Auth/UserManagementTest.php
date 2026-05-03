<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_can_not_open_user_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_open_user_management(): void
    {
        $this->withoutVite();

        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data')
                ->has('roles'));
    }

    public function test_super_admin_can_create_user(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'Created User',
                'email' => 'created@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRoleEnum::USER->value,
            ])
            ->assertRedirect();

        $user = User::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertSame(UserRoleEnum::USER, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_normal_user_can_not_create_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('users.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'role' => UserRoleEnum::USER->value,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing(User::class, [
            'email' => 'blocked@example.com',
        ]);
    }

    public function test_super_admin_can_login_as_user(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $user = User::factory()->create([
            'role' => UserRoleEnum::USER,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('users.impersonate', $user))
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('impersonator_id', $superAdmin->id);

        $this->assertAuthenticatedAs($user);
    }

    public function test_normal_user_can_not_login_as_user(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->post(route('users.impersonate', $target))
            ->assertForbidden();

        $this->assertAuthenticatedAs($user);
    }

    public function test_impersonated_user_can_return_to_super_admin(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);
        $user = User::factory()->create([
            'role' => UserRoleEnum::USER,
        ]);

        $this->actingAs($user)
            ->withSession(['impersonator_id' => $superAdmin->id])
            ->post(route('impersonation.destroy'))
            ->assertRedirect(route('users.index', absolute: false));

        $this->assertAuthenticatedAs($superAdmin);
    }
}
