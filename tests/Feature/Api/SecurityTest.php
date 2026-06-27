<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

it('returns security headers on api responses', function () {
    $response = $this->getJson('/api/v1/does-not-exist');

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('rejects invalid order status query parameter', function () {
    $user = User::factory()->create();

    $this->actingAsJwt($user)
        ->getJson('/api/v1/orders?status=invalid')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please use a valid order status: pending, confirmed, or cancelled.');
});

it('rejects invalid page query parameter on orders list', function () {
    $user = User::factory()->create();

    $this->actingAsJwt($user)
        ->getJson('/api/v1/orders?page=0')
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Page number must be at least 1.');
});

it('rejects non numeric order id in route', function () {
    $user = User::factory()->create();

    $this->actingAsJwt($user)
        ->getJson('/api/v1/orders/abc')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'ROUTE_NOT_FOUND');
});

it('rejects invalid payment metadata card number', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50.00,
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'credit_card',
        'metadata' => ['card_number' => 'not-a-card'],
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});

it('rejects unsupported payment metadata keys', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50.00,
    ]);

    $this->actingAsJwt($user)->postJson("/api/v1/orders/{$order->id}/payments", [
        'payment_method' => 'paypal',
        'metadata' => ['cvv' => '123'],
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED');
});

it('rate limits login attempts', function () {
    $ip = '203.0.113.50';

    for ($i = 0; $i < 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/v1/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'Password1',
        ]);
    }

    $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/v1/auth/login', [
        'email' => 'missing@example.com',
        'password' => 'Password1',
    ])->assertStatus(429)
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');

    Illuminate\Support\Facades\RateLimiter::clear('auth-login');
});
