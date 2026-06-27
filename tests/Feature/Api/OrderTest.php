<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

it('creates an order with calculated total', function () {
    $user = User::factory()->create();

    $response = $this->actingAsJwt($user)->postJson('/api/v1/orders', [
        'customer_name' => 'Alice',
        'customer_email' => 'alice@example.com',
        'items' => [
            ['product_name' => 'Widget', 'quantity' => 2, 'price' => 15.00],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.total', '30.00')
        ->assertJsonPath('data.status', 'pending');
});

it('lists orders with status filter and pagination', function () {
    $user = User::factory()->create();
    Order::factory()->for($user)->count(2)->create(['status' => OrderStatus::Pending]);
    Order::factory()->for($user)->create(['status' => OrderStatus::Confirmed]);

    $response = $this->actingAsJwt($user)->getJson('/api/v1/orders?status=confirmed&per_page=10');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('returns empty arrays instead of null in pagination metadata', function () {
    $user = User::factory()->create();

    $response = $this->actingAsJwt($user)->getJson('/api/v1/orders?per_page=15');

    $response->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('links.prev', [])
        ->assertJsonPath('links.next', [])
        ->assertJsonPath('meta.from', [])
        ->assertJsonPath('meta.to', []);
});

it('updates an order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['customer_name' => 'Old Name']);

    $response = $this->actingAsJwt($user)->putJson("/api/v1/orders/{$order->id}", [
        'customer_name' => 'New Name',
        'status' => 'confirmed',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.customer_name', 'New Name')
        ->assertJsonPath('data.status', 'confirmed');
});

it('deletes an order without payments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAsJwt($user)->deleteJson("/api/v1/orders/{$order->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

it('returns conflict when deleting order with payments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();
    Payment::factory()->for($order)->create();

    $this->actingAsJwt($user)->deleteJson("/api/v1/orders/{$order->id}")
        ->assertConflict()
        ->assertJsonPath('error.code', 'ORDER_HAS_PAYMENTS');
});

it('returns meaningful validation error when item quantity is missing', function () {
    $user = User::factory()->create();

    $response = $this->actingAsJwt($user)->postJson('/api/v1/orders', [
        'customer_name' => 'Alice Smith',
        'customer_email' => 'alice@example.com',
        'items' => [
            ['product_name' => 'Laptop', 'price' => 999.99],
            ['product_name' => 'Mouse', 'quantity' => 2, 'price' => 25.50],
        ],
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please enter how many units you need for each product.')
        ->assertJsonPath('error.details.0.field', 'items.0.quantity')
        ->assertJsonPath('error.details.0.message', 'Please enter how many units you need for each product.');
});

it('returns summary validation message for multiple order field errors', function () {
    $user = User::factory()->create();

    $response = $this->actingAsJwt($user)->postJson('/api/v1/orders', [
        'customer_name' => '',
        'customer_email' => 'not-an-email',
        'items' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'The given data was invalid. Please review the field errors below.')
        ->assertJsonFragment(['field' => 'customer_email', 'message' => 'Please enter a valid customer email address.'])
        ->assertJsonFragment(['field' => 'items', 'message' => 'Please add at least one product to the order.']);
});

it('rejects creating an order with non pending status', function () {
    $user = User::factory()->create();

    $this->actingAsJwt($user)->postJson('/api/v1/orders', [
        'customer_name' => 'Alice Smith',
        'customer_email' => 'alice@example.com',
        'status' => 'confirmed',
        'items' => [
            ['product_name' => 'Laptop', 'quantity' => 1, 'price' => 999.99],
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'New orders can only be created with pending status.');
});

it('rejects empty update body', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create();

    $this->actingAsJwt($user)->putJson("/api/v1/orders/{$order->id}", [])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Please provide at least one field to update.');
});

it('rejects updating a paid order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'customer_name' => 'Alice Smith',
    ]);

    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Successful,
    ]);

    $this->actingAsJwt($user)->putJson("/api/v1/orders/{$order->id}", [
        'customer_name' => 'Updated Name',
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'ORDER_LOCKED')
        ->assertJsonPath('error.message', 'This order has been paid successfully and can no longer be modified.');
});

it('rejects item prices with more than two decimal places', function () {
    $user = User::factory()->create();

    $this->actingAsJwt($user)->postJson('/api/v1/orders', [
        'customer_name' => 'Alice Smith',
        'customer_email' => 'alice@example.com',
        'items' => [
            ['product_name' => 'Laptop', 'quantity' => 1, 'price' => 10.999],
        ],
    ])->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonPath('error.message', 'Product price must have at most 2 decimal places.');
});

it('prevents accessing another users order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->for($owner)->create();

    $this->actingAsJwt($other)->getJson("/api/v1/orders/{$order->id}")
        ->assertNotFound();
});
