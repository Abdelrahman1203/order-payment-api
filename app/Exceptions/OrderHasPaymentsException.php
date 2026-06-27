<?php

namespace App\Exceptions;

class OrderHasPaymentsException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'ORDER_HAS_PAYMENTS',
            'Orders cannot be deleted if they have associated payments.',
            409
        );
    }
}
