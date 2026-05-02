<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionTypeEnum;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_scope' => ['nullable', Rule::in(['own', 'global'])],
            'type' => ['required', Rule::enum(TransactionTypeEnum::class)],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
            'enabled' => ['sometimes', 'boolean'],
        ];
    }
}
