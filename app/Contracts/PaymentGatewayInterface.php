<?php

namespace App\Contracts;

use App\DTOs\PaymentContext;
use App\DTOs\PaymentResult;

interface PaymentGatewayInterface
{
    public function getCode(): string;

    public function process(PaymentContext $context): PaymentResult;
}
