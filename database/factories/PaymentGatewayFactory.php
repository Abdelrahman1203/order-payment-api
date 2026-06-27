<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentGateway> */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        return [
            'code' => 'credit_card',
            'name' => 'Credit Card',
            'is_enabled' => true,
            'config_key' => 'CREDIT_CARD',
        ];
    }
}
