<?php

namespace App\Exceptions;

class OrderNotFoundException extends ApiException
{
    public function __construct()
    {
        parent::__construct('ORDER_NOT_FOUND', 'Order not found.', 404);
    }
}
