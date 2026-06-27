<?php

namespace App\Http\Requests\Api\V1;

use App\Services\PaymentGatewayManager;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends ApiFormRequest
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
        /** @var PaymentGatewayManager $manager */
        $manager = app(PaymentGatewayManager::class);

        return [
            'payment_method' => ['required', 'string', 'max:50', Rule::in($manager->enabledCodes())],
            'metadata' => ['sometimes', 'array', 'max:5'],
            'metadata.card_number' => ['required_if:payment_method,credit_card', 'string', 'regex:/^[0-9]{13,19}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.string' => 'Payment method must be text.',
            'payment_method.max' => 'Payment method cannot be longer than 50 characters.',
            'payment_method.in' => 'The selected payment method is not supported. Use credit_card, paypal, stripe, or bank_transfer.',

            'metadata.array' => 'Payment metadata must be sent as an object.',
            'metadata.max' => 'Payment metadata cannot contain more than 5 fields.',
            'metadata.card_number.required_if' => 'Please enter a card number when paying with credit card.',
            'metadata.card_number.string' => 'Card number must be text.',
            'metadata.card_number.regex' => 'Please enter a valid card number (13 to 19 digits).',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $metadata = $this->input('metadata');

            if (! is_array($metadata)) {
                return;
            }

            $allowedKeys = ['card_number'];

            foreach (array_keys($metadata) as $key) {
                if (! in_array($key, $allowedKeys, true)) {
                    $validator->errors()->add(
                        'metadata',
                        "Payment metadata contains an unsupported field: {$key}. Only card_number is allowed."
                    );
                }
            }
        });
    }
}
