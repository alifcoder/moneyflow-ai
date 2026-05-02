<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CashboxUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cashbox')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
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
