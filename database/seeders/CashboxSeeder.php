<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cashbox;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class CashboxSeeder extends Seeder
{
    /**
     * Seed the global default cashbox.
     */
    public function run(): void
    {
        $currency = Currency::query()->where('code', 'USD')->global()->firstOrFail();

        Cashbox::query()->updateOrCreate(
            [
                'user_id' => null,
                'name' => 'Cash',
            ],
            [
                'currency_id' => $currency->id,
                'is_default' => true,
                'enabled' => true,
            ],
        );
    }
}
