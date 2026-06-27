<?php

namespace App\Providers;

use App\PaymentGateways\BankTransferGateway;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;
use App\Services\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function (): PaymentGatewayManager {
            return new PaymentGatewayManager([
                new BankTransferGateway,
                new CreditCardGateway,
                new PayPalGateway,
                new StripeGateway,
            ]);
        });
    }
}
