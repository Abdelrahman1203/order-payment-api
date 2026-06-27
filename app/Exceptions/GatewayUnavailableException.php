<?php

namespace App\Exceptions;

class GatewayUnavailableException extends ApiException
{
    public function __construct(string $method)
    {
        parent::__construct(
            'GATEWAY_UNAVAILABLE',
            "Payment gateway [{$method}] is unavailable.",
            422
        );
    }
}
