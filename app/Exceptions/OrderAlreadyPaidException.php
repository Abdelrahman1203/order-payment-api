<?php

namespace App\Exceptions;

class OrderAlreadyPaidException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'ORDER_ALREADY_PAID',
            'This order has already been paid successfully. You cannot process another payment for it.',
            422
        );
    }
}
