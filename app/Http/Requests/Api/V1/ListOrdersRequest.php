<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends ApiFormRequest
{
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
            'status' => ['sometimes', 'nullable', Rule::enum(OrderStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Please use a valid order status: pending, confirmed, or cancelled.',
            'per_page.integer' => 'Results per page must be a whole number.',
            'per_page.min' => 'Results per page must be at least 1.',
            'per_page.max' => 'Results per page cannot exceed 100.',
            'page.integer' => 'Page number must be a whole number.',
            'page.min' => 'Page number must be at least 1.',
        ];
    }
}
