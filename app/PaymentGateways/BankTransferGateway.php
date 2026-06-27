<?php

namespace App\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentContext;
use App\DTOs\PaymentResult;
use App\Enums\PaymentStatus;
use App\Exceptions\GatewayUnavailableException;
use Illuminate\Support\Str;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function getCode(): string
    {
        return 'bank_transfer';
    }

    public function process(PaymentContext $context): PaymentResult
    {
        $this->ensureCredentialsConfigured();

        if (str_contains($context->customerEmail, 'hold@')) {
            return new PaymentResult(
                PaymentStatus::Failed,
                null,
                'Bank transfer failed: payment is on hold until gateway credentials are verified.'
            );
        }

        return new PaymentResult(
            PaymentStatus::Successful,
            'bt_'.Str::uuid()->toString(),
        );
    }

    private function ensureCredentialsConfigured(): void
    {
        /** @var array{api_key?: string|null, secret?: string|null} $config */
        $config = config('payments.gateways.bank_transfer', []);

        if (blank($config['api_key'] ?? null) || blank($config['secret'] ?? null)) {
            throw new GatewayUnavailableException($this->getCode());
        }
    }
}
