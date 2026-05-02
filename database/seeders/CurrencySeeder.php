<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed global currencies.
     */
    public function run(): void
    {
        Currency::query()->updateOrCreate(
            ['user_id' => null, 'code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'is_default' => true,
                'enabled' => true,
            ],
        );

        Currency::query()->updateOrCreate(
            ['user_id' => null, 'code' => 'UZS'],
            [
                'name' => 'Uzbekistan Som',
                'symbol' => 'sum',
                'is_default' => false,
                'enabled' => true,
            ],
        );
    }
}
