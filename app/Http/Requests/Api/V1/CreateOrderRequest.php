<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Requests\Concerns\MapsOrderItemValidationMessages;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends ApiFormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'status' => ['sometimes', Rule::in([OrderStatus::Pending->value])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
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
        );
    }
}
