<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Cashbox;
use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashboxStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cashbox::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_scope' => ['nullable', Rule::in(['own', 'global'])],
            'currency_id' => [
                'required',
                Rule::exists(Currency::class, 'id')->where(function ($query): void {
                    if (! $this->user()?->isSuperAdmin()) {
                        $query->where(function ($query): void {
                            $query->whereNull('user_id')
                                ->orWhere('user_id', $this->user()->id);
                        });
                    }
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }
}
