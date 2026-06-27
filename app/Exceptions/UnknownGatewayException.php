<?php

namespace App\Exceptions;

class UnknownGatewayException extends ApiException
{
    public function __construct(string $method)
    {
        parent::__construct(
            'UNKNOWN_GATEWAY',
            "Unknown payment gateway [{$method}].",
            422
        );
    }
}
