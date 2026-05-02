<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_registered_user_has_user_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(UserRoleEnum::USER, $user->role);
    }

    public function test_seeded_admin_has_super_admin_role(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $admin = User::query()->where('email', 'admin@moneyflow.test')->firstOrFail();

        $this->assertSame('Super Admin', $admin->name);
        $this->assertSame(UserRoleEnum::SUPER_ADMIN, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_is_super_admin_helper_works(): void
    {
        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);

        $user = User::factory()->create([
            'role' => UserRoleEnum::USER,
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($user->isSuperAdmin());
    }

    public function test_is_user_helper_works(): void
    {
        $user = User::factory()->create([
            'role' => UserRoleEnum::USER,
        ]);

        $superAdmin = User::factory()->create([
            'role' => UserRoleEnum::SUPER_ADMIN,
        ]);

        $this->assertTrue($user->isUser());
        $this->assertFalse($superAdmin->isUser());
    }
}
