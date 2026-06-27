<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentContext;
use App\DTOs\PaymentResult;
use App\Enums\PaymentStatus;
use Illuminate\Support\Str;

class StripeGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'stripe';
    }

    public function process(PaymentContext $context): PaymentResult
    {
        if (str_ends_with($context->amount, '.99')) {
            return new PaymentResult(
                PaymentStatus::Failed,
                null,
                'Stripe payment failed: simulated decline for amounts ending in .99.'
            );
        }

        return new PaymentResult(
            PaymentStatus::Successful,
            'pi_'.Str::uuid()->toString(),
        );
    }
}
