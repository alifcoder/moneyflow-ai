<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Currency::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_scope' => ['nullable', Rule::in(['own', 'global'])],
            'code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['nullable', 'string', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }
}
