<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            ['code' => 'credit_card', 'name' => 'Credit Card', 'config_key' => 'CREDIT_CARD'],
            ['code' => 'paypal', 'name' => 'PayPal', 'config_key' => 'PAYPAL'],
            ['code' => 'stripe', 'name' => 'Stripe', 'config_key' => 'STRIPE'],
            ['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'config_key' => 'BANK_TRANSFER'],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::query()->updateOrCreate(
                ['code' => $gateway['code']],
                [
                    'name' => $gateway['name'],
                    'is_enabled' => true,
                    'config_key' => $gateway['config_key'],
                ]
            );
        }
    }
}
