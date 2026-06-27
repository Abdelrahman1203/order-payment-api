<?php

use App\DTOs\PaymentContext;
use App\Enums\PaymentStatus;
use App\Exceptions\GatewayUnavailableException;
use App\PaymentGateways\BankTransferGateway;

beforeEach(function () {
    config([
        'payments.gateways.bank_transfer' => [
            'env_prefix' => 'BANK_TRANSFER',
            'api_key' => 'bt_test_key',
            'secret' => 'bt_test_secret',
        ],
    ]);
});

it('processes successful bank transfer when api key and secret are configured', function () {
    $gateway = new BankTransferGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '250.00',
        customerEmail: 'buyer@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Successful)
        ->and($result->gatewayReference)->toStartWith('bt_');
});

it('fails bank transfer for hold email', function () {
    $gateway = new BankTransferGateway;

    $result = $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '250.00',
        customerEmail: 'hold@example.com',
    ));

    expect($result->status)->toBe(PaymentStatus::Failed)
        ->and($result->failureReason)->toContain('on hold');
});

it('throws when bank transfer secret is missing', function () {
    config([
        'payments.gateways.bank_transfer' => [
            'env_prefix' => 'BANK_TRANSFER',
            'api_key' => 'bt_test_key',
            'secret' => null,
        ],
    ]);

    $gateway = new BankTransferGateway;

    $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '250.00',
        customerEmail: 'buyer@example.com',
    ));
})->throws(GatewayUnavailableException::class);

it('throws when bank transfer api key is missing', function () {
    config([
        'payments.gateways.bank_transfer' => [
            'env_prefix' => 'BANK_TRANSFER',
            'api_key' => null,
            'secret' => 'bt_test_secret',
        ],
    ]);

    $gateway = new BankTransferGateway;

    $gateway->process(new PaymentContext(
        orderId: 1,
        amount: '250.00',
        customerEmail: 'buyer@example.com',
    ));
})->throws(GatewayUnavailableException::class);
