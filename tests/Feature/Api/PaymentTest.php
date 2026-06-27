<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

it('processes payment for confirmed order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 100.00,
        'customer_email' => 'buyer@example.com',
    ]);

    $response = $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'credit_card',
        'metadata' => ['card_number' => '4242424242424242'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'successful')
        ->assertJsonPath('data.payment_method', 'credit_card');
});

it('rejects duplicate payment after order is already paid', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 100.00,
        'customer_email' => 'buyer@example.com',
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'credit_card',
        'metadata' => ['card_number' => '4242424242424242'],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'successful');

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'paypal',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'ORDER_ALREADY_PAID')
        ->assertJsonPath('error.message', 'This order has already been paid successfully. You cannot process another payment for it.');
});

it('rejects credit card payment without card number', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50.00,
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'credit_card',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please enter a card number when paying with credit card.');
});

it('allows payment retry after a failed attempt', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 19.99,
        'customer_email' => 'buyer@example.com',
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'stripe',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'failed');

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'paypal',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'successful');
});

it('rejects payment for pending order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'total' => 50.00,
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'paypal',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'ORDER_NOT_CONFIRMED');
});

it('lists payments for an order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order)->count(2)->create();

    $this->actingAsJwt($user)->getJson("/api/v1/orders/{$order->id}/payments")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists all payments for authenticated user', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order)->count(3)->create();

    $this->actingAsJwt($user)->getJson('/api/v1/payments?per_page=10')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['links', 'meta']);
});

it('simulates failed stripe payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 19.99,
        'customer_email' => 'buyer@example.com',
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'stripe',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'failed');
});

it('simulates declined credit card payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 1500.00,
        'customer_email' => 'buyer@example.com',
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'credit_card',
        'metadata' => ['card_number' => '4111111111111111'],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.failure_reason', 'Credit card declined: amount exceeds limit for test card.');
});

it('simulates failed paypal payment for fail email', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50.00,
        'customer_email' => 'fail@example.com',
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'paypal',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'failed')
        ->assertJsonPath('data.failure_reason', 'PayPal payment failed: payer account rejected.');
});
