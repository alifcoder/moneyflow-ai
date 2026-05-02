<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the SuperAdmin account.
     */
    public function run(): void
    {
        $admin = User::query()->firstOrNew([
            'email' => 'admin@moneyflow.test',
        ]);

        $admin->name = 'Super Admin';
        $admin->password = Hash::make('password');
        $admin->role = UserRoleEnum::SUPER_ADMIN;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
