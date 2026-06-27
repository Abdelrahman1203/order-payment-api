<?php

namespace App\Exceptions;

class OrderLockedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'ORDER_LOCKED',
            'This order has been paid successfully and can no longer be modified.',
            422
        );
    }
}
