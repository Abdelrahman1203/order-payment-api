<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\Concerns\MapsOrderItemValidationMessages;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends ApiFormRequest
{
    use MapsOrderItemValidationMessages;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'filled', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'filled', 'email', 'max:255'],
            'status' => ['sometimes', Rule::enum(OrderStatus::class)],
            'items' => ['sometimes', 'array', 'min:1', 'max:50'],
            'items.*.product_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:9999'],
            'items.*.price' => ['required_with:items', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            $this->orderCustomerValidationMessages(),
            $this->orderItemValidationMessages(),
            [
                'customer_name.filled' => 'Please enter the customer\'s full name.',
                'customer_email.filled' => 'Please enter the customer\'s email address.',
            ],
        );
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $updatableFields = ['customer_name', 'customer_email', 'status', 'items'];

            $hasField = collect($updatableFields)->contains(
                fn (string $field) => $this->exists($field)
            );

            if (! $hasField) {
                $validator->errors()->add(
                    'body',
                    'Please provide at least one field to update.'
                );
            }
        });
    }
}
