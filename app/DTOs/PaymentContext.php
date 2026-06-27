<?php

namespace App\DTOs;

readonly class PaymentContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $orderId,
        public string $amount,
        public string $customerEmail,
        public array $metadata = [],
    ) {}
}
