<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TransactionTypeEnum;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Seed global default categories.
     */
    public function run(): void
    {
        foreach (['Salary', 'Bonus', 'Other Income'] as $name) {
            Category::query()->updateOrCreate(
                [
                    'user_id' => null,
                    'type' => TransactionTypeEnum::INCOME->value,
                    'name' => $name,
                ],
                [
                    'is_default' => true,
                    'enabled' => true,
                ],
            );
        }

        foreach (['Food', 'Transport', 'Utilities', 'Other Expense'] as $name) {
            Category::query()->updateOrCreate(
                [
                    'user_id' => null,
                    'type' => TransactionTypeEnum::EXPENSE->value,
                    'name' => $name,
                ],
                [
                    'is_default' => true,
                    'enabled' => true,
                ],
            );
        }
    }
}
