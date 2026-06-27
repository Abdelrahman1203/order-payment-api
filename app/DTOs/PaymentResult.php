<?php

namespace App\DTOs;

use App\Enums\PaymentStatus;

readonly class PaymentResult
{
    public function __construct(
        public PaymentStatus $status,
        public ?string $gatewayReference = null,
        public ?string $failureReason = null,
    ) {}
}
