<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => PaymentStatus::Successful,
            'payment_method' => 'credit_card',
            'amount' => fake()->randomFloat(2, 10, 500),
            'gateway_reference' => 'cc_'.fake()->uuid(),
            'failure_reason' => null,
        ];
    }
}
