<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\OrderAlreadyPaidException;
use App\Exceptions\OrderHasPaymentsException;
use App\Exceptions\OrderLockedException;
use App\Exceptions\OrderNotConfirmedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentRepository;
use App\Services\OrderService;
use App\Services\PaymentService;
use Database\Seeders\PaymentGatewaySeeder;

beforeEach(function () {
    $this->seed(PaymentGatewaySeeder::class);
});

it('calculates order total from items', function () {
    $user = User::factory()->create();
    $repository = new OrderRepository;
    $service = new OrderService($repository, new PaymentRepository);

    $order = $service->create($user, [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'items' => [
            ['product_name' => 'Book', 'quantity' => 2, 'price' => 10.50],
            ['product_name' => 'Pen', 'quantity' => 1, 'price' => 5.00],
        ],
    ]);

    expect((string) $order->total)->toBe('26.00');
});

it('blocks deleting orders with payments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100]);
    Payment::factory()->for($order)->create();

    $service = new OrderService(new OrderRepository, new PaymentRepository);

    $service->delete($order->id, $user);
})->throws(OrderHasPaymentsException::class);

it('rejects payment for non confirmed order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Pending,
        'total' => 50,
        'customer_email' => 'buyer@example.com',
    ]);

    $service = new PaymentService(
        new OrderRepository,
        new PaymentRepository,
        app(\App\Services\PaymentGatewayManager::class),
    );

    $service->process($order->id, $user, ['payment_method' => 'credit_card']);
})->throws(OrderNotConfirmedException::class);

it('rejects payment when order already has a successful payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50,
        'customer_email' => 'buyer@example.com',
    ]);

    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Successful,
    ]);

    $service = new PaymentService(
        new OrderRepository,
        new PaymentRepository,
        app(\App\Services\PaymentGatewayManager::class),
    );

    $service->process($order->id, $user, ['payment_method' => 'paypal']);
})->throws(OrderAlreadyPaidException::class);

it('blocks updating orders with a successful payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 50,
    ]);

    Payment::factory()->for($order)->create([
        'status' => PaymentStatus::Successful,
    ]);

    $service = new OrderService(new OrderRepository, new PaymentRepository);

    $service->update($order->id, $user, ['customer_name' => 'Updated Name']);
})->throws(OrderLockedException::class);

it('rolls back pending payment when gateway throws during processing', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 25.00,
        'customer_email' => 'buyer@example.com',
    ]);

    config([
        'payments.gateways.bank_transfer' => [
            'env_prefix' => 'BANK_TRANSFER',
            'api_key' => null,
            'secret' => null,
        ],
    ]);

    $service = new PaymentService(
        new OrderRepository,
        new PaymentRepository,
        app(\App\Services\PaymentGatewayManager::class),
    );

    try {
        $service->process($order->id, $user, ['payment_method' => 'bank_transfer']);
    } catch (\App\Exceptions\GatewayUnavailableException) {
        // expected
    }

    expect(Payment::query()->where('order_id', $order->id)->count())->toBe(0);
});

it('creates payment as pending then updates to gateway result', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'status' => OrderStatus::Confirmed,
        'total' => 25.00,
        'customer_email' => 'buyer@example.com',
    ]);

    $service = new PaymentService(
        new OrderRepository,
        new PaymentRepository,
        app(\App\Services\PaymentGatewayManager::class),
    );

    $payment = $service->process($order->id, $user, [
        'payment_method' => 'paypal',
    ]);

    expect($payment->status)->toBe(\App\Enums\PaymentStatus::Successful);
    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'successful',
    ]);
});
