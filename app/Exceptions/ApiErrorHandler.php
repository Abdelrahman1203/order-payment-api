<?php

namespace App\Exceptions;

use App\Support\ApiErrorResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiErrorHandler
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'TOO_MANY_REQUESTS',
                'Too many requests. Please slow down and try again later.',
                [],
                429,
            );
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            $details = collect($e->errors())->map(
                fn (array $messages, string $field) => [
                    'field' => $field,
                    'message' => $messages[0] ?? 'This field is invalid.',
                ]
            )->values()->all();

            $message = count($details) === 1
                ? $details[0]['message']
                : 'The given data was invalid. Please review the field errors below.';

            return ApiErrorResponse::make('VALIDATION_FAILED', $message, $details, 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'UNAUTHENTICATED',
                'You are not signed in. Please log in or create an account to access this resource.',
                [
                    [
                        'field' => 'login',
                        'message' => 'Send a POST request to /api/v1/auth/login with your email and password.',
                    ],
                    [
                        'field' => 'register',
                        'message' => 'If you do not have an account yet, use POST /api/v1/auth/register first.',
                    ],
                    [
                        'field' => 'authorization',
                        'message' => 'After login, copy the token from the response and add this header: Authorization: Bearer {your-token}.',
                    ],
                ],
                401,
            );
        });

        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'TOKEN_EXPIRED',
                'Your authentication token has expired. Please log in again.',
                [],
                401,
            );
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'TOKEN_INVALID',
                'The authentication token is invalid or malformed.',
                [],
                401,
            );
        });

        $exceptions->render(function (TokenBlacklistedException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'TOKEN_BLACKLISTED',
                'This authentication token has been revoked. Please log in again.',
                [],
                401,
            );
        });

        $exceptions->render(function (JWTException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'UNAUTHENTICATED',
                'Authentication failed. Provide a valid Bearer token in the Authorization header.',
                [],
                401,
            );
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'FORBIDDEN',
                $e->getMessage() !== '' ? $e->getMessage() : 'You are not authorized to perform this action.',
                [],
                403,
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'RESOURCE_NOT_FOUND',
                'The requested resource could not be found.',
                [],
                404,
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'ROUTE_NOT_FOUND',
                'The requested API endpoint does not exist.',
                [],
                404,
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                'METHOD_NOT_ALLOWED',
                'This HTTP method is not supported for the requested endpoint.',
                [],
                405,
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! self::isApi($request)) {
                return null;
            }

            if ($e instanceof ApiException) {
                return null;
            }

            if (config('app.debug')) {
                return ApiErrorResponse::make(
                    'SERVER_ERROR',
                    $e->getMessage() !== '' ? $e->getMessage() : 'An unexpected server error occurred.',
                    [],
                    500,
                );
            }

            return ApiErrorResponse::make(
                'SERVER_ERROR',
                'An unexpected error occurred. Please try again later.',
                [],
                500,
            );
        });
    }

    private static function isApi(Request $request): bool
    {
        return $request->is('api/*');
    }
}
