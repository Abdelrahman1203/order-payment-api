<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentContext;
use App\DTOs\PaymentResult;
use App\Enums\PaymentStatus;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'credit_card';
    }

    public function process(PaymentContext $context): PaymentResult
    {
        $cardNumber = (string) ($context->metadata['card_number'] ?? '');
        $amount = (float) $context->amount;

        if ($cardNumber === '4111111111111111' && $amount > 1000) {
            return new PaymentResult(
                PaymentStatus::Failed,
                null,
                'Credit card declined: amount exceeds limit for test card.'
            );
        }

        return new PaymentResult(
            PaymentStatus::Successful,
            'cc_'.Str::uuid()->toString(),
        );
    }
}
