<?php

namespace App\Exceptions;

use App\Support\ApiErrorResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode = 400,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiErrorResponse::make(
            $this->errorCode,
            $this->getMessage(),
            $this->details,
            $this->statusCode,
        );
    }
}
