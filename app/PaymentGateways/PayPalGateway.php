<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentContext;
use App\DTOs\PaymentResult;
use App\Enums\PaymentStatus;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'paypal';
    }

    public function process(PaymentContext $context): PaymentResult
    {
        if (str_contains($context->customerEmail, 'fail@')) {
            return new PaymentResult(
                PaymentStatus::Failed,
                null,
                'PayPal payment failed: payer account rejected.'
            );
        }

        return new PaymentResult(
            PaymentStatus::Successful,
            'pp_'.Str::uuid()->toString(),
        );
    }
}
