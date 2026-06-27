<?php

namespace App\Exceptions;

class OrderNotConfirmedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'ORDER_NOT_CONFIRMED',
            'Payments can only be processed for confirmed orders.',
            422
        );
    }
}
