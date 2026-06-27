<?php

use App\Exceptions\GatewayUnavailableException;
use App\Exceptions\UnknownGatewayException;
use App\Models\PaymentGateway;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;
use App\Services\PaymentGatewayManager;

it('resolves registered gateway', function () {
    $manager = new PaymentGatewayManager([
        new CreditCardGateway,
        new PayPalGateway,
        new StripeGateway,
    ]);

    $gateway = $manager->resolve('credit_card');

    expect($gateway->getCode())->toBe('credit_card');
});

it('throws for unknown gateway', function () {
    $manager = new PaymentGatewayManager([
        new CreditCardGateway,
    ]);

    $manager->resolve('bitcoin');
})->throws(UnknownGatewayException::class);

it('throws when gateway is disabled in database', function () {
    PaymentGateway::query()->where('code', 'paypal')->update(['is_enabled' => false]);

    $manager = new PaymentGatewayManager([
        new PayPalGateway,
    ]);

    $manager->resolve('paypal');
})->throws(GatewayUnavailableException::class);

it('throws when gateway secret is missing from config', function () {
    config([
        'payments.gateways.credit_card' => [
            'env_prefix' => 'CREDIT_CARD',
            'api_key' => 'cc_test_key',
            'secret' => null,
        ],
    ]);

    $manager = new PaymentGatewayManager([
        new CreditCardGateway,
    ]);

    $manager->resolve('credit_card');
})->throws(GatewayUnavailableException::class);
