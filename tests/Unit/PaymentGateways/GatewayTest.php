<?php

use App\DTOs\PaymentContext;
use App\Enums\PaymentStatus;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;

it('processes successful credit card payment', function () {
    $gateway = new CreditCardGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '100.00',
        customerEmail: 'buyer@example.com',
        metadata: ['card_number' => '4242424242424242'],
    ));

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->gatewayReference)->toStartWith('cc_');
});

it('declines credit card for test card over limit', function () {
    $gateway = new CreditCardGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '1500.00',
        customerEmail: 'buyer@example.com',
        metadata: ['card_number' => '4111111111111111'],
    ));

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toContain('declined');
});

it('processes successful paypal payment', function () {
    $gateway = new PayPalGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '50.00',
        customerEmail: 'buyer@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->gatewayReference)->toStartWith('pp_');
});

it('fails paypal payment for fail email', function () {
    $gateway = new PayPalGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '50.00',
        customerEmail: 'fail@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Failed);
});

it('processes successful stripe payment', function () {
    $gateway = new StripeGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '75.50',
        customerEmail: 'buyer@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->gatewayReference)->toStartWith('pi_');
});

it('fails stripe payment for amounts ending in 99 cents', function () {
    $gateway = new StripeGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '25.99',
        customerEmail: 'buyer@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Failed);
});
