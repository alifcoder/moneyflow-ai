<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionTypeEnum;
use App\Models\Cashbox;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransactionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cashbox_id' => ['required', $this->visibleCashboxRule()],
            'currency_id' => ['required', $this->visibleCurrencyRule()],
            'category_id' => ['required', $this->visibleCategoryRule()],
            'type' => ['required', Rule::enum(TransactionTypeEnum::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'comment' => ['nullable', 'string'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = Category::query()->find($this->integer('category_id'));

                if ($category !== null && $category->type->value !== $this->string('type')->toString()) {
                    $validator->errors()->add('category_id', 'The selected category type must match the transaction type.');
                }
            },
        ];
    }

    private function visibleCashboxRule(): object
    {
        return Rule::exists(Cashbox::class, 'id')->where(function ($query): void {
            $query->where(function ($query): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $this->user()->id);
            });
        });
    }

    private function visibleCurrencyRule(): object
    {
        return Rule::exists(Currency::class, 'id')->where(function ($query): void {
            $query->where(function ($query): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $this->user()->id);
            });
        });
    }

    private function visibleCategoryRule(): object
    {
        return Rule::exists(Category::class, 'id')->where(function ($query): void {
            $query->where(function ($query): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $this->user()->id);
            });
        });
    }
}
